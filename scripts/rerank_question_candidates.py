#!/usr/bin/env python3
"""Rerank SpeakReady question candidates with a local trained classifier."""

from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
import sys
from pathlib import Path
from typing import Any

_MODEL_CACHE: dict[str, tuple[Any, Any, Any]] = {}


def clean_text(value: Any, limit: int = 4000) -> str:
    return re.sub(r"\s+", " ", str(value or "")).strip()[:limit]


def normalized_text(value: Any) -> str:
    return re.sub(r"[^a-z0-9]+", " ", str(value or "").lower()).strip()


def tokens(value: Any) -> list[str]:
    return re.findall(r"[a-z0-9][a-z0-9']{1,}", str(value or "").lower())


def title_set(values: Any) -> set[str]:
    return {normalized_text(value) for value in (values or []) if normalized_text(value)}


def read_json(path: Path) -> dict[str, Any]:
    try:
        payload = json.loads(path.read_text(encoding="utf-8-sig"))
    except json.JSONDecodeError as exc:
        raise SystemExit(f"Invalid JSON file at {path}: {exc}") from exc
    if not isinstance(payload, dict):
        raise SystemExit(f"Expected JSON object at {path}")
    return payload


def read_label_map(path: Path, model_path: Path) -> dict[int, str]:
    labels: dict[int, str] = {}

    if path.is_file():
        payload = read_json(path)
        raw_labels = payload.get("labels", payload)
        if isinstance(raw_labels, dict):
            for key, value in raw_labels.items():
                match = re.search(r"(\d+)$", str(key))
                if match and clean_text(value):
                    labels[int(match.group(1))] = clean_text(value, 160)

    if labels:
        return labels

    config_path = model_path / "config.json"
    if not config_path.is_file():
        return {}

    config = read_json(config_path)
    raw_id2label = config.get("id2label", {})
    if isinstance(raw_id2label, dict):
        for key, value in raw_id2label.items():
            if str(key).isdigit() and clean_text(value):
                labels[int(key)] = clean_text(value, 160)

    return labels


def query_text(payload: dict[str, Any]) -> str:
    question_types = " ".join(clean_text(item, 80) for item in payload.get("question_types", []) if clean_text(item))
    parts = [
        "target role:",
        clean_text(payload.get("target_position"), 240),
        "category:",
        clean_text(payload.get("category"), 160),
        "difficulty:",
        clean_text(payload.get("difficulty"), 80),
        "question types:",
        question_types,
        "interview focus:",
        clean_text(payload.get("interview_focus"), 240),
        "job description:",
        clean_text(payload.get("job_description"), 1200),
        "resume:",
        clean_text(payload.get("resume_text"), 1200),
    ]
    return clean_text(" ".join(parts), 5000)


def candidate_text(candidate: dict[str, Any]) -> str:
    parts = [
        "question:",
        clean_text(candidate.get("question_text"), 1400),
        "expected guide:",
        clean_text(candidate.get("expected_guide"), 1200),
        "category:",
        clean_text(candidate.get("category"), 160),
        clean_text(candidate.get("archive_category"), 160),
        "type:",
        clean_text(candidate.get("type"), 80),
        "difficulty:",
        clean_text(candidate.get("difficulty"), 80),
        "skills:",
        " ".join(clean_text(skill, 120) for skill in candidate.get("mapped_skills", []) if clean_text(skill)),
        "roles:",
        " ".join(clean_text(role, 120) for role in candidate.get("archive_roles", []) if clean_text(role)),
    ]
    return clean_text(" ".join(parts), 5000)


def import_transformer_runtime() -> tuple[Any, Any, Any]:
    os.environ.setdefault("HF_HUB_DISABLE_PROGRESS_BARS", "1")
    os.environ.setdefault("TRANSFORMERS_NO_ADVISORY_WARNINGS", "1")

    try:
        import torch  # type: ignore
        from transformers import AutoModelForSequenceClassification, AutoTokenizer  # type: ignore
        from transformers.utils import logging as transformers_logging  # type: ignore
    except Exception as exc:
        raise RuntimeError(
            "The trained question model requires torch and transformers in this Python environment. "
            f"Original import error: {exc}"
        ) from exc

    transformers_logging.set_verbosity_error()
    transformers_logging.disable_progress_bar()

    return torch, AutoTokenizer, AutoModelForSequenceClassification


