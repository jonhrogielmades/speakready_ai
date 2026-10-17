#!/usr/bin/env python3
"""Train the local feedback scoring model without external ML packages."""

from __future__ import annotations

import argparse
import hashlib
import json
import math
import os
import random
import re
import statistics
import time
from typing import Any


SCORE_FIELDS = [
    "score",
    "clarity_score",
    "relevance_score",
    "grammar_score",
    "professionalism_score",
    "star_method_score",
]
HASH_BUCKETS = 512
MODEL_SCHEMA_VERSION = 3
MODEL_TYPE = "hashed_linear_feedback_scorer_v3"
RANDOM_SEED = 1337
DEFAULT_VALIDATION_FRACTION = 0.20
DEFAULT_MIN_VALIDATION_EXAMPLES = 100
DEFAULT_MAX_VALIDATION_MAE = 5.0
DEFAULT_MIN_WITHIN_TEN_RATE = 0.95
DEFAULT_MIN_VALIDATION_SKILL = 0.10
DEFAULT_MIN_HUMAN_REVIEWED_RATIO = 1.0

ACTION_VERBS = (
    "adapted",
    "clarified",
    "checked",
    "collaborated",
    "explained",
    "found",
    "listened",
    "led",
    "built",
    "created",
    "resolved",
    "solved",
    "improved",
    "reduced",
    "increased",
    "delivered",
    "designed",
    "implemented",
    "organized",
    "managed",
    "tested",
    "analyzed",
    "coordinated",
    "handled",
    "supported",
    "communicated",
    "verified",
    "planned",
    "documented",
    "trained",
    "presented",
    "mentored",
)
RESULT_WORDS = (
    "result",
    "outcome",
    "impact",
    "improved",
    "reduced",
    "increased",
    "delivered",
    "saved",
    "faster",
    "resolved",
    "completed",
    "finished",
    "passed",
    "learned",
    "success",
    "percent",
)
FILLER_WORDS = ("um", "uh", "ah", "like", "basically", "actually", "you know", "sort of", "kind of")
HEDGE_WORDS = ("maybe", "probably", "possibly", "i think", "i guess", "just", "stuff", "things")


def clamp(value: Any, low: float = 0.0, high: float = 100.0) -> float:
    try:
        parsed = float(value)
    except (TypeError, ValueError):
        return low
    if not math.isfinite(parsed):
        return low
    return max(low, min(high, parsed))


def numeric(value: Any) -> float:
    return clamp(value, 0.0, 1_000_000.0)


def tokens(text: Any) -> list[str]:
    return re.findall(r"[a-zA-Z][a-zA-Z']{1,}", str(text or "").lower())


def count(pattern: str, text: str) -> int:
    return len(re.findall(pattern, text, flags=re.IGNORECASE))


def hash_bucket(token: str) -> int:
    digest = hashlib.sha1(token.encode("utf-8")).hexdigest()
    return int(digest[:8], 16) % HASH_BUCKETS


def signed_hash(prefix: str, token: str) -> tuple[str, float]:
    digest = hashlib.sha1(f"{prefix}:{token}".encode("utf-8")).hexdigest()
    bucket = int(digest[:8], 16) % HASH_BUCKETS
    sign = 1.0 if int(digest[8:10], 16) % 2 == 0 else -1.0
    return f"{prefix}{bucket}", sign


def stem(token: str) -> str:
    for suffix in ("ingly", "edly", "ing", "ed", "ies", "s"):
        if len(token) > len(suffix) + 3 and token.endswith(suffix):
            if suffix == "ies":
                return token[: -len(suffix)] + "y"
            return token[: -len(suffix)]
    return token


def ratio(numerator: float, denominator: float, cap: float = 1.0) -> float:
    if denominator <= 0:
        return 0.0
    return min(max(numerator / denominator, 0.0), cap)


def add_feature(features: dict[str, float], name: str, value: float) -> None:
    if value == 0.0 or not math.isfinite(value):
        return
    features[name] = clamp(features.get(name, 0.0) + value, -2.0, 2.0)


