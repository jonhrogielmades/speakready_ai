#!/usr/bin/env python3
"""Run the local feedback scoring model and emit SpeakReady feedback JSON."""

from __future__ import annotations

import argparse
import json
import math
import re
import statistics
import sys
from typing import Any

from train_feedback_model import SCORE_FIELDS, text_features


def clamp_int(value: Any, low: float = 0.0, high: float = 100.0) -> int:
    try:
        parsed = float(value)
    except (TypeError, ValueError):
        return int(low)
    if not math.isfinite(parsed):
        return int(low)
    return int(round(max(low, min(high, parsed))))


def word_count(text: str) -> int:
    return len(re.findall(r"\b[\w']+\b", text, flags=re.UNICODE))


def normalize_text(text: Any) -> str:
    return re.sub(r"\s+", " ", str(text or "")).strip()


def excerpt(text: Any, limit: int = 160) -> str:
    normalized = normalize_text(text)
    if len(normalized) <= limit:
        return normalized
    return normalized[:limit].rsplit(" ", 1)[0].strip() or normalized[:limit].strip()


def best_quote(answer: str) -> str:
    sentences = [part.strip() for part in re.split(r"(?<=[.!?])\s+", answer) if part.strip()]
    candidates = sorted(sentences or [answer.strip()], key=lambda item: (word_count(item), len(item)), reverse=True)
    for candidate in candidates:
        if word_count(candidate) >= 3:
            return excerpt(candidate, 220)
    return excerpt(answer, 220)


def answer_tokens(text: Any) -> list[str]:
    return re.findall(r"[a-zA-Z][a-zA-Z']{1,}", str(text or "").lower())


def is_behavioral(answer: dict[str, Any]) -> bool:
    question = str(answer.get("question", ""))
    context = " ".join(
        [
            question,
            str(answer.get("expected_guide", "")),
            " ".join(str(item) for item in answer.get("mapped_skills", []) if item),
        ]
    )
    question_type = str(answer.get("question_type", "")).lower()
    experiential = re.search(
        r"\b(tell me about|describe|share|give (?:me )?an example|walk me through)\b.*\b(time|situation|experience|project|incident|challenge|mistake|conflict|case|deadline|decision|failure|success|problem|issue|achievement|change|pressure|setback)\b|\bexample of how you\b",
        question,
        re.IGNORECASE,
    )
    star_guide = re.search(r"\buse STAR\b|\bSTAR Method\b|\bsituation[, ]+task[, ]+action[, ]+(?:and )?result\b", context, re.IGNORECASE)
    return bool(experiential or star_guide or question_type == "behavioral")


def local_star_score(answer_text: str) -> int:
    text = answer_text.lower()
    parts = 0
    if re.search(r"\b(?:when|while|during|at my|in my|there was|we had|i had|challenge|problem|situation)\b", text):
        parts += 1
    if re.search(r"\b(?:my task|my goal|i needed|i had to|responsible|objective|assigned)\b", text):
        parts += 1
    if re.search(r"\b(?:i|we)\s+(?:led|built|created|resolved|solved|improved|reduced|increased|delivered|designed|implemented|organized|managed|tested|analyzed|coordinated|handled|supported|communicated|verified|planned|documented|trained|presented|mentored)\b", text):
        parts += 1
    if re.search(r"\b(?:result|outcome|impact|improved|reduced|increased|saved|resolved|completed|learned|success|percent|%)\b|\d+", text):
        parts += 1
    return parts * 25


def star_bucket(value: float) -> int:
    return min([0, 25, 50, 75, 100], key=lambda candidate: abs(candidate - value))


def predict_member(weights: dict[str, float], features: dict[str, float], scales: dict[str, float]) -> float:
    return sum(weight * (features.get(name, 0.0) / scales.get(name, 1.0)) for name, weight in weights.items())


def predict_field(field_model: dict[str, Any], features: dict[str, float]) -> int:
    fallback = float(field_model.get("fallback", 0.0))
    confidence = float(field_model.get("confidence", 1.0))
    scales = {str(name): float(value) for name, value in field_model.get("scales", {}).items() if isinstance(value, (int, float))}
    members = field_model.get("members", [])
    predictions: list[float] = []

    for member in members if isinstance(members, list) else []:
        weights = member.get("weights", {}) if isinstance(member, dict) else {}
        if isinstance(weights, dict) and weights:
            typed_weights = {str(name): float(value) for name, value in weights.items()}
            predictions.append(predict_member(typed_weights, features, scales) * 100.0)

    if not predictions:
        weights = field_model.get("weights", {})
        if isinstance(weights, dict) and weights:
            predictions.append(sum(float(weights.get(name, 0.0)) * value for name, value in features.items()) * 100.0)

    if not predictions:
        return clamp_int(fallback)

    raw = statistics.mean(predictions)
    return clamp_int(fallback + confidence * (raw - fallback))


def weighted_score(scores: dict[str, int], star_applicable: bool) -> int:
    weights = {
        "clarity_score": 0.25,
        "relevance_score": 0.35,
        "grammar_score": 0.10,
        "professionalism_score": 0.20,
        "star_method_score": 0.10,
    }
    if not star_applicable:
        weights.pop("star_method_score")
    total = sum(weights.values())
    return clamp_int(sum(scores.get(field, 0) * weight for field, weight in weights.items()) / total)