def classify_texts(model_path: Path, texts: list[str]) -> list[list[float]]:
    torch, tokenizer, model = load_model(model_path)

    results: list[list[float]] = []
    batch_size = 16
    with torch.inference_mode():
        for offset in range(0, len(texts), batch_size):
            batch = texts[offset : offset + batch_size]
            encoded = tokenizer(
                batch,
                truncation=True,
                padding=True,
                max_length=512,
                return_tensors="pt",
            )
            logits = model(**encoded).logits
            probabilities = torch.softmax(logits, dim=-1)
            results.extend([[float(value) for value in row] for row in probabilities.tolist()])

    return results


def load_model(model_path: Path) -> tuple[Any, Any, Any]:
    cache_key = str(model_path.resolve())
    if cache_key in _MODEL_CACHE:
        return _MODEL_CACHE[cache_key]

    torch, auto_tokenizer, auto_model = import_transformer_runtime()
    tokenizer = auto_tokenizer.from_pretrained(str(model_path), local_files_only=True)
    model = auto_model.from_pretrained(str(model_path), local_files_only=True)
    model.eval()
    _MODEL_CACHE[cache_key] = (torch, tokenizer, model)

    return _MODEL_CACHE[cache_key]


def probability_for_labels(probabilities: list[float], labels: dict[int, str], wanted: set[str]) -> float:
    if not probabilities or not wanted:
        return 0.0

    return max(
        (probabilities[index] for index, label in labels.items() if index < len(probabilities) and normalized_text(label) in wanted),
        default=0.0,
    )


def top_labels(probabilities: list[float], labels: dict[int, str], limit: int = 3) -> list[dict[str, Any]]:
    ranked = sorted(enumerate(probabilities), key=lambda item: item[1], reverse=True)
    return [
        {
            "label": labels.get(index, f"LABEL_{index}"),
            "confidence": round(float(probability), 6),
        }
        for index, probability in ranked[:limit]
    ]


def dot(left: list[float], right: list[float]) -> float:
    if not left or not right:
        return 0.0
    length = min(len(left), len(right))
    return sum(left[index] * right[index] for index in range(length))


def overlap_score(left_terms: set[str], right_text: str) -> float:
    right_terms = set(tokens(right_text))
    if not left_terms or not right_terms:
        return 0.0
    return len(left_terms & right_terms) / max(1, len(left_terms))


def stable_tiebreaker(candidate: dict[str, Any], payload: dict[str, Any]) -> float:
    raw = clean_text(candidate.get("dataset_record_id") or candidate.get("id") or candidate.get("question_text"))
    digest = hashlib.sha1((raw + "|" + clean_text(payload.get("target_position"))).encode("utf-8")).hexdigest()
    return int(digest[:6], 16) / 0xFFFFFF / 10000.0


def candidate_label_keys(candidate: dict[str, Any]) -> set[str]:
    values = [
        candidate.get("category"),
        candidate.get("archive_category"),
        candidate.get("type"),
    ]
    values.extend(candidate.get("mapped_skills", []) or [])
    return {normalized_text(value) for value in values if normalized_text(value)}