def add_signed_hashes(features: dict[str, float], prefix: str, terms: list[str], weight: float, limit: int) -> None:
    for term in terms[:limit]:
        key, sign = signed_hash(prefix, term)
        add_feature(features, key, sign * weight)


def ngrams(items: list[str], size: int) -> list[str]:
    if len(items) < size:
        return []
    return ["_".join(items[index : index + size]) for index in range(0, len(items) - size + 1)]


def text_features(row: dict[str, Any]) -> dict[str, float]:
    data = row.get("input", row)
    if not isinstance(data, dict):
        data = {}

    answer = str(data.get("answer", ""))
    question = str(data.get("question", ""))
    guide = str(data.get("expected_guide", ""))
    skills = " ".join(str(item) for item in data.get("mapped_skills", []) if item)
    category = str(data.get("category", ""))
    target_position = str(data.get("target_position", ""))
    difficulty = str(data.get("difficulty", ""))
    context = " ".join([question, guide, skills, category, target_position, difficulty])
    combined = " ".join([context, answer])

    answer_tokens = tokens(answer)
    answer_stems = [stem(token) for token in answer_tokens]
    context_stems = [stem(token) for token in tokens(context)]
    answer_set = set(answer_stems)
    context_set = set(context_stems)
    word_count = len(answer_tokens)
    sentence_count = max(1, len(re.findall(r"[.!?]+", answer)) or 1)
    filler_count = count(r"\b(?:%s)\b" % "|".join(re.escape(word) for word in FILLER_WORDS), answer)
    action_count = count(r"\b(?:%s)\b" % "|".join(ACTION_VERBS), answer)
    result_count = count(r"\b(?:%s)\b|%%|\d+" % "|".join(RESULT_WORDS), answer)
    first_person = count(r"\b(?:i|my|me|we|our)\b", answer)
    transition_count = count(r"\b(?:because|therefore|then|after|before|while|when|so|finally|however|also|first|next)\b", answer)
    hedge_count = count(r"\b(?:%s)\b" % "|".join(re.escape(word) for word in HEDGE_WORDS), answer)
    number_count = count(r"\b\d+(?:[.,]\d+)?%?\b", answer)
    long_words = sum(1 for token in answer_tokens if len(token) >= 8)
    duration = numeric(data.get("voice_duration"))
    wpm = numeric(data.get("wpm"))
    reported_fillers = numeric(data.get("filler_words_count"))
    pauses = numeric(data.get("pause_count"))
    ideal_wpm = max(0.0, 1.0 - abs(min(wpm, 260.0) - 145.0) / 145.0) if wpm > 0 else 0.0

    features = {
        "bias": 1.0,
        "answer_words": min(word_count, 240) / 240.0,
        "answer_chars": min(len(answer), 1600) / 1600.0,
        "answer_sentences": min(sentence_count, 20) / 20.0,
        "avg_sentence_words": min(word_count / sentence_count, 35) / 35.0,
        "context_overlap": ratio(len(answer_set & context_set), max(1, len(context_set))),
        "unique_word_ratio": ratio(len(set(answer_tokens)), max(1, word_count)),
        "long_word_ratio": ratio(long_words, max(1, word_count), 0.45) / 0.45,
        "filler_ratio": ratio(filler_count, max(1, word_count), 0.25) * 4.0,
        "action_ratio": ratio(action_count, max(1, word_count), 0.20) * 5.0,
        "result_ratio": ratio(result_count, max(1, word_count), 0.20) * 5.0,
        "specific_number_ratio": ratio(number_count, max(1, word_count), 0.12) / 0.12,
        "transition_ratio": ratio(transition_count, max(1, word_count), 0.20) * 5.0,
        "hedge_ratio": ratio(hedge_count, max(1, word_count), 0.25) * 4.0,
        "first_person_ratio": ratio(first_person, max(1, word_count), 0.30) / 0.30,
        "very_short_answer": 1.0 if 0 < word_count < 10 else 0.0,
        "short_answer": 1.0 if 10 <= word_count < 35 else 0.0,
        "substantial_answer": 1.0 if word_count >= 60 else 0.0,
        "wpm": min(wpm, 220.0) / 220.0,
        "ideal_wpm_score": ideal_wpm,
        "voice_duration": min(duration, 300.0) / 300.0,
        "reported_fillers": min(reported_fillers, 30.0) / 30.0,
        "reported_pauses": min(pauses, 30.0) / 30.0,
    }

    action_regex = r"\b(?:i|we)\s+(?:%s)\b" % "|".join(ACTION_VERBS)
    features["star_has_situation"] = 1.0 if count(r"\b(?:when|while|during|challenge|problem|situation)\b", answer) else 0.0
    features["star_has_task"] = 1.0 if count(r"\b(?:my task|my goal|i needed|i had to|responsible|objective|assigned)\b", answer) else 0.0
    features["star_has_action"] = 1.0 if count(action_regex, answer) else 0.0
    features["star_has_result"] = 1.0 if result_count else 0.0
    features["star_completeness"] = sum(features[key] for key in ("star_has_situation", "star_has_task", "star_has_action", "star_has_result")) / 4.0

    for name, value in (
        ("question_type", data.get("question_type", "")),
        ("category", category),
        ("difficulty", difficulty),
        ("response_mode", data.get("response_mode", "")),
    ):
        slug = re.sub(r"[^a-z0-9]+", "_", str(value).lower()).strip("_")[:48]
        if slug:
            features[f"cat:{name}:{slug}"] = 1.0

    for token in tokens(combined)[:500]:
        add_feature(features, f"h{hash_bucket(token)}", 1.0 / 30.0)

    add_signed_hashes(features, "ah", answer_stems, 1.0 / 26.0, 500)
    add_signed_hashes(features, "a2h", ngrams(answer_stems, 2), 1.0 / 18.0, 500)
    add_signed_hashes(features, "a3h", ngrams(answer_stems, 3), 1.0 / 15.0, 400)
    add_signed_hashes(features, "ch", context_stems, 1.0 / 28.0, 400)

    return {key: clamp(value, -2.0, 2.0) for key, value in features.items()}


