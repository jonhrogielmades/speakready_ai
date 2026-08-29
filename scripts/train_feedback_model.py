#!/usr/bin/env python3
"""Train a small local feedback scoring model without external ML packages."""

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


def clamp(value: float, low: float = 0.0, high: float = 100.0) -> float:
    if not math.isfinite(value):
        return low
    return max(low, min(high, value))


def tokens(text: str) -> list[str]:
    return re.findall(r"[a-zA-Z][a-zA-Z']{1,}", text.lower())


def count(pattern: str, text: str) -> int:
    return len(re.findall(pattern, text, flags=re.IGNORECASE))


def hash_bucket(token: str) -> int:
    digest = hashlib.sha1(token.encode("utf-8")).hexdigest()
    return int(digest[:8], 16) % HASH_BUCKETS


def numeric(value: Any) -> float:
    try:
        parsed = float(value)
    except (TypeError, ValueError):
        return 0.0
    return parsed if math.isfinite(parsed) else 0.0


def text_features(row: dict[str, Any]) -> dict[str, float]:
    data = row.get("input", row)
    answer = str(data.get("answer", ""))
    question = str(data.get("question", ""))
    guide = str(data.get("expected_guide", ""))
    skills = " ".join(str(item) for item in data.get("mapped_skills", []) if item)
    combined = " ".join([question, guide, skills, answer])
    words = tokens(answer)
    question_words = set(tokens(" ".join([question, guide, skills])))
    answer_words = set(words)
    word_count = len(words)
    sentence_count = max(1, len(re.findall(r"[.!?]+", answer)) or 1)
    overlap = len(question_words & answer_words) / max(1, len(question_words))
    filler_count = count(r"\b(?:um|uh|ah|like|basically|actually|you know|sort of|kind of)\b", answer)
    action_count = count(
        r"\b(?:led|built|created|resolved|solved|improved|reduced|increased|delivered|designed|implemented|organized|managed|tested|analyzed|coordinated|handled|supported|communicated|verified|planned|documented|trained|presented|mentored)\b",
        answer,
    )
    result_count = count(
        r"\b(?:result|outcome|impact|improved|reduced|increased|delivered|saved|faster|resolved|completed|finished|passed|learned|success|percent|%)\b|\d+",
        answer,
    )
    first_person = count(r"\b(?:i|my|me|we|our)\b", answer)
    star_cues = count(r"\b(?:situation|task|action|result|challenge|goal|because|so|then|after|finally)\b", answer)
    punctuation = count(r"[,;:]", answer)

    features = {
        "bias": 1.0,
        "answer_words": min(word_count, 240) / 240.0,
        "answer_sentences": min(sentence_count, 20) / 20.0,
        "avg_sentence_words": min(word_count / sentence_count, 35) / 35.0,
        "question_overlap": overlap,
        "filler_ratio": min(filler_count / max(1, word_count), 0.25) * 4.0,
        "action_ratio": min(action_count / max(1, word_count), 0.20) * 5.0,
        "result_ratio": min(result_count / max(1, word_count), 0.20) * 5.0,
        "first_person_ratio": min(first_person / max(1, word_count), 0.30) * 3.333,
        "star_cue_ratio": min(star_cues / max(1, word_count), 0.25) * 4.0,
        "punctuation_ratio": min(punctuation / max(1, word_count), 0.20) * 5.0,
        "wpm": min(max(numeric(data.get("wpm")), 0), 220) / 220.0,
        "voice_duration": min(max(numeric(data.get("voice_duration")), 0), 300) / 300.0,
        "reported_fillers": min(max(numeric(data.get("filler_words_count")), 0), 30) / 30.0,
        "reported_pauses": min(max(numeric(data.get("pause_count")), 0), 30) / 30.0,
    }

    for token in tokens(combined)[:500]:
        features[f"h{hash_bucket(token)}"] = features.get(f"h{hash_bucket(token)}", 0.0) + 1.0 / 30.0

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


def train_field(samples: list[tuple[dict[str, float], float]], epochs: int) -> tuple[dict[str, float], float]:
    mean_target = statistics.mean(target for _, target in samples) / 100.0 if samples else 0.0
    weights: dict[str, float] = {"bias": mean_target}
    learning_rate = 0.045
    regularization = 0.0008
    rng = random.Random(1337)
    ordered = samples[:]

    for epoch in range(max(1, epochs)):
        rng.shuffle(ordered)
        rate = learning_rate / math.sqrt(1 + epoch * 0.08)
        for features, target in ordered:
            prediction = sum(weights.get(name, 0.0) * value for name, value in features.items())
            error = prediction - (target / 100.0)
            for name, value in features.items():
                weights[name] = weights.get(name, 0.0) - rate * (error * value + regularization * weights.get(name, 0.0))

    absolute_errors = []
    for features, target in samples:
        prediction = clamp(sum(weights.get(name, 0.0) * value for name, value in features.items()) * 100.0)
        absolute_errors.append(abs(prediction - target))

    return weights, statistics.mean(absolute_errors) if absolute_errors else 100.0


def target_value(row: dict[str, Any], field: str) -> float | None:
    output = row.get("output", {})
    if not isinstance(output, dict) or field not in output:
        return None
    value = numeric(output.get(field))
    return clamp(value)


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
        samples = []
        for row, features in feature_rows:
            target = target_value(row, field)
            if target is not None:
                samples.append((features, target))
        if not samples:
            continue

        weights, mae = train_field(samples, args.epochs)
        mean_target = statistics.mean(target for _, target in samples)
        models[field] = {
            "weights": {key: round(value, 8) for key, value in sorted(weights.items()) if abs(value) >= 0.000001},
            "fallback": round(mean_target, 3),
        }
        metrics[field] = {
            "examples": len(samples),
            "mean_absolute_error": round(mae, 3),
            "mean_target": round(mean_target, 3),
        }

    artifact = {
        "schema_version": 1,
        "model_type": "hashed_linear_feedback_scorer",
        "created_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
        "training_examples": len(rows),
        "training_dataset_checksum": file_sha256(args.train),
        "hash_buckets": HASH_BUCKETS,
        "score_fields": SCORE_FIELDS,
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