def metadata_bonus(candidate: dict[str, Any], payload: dict[str, Any]) -> tuple[float, list[str]]:
    bonus = 0.0
    reasons: list[str] = []

    requested_difficulty = normalized_text(payload.get("difficulty"))
    requested_types = title_set(payload.get("question_types"))
    role_terms = set(tokens(payload.get("target_position")))

    if requested_difficulty and normalized_text(candidate.get("difficulty")) == requested_difficulty:
        bonus += 0.08
        reasons.append("difficulty match")

    if requested_types and normalized_text(candidate.get("type")) in requested_types:
        bonus += 0.07
        reasons.append("question type match")

    candidate_source = candidate_text(candidate)
    role_overlap = overlap_score(role_terms, candidate_source)
    if role_overlap > 0:
        bonus += min(0.10, role_overlap * 0.10)
        reasons.append("role signal match")

    job_terms = set(tokens(payload.get("job_description")))
    job_overlap = overlap_score(job_terms, candidate_source)
    if job_overlap > 0:
        bonus += min(0.06, job_overlap * 0.06)
        reasons.append("job-description signal match")

    return bonus, reasons


def unavailable(reason: str) -> dict[str, Any]:
    return {
        "status": "unavailable",
        "reason": reason,
        "matches": [],
    }


def rerank(payload: dict[str, Any], model_path: Path, label_map_path: Path, limit: int) -> dict[str, Any]:
    candidates = [candidate for candidate in payload.get("candidates", []) if isinstance(candidate, dict)]
    candidates = [candidate for candidate in candidates if clean_text(candidate.get("question_text"))]
    if not candidates:
        return {"status": "no_candidates", "matches": []}

    if not model_path.is_dir():
        return unavailable(f"model path not found: {model_path}")

    labels = read_label_map(label_map_path, model_path)
    texts = [query_text(payload)] + [candidate_text(candidate) for candidate in candidates]

    try:
        distributions = classify_texts(model_path, texts)
    except Exception as exc:
        return unavailable(str(exc))

    if not distributions:
        return unavailable("model returned no probabilities")

    query_distribution = distributions[0]
    candidate_distributions = distributions[1:]
    scored: list[tuple[float, list[str], list[dict[str, Any]], dict[str, Any]]] = []
    total = max(1, len(candidates))

    for index, (candidate, distribution) in enumerate(zip(candidates, candidate_distributions)):
        model_similarity = dot(query_distribution, distribution)
        category_probability = probability_for_labels(distribution, labels, candidate_label_keys(candidate))
        query_category_probability = probability_for_labels(query_distribution, labels, candidate_label_keys(candidate))
        bonus, reasons = metadata_bonus(candidate, payload)
        base_rank_bonus = (1.0 - (index / total)) * 0.04

        score = (
            model_similarity * 0.52
            + category_probability * 0.20
            + query_category_probability * 0.16
            + bonus
            + base_rank_bonus
            + stable_tiebreaker(candidate, payload)
        )
        model_reasons = reasons[:]
        if model_similarity > 0:
            model_reasons.append("trained-model category similarity")
        if category_probability > 0:
            model_reasons.append("candidate category confidence")
        if query_category_probability > 0:
            model_reasons.append("context category confidence")

        scored.append((score, model_reasons, top_labels(distribution, labels), candidate))

    scored.sort(key=lambda item: item[0], reverse=True)
    matches = []
    for score, reasons, labels_for_candidate, candidate in scored[: max(1, limit)]:
        match = dict(candidate)
        match["trained_model_score"] = round(float(score), 6)
        match["trained_model_labels"] = labels_for_candidate
        match["recommendation_reason"] = ", ".join(reasons) if reasons else "trained-model rerank"
        match["selection_pipeline"] = "trained_question_model_rerank"
        matches.append(match)

    return {
        "status": "reranked" if matches else "no_match",
        "model_path": str(model_path),
        "label_map_path": str(label_map_path) if label_map_path else None,
        "query_labels": top_labels(query_distribution, labels),
        "matches": matches,
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--model", required=True)
    parser.add_argument("--label-map", default="")
    parser.add_argument("--limit", type=int, default=1)
    args = parser.parse_args()

    payload = json.load(sys.stdin)
    if not isinstance(payload, dict):
        raise SystemExit("Rerank payload must be a JSON object.")

    model_path = Path(args.model).resolve()
    label_map_path = Path(args.label_map).resolve() if args.label_map else model_path / "speakready_question_labels.json"
    print(json.dumps(rerank(payload, model_path, label_map_path, max(1, args.limit)), ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