def read_jsonl(path: str) -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []
    with open(path, "r", encoding="utf-8") as handle:
        for line_no, line in enumerate(handle, 1):
            line = line.strip()
            if not line:
                continue
            try:
                row = json.loads(line)
            except json.JSONDecodeError as exc:
                raise SystemExit(f"Invalid JSONL at line {line_no}: {exc}") from exc
            if isinstance(row, dict):
                rows.append(row)
    return rows


def file_sha256(path: str) -> str:
    digest = hashlib.sha256()
    with open(path, "rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def stable_row_key(row: dict[str, Any]) -> str:
    data = row.get("input", {}) if isinstance(row.get("input", {}), dict) else {}
    metadata = row.get("metadata", {}) if isinstance(row.get("metadata", {}), dict) else {}
    for group_field in ("session_id", "answer_id", "record_id"):
        group_value = data.get(group_field)
        if group_value is not None and str(group_value).strip():
            return f"{group_field}:{group_value}"

    parts = [
        row.get("source", ""),
        data.get("question", ""),
        data.get("answer", ""),
        metadata.get("created_at", ""),
    ]
    return "row:" + hashlib.sha256("|".join(str(value) for value in parts).encode("utf-8")).hexdigest()


def split_rows(rows: list[dict[str, Any]], validation_fraction: float) -> tuple[list[dict[str, Any]], list[dict[str, Any]]]:
    """Create a deterministic group-aware holdout to prevent session leakage."""
    if len(rows) < 10 or validation_fraction <= 0:
        return rows[:], []

    validation_fraction = max(0.05, min(0.40, validation_fraction))
    grouped: dict[str, list[dict[str, Any]]] = {}
    for row in rows:
        grouped.setdefault(stable_row_key(row), []).append(row)

    ranked_groups = sorted(
        grouped.items(),
        key=lambda item: hashlib.sha256(f"{RANDOM_SEED}|{item[0]}".encode("utf-8")).hexdigest(),
    )
    if len(ranked_groups) < 2:
        # A holdout from the same interview/session would leak context into
        # validation. Keep all rows for training and let the quality gate fail
        # closed until a genuinely independent group exists.
        return rows[:], []

    validation_target = max(1, int(round(len(rows) * validation_fraction)))
    validation: list[dict[str, Any]] = []
    training: list[dict[str, Any]] = []

    for index, (_, group_rows) in enumerate(ranked_groups):
        groups_remaining = len(ranked_groups) - index - 1
        if len(validation) < validation_target and groups_remaining > 0:
            validation.extend(group_rows)
        else:
            training.extend(group_rows)

    return training, validation


def is_human_reviewed(row: dict[str, Any]) -> bool:
    source = str(row.get("source", "")).strip().lower()
    metadata = row.get("metadata", {}) if isinstance(row.get("metadata", {}), dict) else {}
    status = str(metadata.get("audit_status", "")).strip().lower()
    origin = str(metadata.get("data_origin", "")).strip().lower()
    label_policy = str(metadata.get("label_policy", "")).strip().lower()
    return (
        source == "interview_answers"
        and status in {"approved", "archived"}
        and origin == "human_reviewed_interview_answer"
        and label_policy == "approved_or_archived_admin_review"
    )


def dataset_summary(rows: list[dict[str, Any]]) -> dict[str, Any]:
    sources: dict[str, int] = {}
    origins: dict[str, int] = {}
    human_reviewed = 0

    for row in rows:
        source = str(row.get("source", "unknown") or "unknown")
        metadata = row.get("metadata", {}) if isinstance(row.get("metadata", {}), dict) else {}
        origin = str(metadata.get("data_origin", "unspecified") or "unspecified")
        sources[source] = sources.get(source, 0) + 1
        origins[origin] = origins.get(origin, 0) + 1
        if is_human_reviewed(row):
            human_reviewed += 1

    total = len(rows)
    return {
        "total_examples": total,
        "human_reviewed_examples": human_reviewed,
        "human_reviewed_ratio": round(human_reviewed / total, 6) if total else 0.0,
        "sources": dict(sorted(sources.items())),
        "data_origins": dict(sorted(origins.items())),
    }


def target_value(row: dict[str, Any], field: str) -> float | None:
    output = row.get("output", {})
    if not isinstance(output, dict) or field not in output:
        return None
    return clamp(output.get(field))


def feature_scales(samples: list[tuple[dict[str, float], float]], names: list[str]) -> dict[str, float]:
    totals = {name: 0.0 for name in names}
    for features, _ in samples:
        for name in names:
            value = features.get(name, 0.0)
            totals[name] += value * value
    count_samples = max(1, len(samples))
    return {name: 1.0 if name == "bias" else max(math.sqrt(total / count_samples), 0.05) for name, total in totals.items()}


def predict_member(weights: dict[str, float], features: dict[str, float], scales: dict[str, float]) -> float:
    return sum(weight * (features.get(name, 0.0) / scales.get(name, 1.0)) for name, weight in weights.items())


def train_member(
    samples: list[tuple[dict[str, float], float]],
    scales: dict[str, float],
    mean_target: float,
    epochs: int,
    seed: int,
) -> dict[str, float]:
    weights: dict[str, float] = {"bias": mean_target / 100.0}
    rng = random.Random(seed)
    learning_rate = 0.035
    regularization = 0.0012

    for epoch in range(max(1, epochs)):
        ordered = samples[:]
        rng.shuffle(ordered)
        rate = learning_rate / math.sqrt(1 + epoch * 0.10)
        for features, target in ordered:
            error = max(-0.45, min(0.45, predict_member(weights, features, scales) - target / 100.0))
            for name, value in features.items():
                scaled = value / scales.get(name, 1.0)
                current = weights.get(name, 0.0)
                weights[name] = current - rate * (error * scaled + regularization * current)

    return {name: round(value, 8) for name, value in sorted(weights.items()) if name == "bias" or abs(value) >= 0.000001}


def predict_model(field_model: dict[str, Any], features: dict[str, float]) -> float:
    members = field_model.get("members", [])
    scales = {str(name): float(value) for name, value in field_model.get("scales", {}).items()}
    fallback = float(field_model.get("fallback", 0.0))
    confidence = float(field_model.get("confidence", 1.0))
    predictions = []

    for member in members if isinstance(members, list) else []:
        weights = member.get("weights", {}) if isinstance(member, dict) else {}
        if isinstance(weights, dict):
            predictions.append(predict_member({str(k): float(v) for k, v in weights.items()}, features, scales) * 100.0)

    if not predictions:
        return clamp(fallback)

    raw = statistics.mean(predictions)
    return clamp(fallback + confidence * (raw - fallback))


def error_metrics(field_model: dict[str, Any], samples: list[tuple[dict[str, float], float]]) -> dict[str, Any]:
    if not samples:
        return {
            "examples": 0,
            "mean_absolute_error": None,
            "root_mean_squared_error": None,
            "median_absolute_error": None,
            "mean_error_bias": None,
            "within_5_rate": None,
            "within_10_rate": None,
            "within_15_rate": None,
        }

    residuals = [predict_model(field_model, features) - target for features, target in samples]
    absolute_errors = [abs(value) for value in residuals]
    return {
        "examples": len(samples),
        "mean_absolute_error": round(statistics.mean(absolute_errors), 3),
        "root_mean_squared_error": round(math.sqrt(statistics.mean(value * value for value in residuals)), 3),
        "median_absolute_error": round(statistics.median(absolute_errors), 3),
        "mean_error_bias": round(statistics.mean(residuals), 3),
        "within_5_rate": round(sum(error <= 5 for error in absolute_errors) / len(absolute_errors), 6),
        "within_10_rate": round(sum(error <= 10 for error in absolute_errors) / len(absolute_errors), 6),
        "within_15_rate": round(sum(error <= 15 for error in absolute_errors) / len(absolute_errors), 6),
    }


def add_baseline_skill(metrics: dict[str, Any], samples: list[tuple[dict[str, float], float]], baseline: float) -> dict[str, Any]:
    if not samples or metrics.get("mean_absolute_error") is None:
        metrics["baseline_mean_absolute_error"] = None
        metrics["skill_score"] = None
        return metrics

    baseline_mae = statistics.mean(abs(baseline - target) for _, target in samples)
    model_mae = float(metrics["mean_absolute_error"])
    metrics["baseline_mean_absolute_error"] = round(baseline_mae, 3)
    metrics["skill_score"] = round(1.0 - (model_mae / baseline_mae), 6) if baseline_mae > 0 else 0.0
    return metrics


def train_field(
    training_samples: list[tuple[dict[str, float], float]],
    validation_samples: list[tuple[dict[str, float], float]],
    epochs: int,
    field: str,
) -> tuple[dict[str, Any], dict[str, Any]]:
    names = sorted({name for features, _ in training_samples for name, value in features.items() if value != 0.0})
    scales = feature_scales(training_samples, names)
    mean_target = statistics.mean(target for _, target in training_samples)
    sample_confidence = len(training_samples) / (len(training_samples) + 100.0)
    ensemble_size = 7 if len(training_samples) >= 250 else 5 if len(training_samples) >= 75 else 3 if len(training_samples) >= 25 else 1
    seed_offset = int(hashlib.sha1(field.encode("utf-8")).hexdigest()[:6], 16)
    members = [
        {
            "seed": RANDOM_SEED + index,
            "weights": train_member(training_samples, scales, mean_target, epochs, RANDOM_SEED + seed_offset + index),
        }
        for index in range(ensemble_size)
    ]
    field_model = {
        "algorithm": "scaled_bagged_elastic_net_sgd",
        "fallback": round(mean_target, 3),
        "confidence": round(sample_confidence, 6),
        "examples": len(training_samples),
        "feature_count": len(names),
        "scales": {name: round(value, 8) for name, value in sorted(scales.items()) if name == "bias" or abs(value - 1.0) >= 0.000001},
        "weights": members[0]["weights"],
        "members": members,
    }
    provisional_validation_metrics = error_metrics(field_model, validation_samples)

    if validation_samples and provisional_validation_metrics["mean_absolute_error"] is not None:
        validation_confidence = max(0.0, 1.0 - float(provisional_validation_metrics["mean_absolute_error"]) / 30.0)
        field_model["confidence"] = round(min(sample_confidence, validation_confidence), 6)
    else:
        field_model["confidence"] = round(min(sample_confidence, 0.25), 6)

    training_metrics = add_baseline_skill(error_metrics(field_model, training_samples), training_samples, mean_target)
    validation_metrics = add_baseline_skill(error_metrics(field_model, validation_samples), validation_samples, mean_target)

    metrics = {
        "examples": len(training_samples),
        "training_examples": len(training_samples),
        "validation_examples": len(validation_samples),
        "mean_absolute_error": training_metrics["mean_absolute_error"],
        "training_mean_absolute_error": training_metrics["mean_absolute_error"],
        "validation_mean_absolute_error": validation_metrics["mean_absolute_error"],
        "training": training_metrics,
        "validation": validation_metrics,
        "mean_target": round(mean_target, 3),
        "confidence": field_model["confidence"],
        "feature_count": len(names),
        "ensemble_size": ensemble_size,
    }
    return field_model, metrics


def quality_summary(
    metrics: dict[str, Any],
    data_summary: dict[str, Any],
    validation_examples: int,
    minimum_validation_examples: int,
    maximum_validation_mae: float,
    minimum_within_ten_rate: float,
    minimum_validation_skill: float,
    minimum_human_reviewed_ratio: float,
) -> dict[str, Any]:
    validation_maes = [
        float(value["validation"]["mean_absolute_error"])
        for value in metrics.values()
        if value.get("validation", {}).get("mean_absolute_error") is not None
    ]
    within_ten_rates = [
        float(value["validation"]["within_10_rate"])
        for value in metrics.values()
        if value.get("validation", {}).get("within_10_rate") is not None
    ]
    skill_scores = [
        float(value["validation"]["skill_score"])
        for value in metrics.values()
        if value.get("validation", {}).get("skill_score") is not None
    ]
    human_ratio = float(data_summary.get("human_reviewed_ratio", 0.0))
    reasons: list[str] = []

    if validation_examples < minimum_validation_examples:
        reasons.append("insufficient_validation_examples")
    if len(validation_maes) != len(SCORE_FIELDS):
        reasons.append("missing_field_validation_metrics")
    if validation_maes and max(validation_maes) > maximum_validation_mae:
        reasons.append("validation_mae_above_limit")
    if within_ten_rates and min(within_ten_rates) < minimum_within_ten_rate:
        reasons.append("validation_within_10_rate_below_target")
    if len(skill_scores) != len(SCORE_FIELDS):
        reasons.append("validation_skill_missing")
    elif min(skill_scores) < minimum_validation_skill:
        reasons.append("validation_skill_below_target")
    if human_ratio < minimum_human_reviewed_ratio:
        reasons.append("insufficient_human_reviewed_labels")

    return {
        "validated": validation_examples >= minimum_validation_examples and len(validation_maes) == len(SCORE_FIELDS),
        "production_ready": not reasons,
        "reasons": reasons,
        "validation_examples": validation_examples,
        "worst_validation_mae": round(max(validation_maes), 3) if validation_maes else None,
        "lowest_validation_within_10_rate": round(min(within_ten_rates), 6) if within_ten_rates else None,
        "lowest_validation_skill_score": round(min(skill_scores), 6) if skill_scores else None,
        "human_reviewed_ratio": round(human_ratio, 6),
        "thresholds": {
            "minimum_validation_examples": minimum_validation_examples,
            "maximum_validation_mae": maximum_validation_mae,
            "minimum_within_10_rate": minimum_within_ten_rate,
            "minimum_validation_skill": minimum_validation_skill,
            "minimum_human_reviewed_ratio": minimum_human_reviewed_ratio,
        },
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--train", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--epochs", type=int, default=80)
    parser.add_argument("--validation-fraction", type=float, default=DEFAULT_VALIDATION_FRACTION)
    parser.add_argument("--min-validation-examples", type=int, default=DEFAULT_MIN_VALIDATION_EXAMPLES)
    parser.add_argument("--max-validation-mae", type=float, default=DEFAULT_MAX_VALIDATION_MAE)
    parser.add_argument("--min-within-ten-rate", type=float, default=DEFAULT_MIN_WITHIN_TEN_RATE)
    parser.add_argument("--min-validation-skill", type=float, default=DEFAULT_MIN_VALIDATION_SKILL)
    parser.add_argument("--min-human-reviewed-ratio", type=float, default=DEFAULT_MIN_HUMAN_REVIEWED_RATIO)
    args = parser.parse_args()

    rows = read_jsonl(args.train)
    if not rows:
        raise SystemExit("No training rows found.")

    training_rows, validation_rows = split_rows(rows, args.validation_fraction)
    training_features = [(row, text_features(row)) for row in training_rows]
    validation_features = [(row, text_features(row)) for row in validation_rows]
    models: dict[str, Any] = {}
    metrics: dict[str, Any] = {}

    for field in SCORE_FIELDS:
        training_samples = [(features, target) for row, features in training_features if (target := target_value(row, field)) is not None]
        validation_samples = [(features, target) for row, features in validation_features if (target := target_value(row, field)) is not None]
        if not training_samples:
            continue
        models[field], metrics[field] = train_field(training_samples, validation_samples, args.epochs, field)

    data_summary = dataset_summary(rows)
    artifact_quality = quality_summary(
        metrics,
        data_summary,
        len(validation_rows),
        max(100, args.min_validation_examples),
        min(5.0, max(0.0, args.max_validation_mae)),
        max(0.95, min(1.0, args.min_within_ten_rate)),
        max(0.10, min(1.0, args.min_validation_skill)),
        1.0,
    )

    artifact = {
        "schema_version": MODEL_SCHEMA_VERSION,
        "model_type": MODEL_TYPE,
        "created_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
        "total_examples": len(rows),
        "training_examples": len(training_rows),
        "validation_examples": len(validation_rows),
        "training_dataset_checksum": file_sha256(args.train),
        "split": {
            "method": "deterministic_group_holdout_sha256",
            "seed": RANDOM_SEED,
            "validation_fraction": max(0.0, min(0.40, args.validation_fraction)),
        },
        "dataset_summary": data_summary,
        "quality_summary": artifact_quality,
        "hash_buckets": HASH_BUCKETS,
        "score_fields": SCORE_FIELDS,
        "feature_pipeline": {
            "version": 2,
            "hash_buckets": HASH_BUCKETS,
            "legacy_hash_features": True,
            "answer_hash_prefixes": ["ah", "a2h", "a3h"],
            "context_hash_prefixes": ["ch"],
            "scaling": "sparse_rms_no_centering",
            "small_data_shrinkage": "fallback + confidence * learned_delta",
        },
        "models": models,
        "metrics": metrics,
    }

    os.makedirs(os.path.dirname(os.path.abspath(args.output)), exist_ok=True)
    with open(args.output, "w", encoding="utf-8") as handle:
        json.dump(artifact, handle, ensure_ascii=False, indent=2)
        handle.write("\n")

    print(json.dumps({
        "status": "trained",
        "examples": len(rows),
        "training_examples": len(training_rows),
        "validation_examples": len(validation_rows),
        "production_ready": artifact_quality["production_ready"],
        "quality_summary": artifact_quality,
        "output": args.output,
        "metrics": metrics,
    }, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
