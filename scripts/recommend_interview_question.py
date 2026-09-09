#!/usr/bin/env python3
"""Recommend interview questions from a SpeakReady embedding database."""

from __future__ import annotations

import argparse
import hashlib
import json
import math
import re
import sys
from pathlib import Path
from typing import Any

from build_question_embedding_index import DEFAULT_MODEL, lexical_vector, normalized_text, tokens


DATASET_ALIASES = {
    "ph_job_interview": {"ph_job_interview", "general_interview_official", "candidate_questions"},
    "ph_bpo_communication": {"ph_bpo_communication"},
    "ph_college_admission": {"ph_college_admission"},
}


def clean_text(value: Any, limit: int = 4000) -> str:
    return re.sub(r"\s+", " ", str(value or "")).strip()[:limit]


def cosine(left: list[float], right: list[float]) -> float:
    if not left or not right or len(left) != len(right):
        return 0.0
    return sum(a * b for a, b in zip(left, right))


def read_index(path: Path) -> dict[str, Any]:
    try:
        payload = json.loads(path.read_text(encoding="utf-8"))
    except json.JSONDecodeError as exc:
        raise SystemExit(f"Invalid question embedding index: {exc}") from exc
    if not isinstance(payload, dict) or not isinstance(payload.get("records"), list):
        raise SystemExit("Question embedding index is missing a records array.")
    return payload


def title_set(values: Any) -> set[str]:
    return {normalized_text(value) for value in (values or []) if normalized_text(value)}


def compatible_dataset_keys(dataset_key: str) -> set[str]:
    normalized = normalized_text(dataset_key).replace(" ", "_")
    return DATASET_ALIASES.get(normalized, {normalized}) if normalized else set()


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
        "company persona:",
        clean_text(payload.get("company_persona"), 240),
        "job description:",
        clean_text(payload.get("job_description"), 1200),
        "resume:",
        clean_text(payload.get("resume_text"), 1200),
    ]
    return clean_text(" ".join(parts), 5000)


def sentence_bert_query_vector(text: str, model_name: str) -> list[float]:
    try:
        from sentence_transformers import SentenceTransformer  # type: ignore
    except Exception as exc:
        raise SystemExit(
            "Sentence-BERT recommendation requires sentence-transformers in this Python environment. "
            f"Original import error: {exc}"
        ) from exc

    model = SentenceTransformer(model_name or DEFAULT_MODEL)
    encoded = model.encode([text], convert_to_numpy=True, normalize_embeddings=True)
    return [float(value) for value in encoded[0]]


def query_vector(index: dict[str, Any], payload: dict[str, Any]) -> list[float]:
    text = query_text(payload)
    backend = index.get("embedding_backend")
    if backend == "sentence-bert":
        return sentence_bert_query_vector(text, str(index.get("embedding_model") or DEFAULT_MODEL))
    if backend == "lexical_hash":
        return lexical_vector(text, int(index.get("embedding_dimensions") or 384))
    raise SystemExit(f"Unsupported embedding backend: {backend}")


def overlap_score(left: set[str], right_text: str) -> float:
    right = set(tokens(right_text))
    if not left or not right:
        return 0.0
    return len(left & right) / max(1, len(left))


