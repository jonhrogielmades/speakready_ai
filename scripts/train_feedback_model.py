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
MODEL_SCHEMA_VERSION = 2
MODEL_TYPE = "hashed_linear_feedback_scorer_v2"
RANDOM_SEED = 1337

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


def train_field(samples: list[tuple[dict[str, float], float]], epochs: int, field: str) -> tuple[dict[str, Any], dict[str, Any]]:
    names = sorted({name for features, _ in samples for name, value in features.items() if value != 0.0})
    scales = feature_scales(samples, names)
    mean_target = statistics.mean(target for _, target in samples)
    confidence = round(len(samples) / (len(samples) + 40.0), 6)
    ensemble_size = 7 if len(samples) >= 250 else 5 if len(samples) >= 75 else 3 if len(samples) >= 25 else 1
    seed_offset = int(hashlib.sha1(field.encode("utf-8")).hexdigest()[:6], 16)
    members = [
        {
            "seed": RANDOM_SEED + index,
            "weights": train_member(samples, scales, mean_target, epochs, RANDOM_SEED + seed_offset + index),
        }
        for index in range(ensemble_size)
    ]
    field_model = {
        "algorithm": "scaled_bagged_elastic_net_sgd",
        "fallback": round(mean_target, 3),
        "confidence": confidence,
        "examples": len(samples),
        "feature_count": len(names),
        "scales": {name: round(value, 8) for name, value in sorted(scales.items()) if name == "bias" or abs(value - 1.0) >= 0.000001},
        "weights": members[0]["weights"],
        "members": members,
    }
    errors = [abs(predict_model(field_model, features) - target) for features, target in samples]
    mean_error = statistics.mean(errors) if errors else 100.0
    metrics = {
        "examples": len(samples),
        "mean_absolute_error": round(mean_error, 3),
        "training_mean_absolute_error": round(mean_error, 3),
        "validation_mean_absolute_error": None,
        "mean_target": round(mean_target, 3),
        "confidence": confidence,
        "feature_count": len(names),
        "ensemble_size": ensemble_size,
    }
    return field_model, metrics


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--train", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--epochs", type=int, default=80)
    args = parser.parse_args()

    rows = read_jsonl(args.train)
    if not rows:
        raise SystemExit("No training rows found.")

    feature_rows = [(row, text_features(row)) for row in rows]
    models: dict[str, Any] = {}
    metrics: dict[str, Any] = {}

    for field in SCORE_FIELDS:
        samples = [(features, target) for row, features in feature_rows if (target := target_value(row, field)) is not None]
        if not samples:
            continue
        models[field], metrics[field] = train_field(samples, args.epochs, field)

    artifact = {
        "schema_version": MODEL_SCHEMA_VERSION,
        "model_type": MODEL_TYPE,
        "created_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
        "training_examples": len(rows),
        "training_dataset_checksum": file_sha256(args.train),
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

    print(json.dumps({"status": "trained", "examples": len(rows), "output": args.output, "metrics": metrics}, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