def predict_scores(model: dict[str, Any], row: dict[str, Any], star_applicable: bool) -> dict[str, int]:
    features = text_features(row)
    model_fields = model.get("models", {})
    scores: dict[str, int] = {}

    for field in SCORE_FIELDS:
        field_model = model_fields.get(field, {}) if isinstance(model_fields, dict) else {}
        scores[field] = predict_field(field_model, features) if isinstance(field_model, dict) else 0

    answer_text = str(row.get("input", {}).get("answer", ""))
    if not star_applicable:
        scores["star_method_score"] = 0
    else:
        scores["star_method_score"] = star_bucket((scores.get("star_method_score", 0) + local_star_score(answer_text)) / 2)

    scores["score"] = weighted_score(scores, star_applicable)
    return scores


def missing_criteria(answer: dict[str, Any], scores: dict[str, int]) -> list[str]:
    guide = normalize_text(answer.get("expected_guide", ""))
    question = normalize_text(answer.get("question", ""))
    source = guide or question
    if not source or (scores.get("relevance_score", 0) >= 75 and scores.get("score", 0) >= 70):
        return []
    return [excerpt(source, 180)]


def alignment_for(relevance: int, skipped: bool, too_short: bool) -> str:
    if skipped:
        return "skipped"
    if too_short:
        return "insufficient_evidence"
    if relevance >= 75:
        return "directly_addressed"
    if relevance >= 50:
        return "partially_addressed"
    return "not_addressed"


def feedback_text(question: str, quote: str, alignment: str, missing: list[str], too_short: bool) -> str:
    if too_short:
        return (
            f'The answer was too short to check your interview readiness. For the question "{question}", '
            f'the answer text was "{quote}", but it did not give enough clear detail. '
            "Next step: give a full direct answer, then add one true detail."
        )

    relation = {
        "directly_addressed": "answered the question directly",
        "partially_addressed": "answered part of the question",
        "not_addressed": "did not clearly answer the question",
    }.get(alignment, "needs more detail")
    gap = f' It still needs "{missing[0]}".' if missing else " Keep the same focus and add a clearer result."
    return (
        f'For the question "{question}", you said "{quote}". '
        f'That {relation} because it gives a saved answer detail.{gap} '
        "Next step: make your exact action and result easy to see."
    )


def fallback_answer(answer_text: str) -> str:
    quote = best_quote(answer_text)
    if not quote:
        return ""
    return f"{quote} Add the missing context, your exact action, and the result without inventing new facts."


def session_feedback(items: list[dict[str, Any]]) -> dict[str, Any]:
    if not items:
        return {
            "overall_readiness_score": 0,
            "star_method_score": 0,
            "strengths": "No answers were available for the local model to score.",
            "weaknesses": "Complete at least one answer to get a model score.",
            "improvement_suggestions": "Answer the question directly, then add one clear example and result.",
        }

    average = lambda field: clamp_int(sum(int(item.get(field, 0)) for item in items) / max(1, len(items)))
    best = max(items, key=lambda item: int(item.get("score", 0)))
    weakest = min(items, key=lambda item: int(item.get("score", 0)))
    return {
        "overall_readiness_score": average("score"),
        "star_method_score": average("star_method_score"),
        "strengths": f'The strongest saved answer included "{(best.get("evidence_quotes") or [""])[0]}".',
        "weaknesses": f'The answer for "{best_quote(str(weakest.get("question_focus", "")))}" needs clearer detail.',
        "improvement_suggestions": "For the next attempt, answer the question first, then add your action and the result.",
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--model", required=True)
    args = parser.parse_args()

    with open(args.model, "r", encoding="utf-8") as handle:
        model = json.load(handle)

    payload = json.load(sys.stdin)
    answers = payload.get("answers", [])
    items: list[dict[str, Any]] = []

    for answer in answers:
        if not isinstance(answer, dict):
            continue
        answer_text = normalize_text(answer.get("answer", ""))
        skipped = bool(answer.get("is_skipped")) or answer_text == "" or answer_text.lower() == "(skipped or no answer)"
        too_short = (not skipped) and word_count(answer_text) < 10
        question = normalize_text(answer.get("question", ""))
        quote = "" if skipped else best_quote(answer_text)
        star_applicable = is_behavioral(answer)
        scores = predict_scores(model, {"input": dict(answer, answer=answer_text)}, star_applicable)

        if skipped:
            for field in SCORE_FIELDS:
                scores[field] = 0
        elif too_short:
            for field in SCORE_FIELDS:
                scores[field] = min(scores[field], 10)

        alignment = alignment_for(scores.get("relevance_score", 0), skipped, too_short)
        gaps = [] if skipped else missing_criteria(answer, scores)
        text = (
            f'The question "{question}" was skipped, so there is no answer to check for that question. '
            "Next attempt: answer the question first, then add one true detail."
            if skipped
            else feedback_text(question, quote, alignment, gaps, too_short)
        )

        items.append(
            {
                "id": int(answer.get("id", 0) or 0),
                "score": scores.get("score", 0),
                "clarity_score": scores.get("clarity_score", 0),
                "relevance_score": scores.get("relevance_score", 0),
                "grammar_score": scores.get("grammar_score", 0),
                "professionalism_score": scores.get("professionalism_score", 0),
                "star_applicable": star_applicable,
                "star_method_score": scores.get("star_method_score", 0),
                "evidence_quotes": [] if skipped else [quote],
                "question_focus": question,
                "answer_alignment": alignment,
                "missing_criteria": gaps,
                "ai_feedback": text,
                "better_sample_answer": "" if skipped else fallback_answer(answer_text),
                "follow_up_question": f'What result or lesson would you add to strengthen your answer to "{question}"?',
                "evaluation_source": "local_trained_model",
            }
        )

    print(json.dumps({"per_question_feedback": items, "session_feedback": session_feedback(items)}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