def metadata_bonus(record: dict[str, Any], payload: dict[str, Any]) -> tuple[float, list[str]]:
    bonus = 0.0
    reasons: list[str] = []
    requested_difficulty = normalized_text(payload.get("difficulty"))
    requested_types = title_set(payload.get("question_types"))
    requested_category = normalized_text(payload.get("category"))
    role_terms = set(tokens(payload.get("target_position")))
    role_source = " ".join(
        [
            clean_text(record.get("question_text")),
            clean_text(record.get("expected_guide")),
            " ".join(clean_text(role) for role in record.get("archive_roles", []) if clean_text(role)),
            " ".join(clean_text(skill) for skill in record.get("mapped_skills", []) if clean_text(skill)),
        ]
    )

    if requested_difficulty and normalized_text(record.get("difficulty")) == requested_difficulty:
        bonus += 0.08
        reasons.append("difficulty match")

    if requested_types and normalized_text(record.get("type")) in requested_types:
        bonus += 0.07
        reasons.append("question type match")

    if requested_category and normalized_text(record.get("category")) == requested_category:
        bonus += 0.05
        reasons.append("category match")

    role_overlap = overlap_score(role_terms, role_source)
    if role_overlap > 0:
        bonus += min(0.10, role_overlap * 0.10)
        reasons.append("role signal match")

    job_terms = set(tokens(payload.get("job_description")))
    job_overlap = overlap_score(job_terms, role_source)
    if job_overlap > 0:
        bonus += min(0.06, job_overlap * 0.06)
        reasons.append("job-description signal match")

    return bonus, reasons


def should_keep(record: dict[str, Any], payload: dict[str, Any], excluded: set[str]) -> bool:
    question_text = clean_text(record.get("question_text"))
    if not question_text or normalized_text(question_text) in excluded:
        return False

    compatible_keys = compatible_dataset_keys(str(payload.get("dataset_key") or ""))
    if compatible_keys and str(record.get("dataset_key") or "") not in compatible_keys:
        return False

    requested_types = title_set(payload.get("question_types"))
    if requested_types and normalized_text(record.get("type")) not in requested_types:
        return False

    return True


def stable_tiebreaker(record: dict[str, Any], payload: dict[str, Any]) -> float:
    digest = hashlib.sha1((clean_text(record.get("id")) + "|" + clean_text(payload.get("target_position"))).encode("utf-8")).hexdigest()
    return int(digest[:6], 16) / 0xFFFFFF / 10000.0


def recommend(index: dict[str, Any], payload: dict[str, Any], limit: int) -> dict[str, Any]:
    vector = query_vector(index, payload)
    excluded = {normalized_text(text) for text in payload.get("exclude_question_texts", []) if normalized_text(text)}
    candidates = []

    for record in index.get("records", []):
        if not isinstance(record, dict) or not should_keep(record, payload, excluded):
            continue
        embedding = record.get("embedding", [])
        if not isinstance(embedding, list):
            continue

        similarity = cosine(vector, [float(value) for value in embedding])
        bonus, reasons = metadata_bonus(record, payload)
        score = similarity + bonus + stable_tiebreaker(record, payload)
        candidates.append((score, similarity, reasons, record))

    candidates.sort(key=lambda item: item[0], reverse=True)
    matches = []
    for score, similarity, reasons, record in candidates[: max(1, limit)]:
        matches.append(
            {
                "id": record.get("id"),
                "dataset_key": record.get("dataset_key"),
                "question_text": record.get("question_text"),
                "type": record.get("type"),
                "difficulty": record.get("difficulty"),
                "expected_guide": record.get("expected_guide"),
                "mapped_skills": record.get("mapped_skills", []),
                "source_keys": record.get("source_keys", []),
                "provenance": record.get("provenance"),
                "similarity": round(similarity, 6),
                "ranking_score": round(score, 6),
                "recommendation_reason": ", ".join(reasons) if reasons else "semantic similarity match",
                "selection_pipeline": "sentence_bert_similarity" if index.get("embedding_backend") == "sentence-bert" else "lexical_similarity",
            }
        )

    return {
        "status": "matched" if matches else "no_match",
        "embedding_backend": index.get("embedding_backend"),
        "embedding_model": index.get("embedding_model"),
        "matches": matches,
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--index", required=True)
    parser.add_argument("--limit", type=int, default=1)
    args = parser.parse_args()

    index = read_index(Path(args.index))
    payload = json.load(sys.stdin)
    if not isinstance(payload, dict):
        raise SystemExit("Recommendation payload must be a JSON object.")

    print(json.dumps(recommend(index, payload, max(1, args.limit)), ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
