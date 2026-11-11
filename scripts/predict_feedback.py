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
    return re.findall(r"[^\W\d_][\w'-]{1,}", str(text or "").lower(), flags=re.UNICODE)


QUESTION_STOP_WORDS = {
    "about", "an", "and", "are", "can", "describe", "did", "do", "does", "example",
    "explain", "for", "give", "have", "how", "in", "is", "me", "of", "or", "the",
    "tell", "that", "time", "to", "was", "were", "what", "when", "where", "which",
    "why", "with", "would", "you", "your",
}


def focus_tokens(text: str) -> set[str]:
    aliases = {
        "clients": "customer", "client": "customer", "customers": "customer",
        "pay": "salary", "compensation": "salary", "salaries": "salary",
        "complaints": "complaint", "issues": "issue", "problems": "problem",
        "resolved": "resolve", "resolving": "resolve", "solutions": "solution",
        "improved": "improve", "improving": "improve", "processes": "process",
        "teams": "team", "skills": "skill", "results": "result",
    }
    return {
        aliases.get(token, token)
        for token in answer_tokens(text)
        if token not in QUESTION_STOP_WORDS and len(token) >= 3
    }


def topic_match_status(question: str, answer_text: str, kind: str, signals: dict[str, bool]) -> str:
    # A salary amount/flexibility or work arrangement can answer the intent
    # without repeating the exact words in the prompt. Otherwise a single
    # shared word only shows a possible connection, not question coverage.
    if kind in {"salary", "work_setup"}:
        return "strong" if signals["salary" if kind == "salary" else "setup"] else "unconfirmed"
    matches = focus_tokens(question) & focus_tokens(answer_text)
    if len(matches) >= 2:
        return "strong"
    return "one_term" if matches else "unconfirmed"


def question_kind(question: str, answer: dict[str, Any]) -> str:
    lowered = question.lower()
    # An experience involving payroll or a salary error is a behavioral
    # question; only a request for the candidate's own expectation is salary.
    if is_behavioral(answer) and re.search(r"\b(?:time|example|experience|issue|error|problem|case)\b|\bhow\s+did\s+you\b", lowered):
        return "behavioral"
    if re.search(
        r"\b(?:salary|pay|compensation|wage)\b.{0,70}\b(?:expect\w*|range|desired|seeking|looking for|negotiab\w*)\b"
        r"|\b(?:expect\w*|desired|seeking|looking for|range)\b.{0,70}\b(?:salary|pay|compensation|wage)\b",
        lowered,
    ):
        return "salary"
    if re.search(r"\bweakness(?:es)?\b", lowered):
        return "weakness"
    if re.search(r"\bstrengths?\b", lowered):
        return "strength"
    if re.search(r"\b(?:introduce yourself|tell me about yourself)\b", lowered):
        return "introduction"
    if re.search(r"\b(?:why.*(?:join|apply|want|interested)|what interests you)\b", lowered):
        return "motivation"
    if re.search(r"\b(?:remote|hybrid|on-?site|work setup|shifts?|schedules?|availability|available)\b", lowered):
        return "work_setup"
    if is_behavioral(answer):
        return "behavioral"
    if re.search(r"\b(?:how would you|what would you do|suppose|imagine)\b", lowered):
        return "situational"
    technical_wording = re.search(r"\b(?:explain how|describe how|how does|how would you debug)\b", lowered) \
        and not re.search(r"\bhow\s+(?:you|we|i)\s+work\b", lowered)
    if str(answer.get("question_type", "")).lower() == "technical" or technical_wording:
        return "technical"
    return "direct"


def work_setup_signal(text: str) -> bool:
    """Detect a stated availability or preference, not a past shift mention."""
    target = (
        r"(?:remote(?:ly)?|hybrid|on[ -]?site|from home|"
        r"(?:(?:the|a|any)\s+)?(?:rotating|evening|night|day|morning|weekend)\s+shifts?|"
        r"shifts|weekdays?|weekends?|(?:any|flexible|rotating|the posted|your)\s+schedule|"
        r"(?:full|part)[ -]?time|\d+\s+days?\s+(?:a|per)\s+week|"
        r"(?:morning|evening|night)\s+hours|anytime)"
    )
    patterns = [
        rf"\bI\s+(?:can|could|will|would|cannot|can't)\s+(?:work|do|take|cover|handle)\s+(?:on\s+|during\s+|for\s+)?{target}\b",
        rf"\bI\s+(?:am|am not|'m|'m not)\s+(?:available|open|flexible)\s+(?:for|to|with|on|during)?\s*{target}\b",
        rf"\bI\s+(?:prefer|need|require)\s+(?:to work\s+|a\s+|the\s+)?{target}\b",
        rf"\bmy\s+(?:availability|preference|preferred schedule)\s+(?:is|would be)\s+{target}\b",
        rf"\b{target}\s+(?:works for me|is fine for me|would work for me)\b",
    ]
    return any(re.search(pattern, text, re.IGNORECASE) for pattern in patterns)


def answer_signals(text: str) -> dict[str, bool]:
    lowered = text.lower()
    action_pattern = re.compile(
        r"\bI\s+(?:(?:personally|also|then|first|carefully|directly)\s+)?"
        r"(?:(?:have|had)\s+)?(?:led|built|wrote|made|took|gave|spoke|"
        r"checked|created|handled|resolved|solved|fixed|improved|reduced|"
        r"increased|updated|trained|explained|organized|planned|tested|"
        r"reviewed|found|identified|helped|supported|communicated|delivered|"
        r"designed|implemented|coordinated|managed|analyzed|verified|"
        r"documented|presented|mentored|worked|inspected|diagnosed|"
        r"prioritized|validated|measured|compared|escalated|prepared|"
        r"assisted|proposed|researched|configured|deployed|investigated|"
        r"monitored|reported|facilitated|maintained|migrated|automated|"
        r"optimized|launched|processed|scheduled|delegated|selected|"
        r"evaluated|gathered|contacted|collaborated|recommended|listened|"
        r"asked|shared)\b", re.IGNORECASE,
    )
    has_personal_action = False
    for match in action_pattern.finditer(text):
        # A conditional first-person verb is a plan, not past work. A negated
        # action does not match because "did not"/"never" separates I from it.
        prefix = text[max(0, text.rfind(".", 0, match.start()) + 1):match.start()]
        if not re.search(r"\b(?:if|suppose|imagine)\b", prefix, re.IGNORECASE):
            has_personal_action = True
            break

    result_pattern = re.compile(
        r"\b(?:resulted|learned|reduced|increased|improved|"
        r"resolved|completed|saved|customer (?:remained unhappy|understood|"
        r"accepted|responded))\b"
    )
    has_result = False
    for match in result_pattern.finditer(lowered):
        prefix = lowered[max(0, lowered.rfind(".", 0, match.start()) + 1):match.start()]
        if not re.search(r"\b(?:if|suppose|imagine|would|could|should|might|will)\b", prefix) \
                and not re.search(r"\bto\s+$", prefix):
            has_result = True
            break
    for match in re.finditer(r"\b(?:result|outcome)\s+(?:was|were|is|became|showed|meant|included|led to)\s+([^.!?;]{2,100})", lowered):
        value = match.group(1).strip()
        prefix = lowered[max(0, lowered.rfind(".", 0, match.start()) + 1):match.start()]
        if not re.search(r"\b(?:if|suppose|imagine|would|could|should|might|will)\b", prefix) \
                and not re.match(r"(?:still\s+)?(?:unknown|unclear|pending|not\s+(?:known|clear|measured)|a\s+(?:goal|plan)|to\b)\b", value):
            has_result = True

    has_amount = bool(re.search(
        r"(?:[$₱€£]\s*\d|\b(?:php|usd)\s*\d|\d[\d,.]*\s*(?:k|pesos?|dollars?|per month|monthly|annually|per year))",
        lowered,
    ))
    has_pay_context = bool(re.search(r"\b(?:salary|pay|compensation|wage|package)\b", lowered))
    expectation_cue = bool(re.search(
        r"\b(?:expect\w*|seeking|looking for|aiming for|desired|"
        r"would like|open to|negotiab\w*|flexib\w*)\b", lowered,
    ))
    flexibility = bool(re.search(r"\b(?:negotiab\w*|flexib\w*|open to (?:discuss|negotiat))\b", lowered))
    payroll_history = bool(re.search(r"\b(?:payroll|invoice|reimburse\w*|budget|past salary|current salary|previous salary|salary error)\b", lowered))
    salary_expectation = (
        (expectation_cue and (has_amount or has_pay_context))
        or (flexibility and word_count(text) <= 4
            and not re.search(r"\b(?:shifts?|schedules?|hours?|remote|hybrid|onsite)\b", lowered))
        or (has_amount and word_count(text) <= 8 and not payroll_history)
    )
    return {
        "personal_action": has_personal_action,
        "result": has_result,
        "reason": bool(re.search(r"\b(?:because|so that|in order to|interested|motivated|value)\b", lowered)),
        "salary": salary_expectation,
        "setup": work_setup_signal(text),
        "past_example": bool(re.search(r"\b(?:when|during|at my|in my|last|previously|once|a time)\b", lowered)),
    }


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
    # Follow the wording the candidate saw before a coarse stored type or
    # broad guide. A technical explanation is not a past-event STAR prompt.
    descriptive_technical = re.search(
        r"\b(?:describe|explain|walk me through)\s+(?:how|what)\b.*\b(?:work|works|operates|functions)\b",
        question, re.IGNORECASE,
    ) and not re.search(r"\bhow\s+(?:you|we|i)\s+work\b", question, re.IGNORECASE)
    if descriptive_technical or question_type == "technical":
        return False
    if re.search(r"\b(?:describe|explain)\s+how\s+(?:you|we|i)\s+work\b", question, re.IGNORECASE):
        return False
    experiential = re.search(
        r"\b(tell me about|describe|share|give (?:me )?an example|walk me through)\b.*\b(time|situation|experience|project|incident|challenge|mistake|conflict|case|deadline|decision|failure|success|problem|issue|achievement|change|pressure|setback)\b|\bexample of how you\b",
        question,
        re.IGNORECASE,
    )
    past_handling = re.search(
        r"\bhow\s+did\s+you\s+(?:handle|resolve|solve|manage|address|fix|respond to|deal with|"
        r"prioritize|overcome|improve|investigate)\b",
        question, re.IGNORECASE,
    )
    star_guide = re.search(r"\buse STAR\b|\bSTAR Method\b|\bsituation[, ]+task[, ]+action[, ]+(?:and )?result\b", context, re.IGNORECASE)
    return bool(experiential or past_handling or star_guide)


def local_star_score(answer_text: str) -> int:
    text = answer_text.lower()
    signals = answer_signals(answer_text)
    parts = 0
    if re.search(r"\b(?:when|while|during|at my|in my|there was|we had|i had|challenge|problem|situation)\b", text):
        parts += 1
    if re.search(r"\b(?:my task|my goal|i needed|i had to|responsible|objective|assigned)\b", text):
        parts += 1
    if signals["personal_action"]:
        parts += 1
    if signals["result"]:
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


def missing_criteria(answer: dict[str, Any], scores: dict[str, int], signals: dict[str, bool]) -> list[str]:
    """Return only a demonstrably missing, verbatim part of the question guide."""
    guide = normalize_text(answer.get("expected_guide", ""))
    answer_text = normalize_text(answer.get("answer", ""))
    if not guide:
        return []

    chunks = [part.strip() for part in re.split(r"[.;\n]+|\s+and\s+", guide, flags=re.IGNORECASE) if part.strip()]
    gaps: list[str] = []
    for chunk in chunks:
        lowered = chunk.lower()
        absent = False
        if re.search(r"\b(?:star|situation|task|action|result)\b", lowered):
            if "star" in lowered or "situation" in lowered:
                absent = local_star_score(answer_text) < 100
            elif "result" in lowered:
                absent = not signals["result"]
            elif "action" in lowered:
                absent = not signals["personal_action"]
        elif re.search(r"\b(?:result|outcome|lesson)\b", lowered):
            absent = not signals["result"]
        elif re.search(r"\b(?:personal|your own|what you did)\b", lowered):
            absent = not signals["personal_action"]
        # Other guide clauses can be paraphrased or optional. Word overlap
        # cannot establish that one is genuinely missing from the answer.

        if absent and len(chunk) <= 180 and word_count(chunk) <= 12 and len(focus_tokens(chunk)) >= 1:
            gaps.append(chunk)
        if len(gaps) >= 2:
            break
    return gaps


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


def question_assessment(question: str, answer_text: str, kind: str, signals: dict[str, bool], missing: list[str]) -> dict[str, str]:
    question_terms = focus_tokens(question)
    answer_terms = focus_tokens(answer_text)
    matched = sorted(question_terms & answer_terms)
    focus = ", ".join(matched[:2])
    topic_status = topic_match_status(question, answer_text, kind, signals)

    if kind == "behavioral":
        covered = "a personal action and a stated result" if signals["personal_action"] and signals["result"] else (
            "a personal action" if signals["personal_action"] else "an answer detail, but no clearly detected personal action"
        )
        gap = "the final result or lesson" if not signals["result"] else (
            "the action you personally took" if not signals["personal_action"] else "why that action addressed the problem"
        )
        follow_up = "What happened after your action, and what did you learn?" if not signals["result"] else (
            "Which step did you personally take in that situation?" if not signals["personal_action"] else
            "Why did you choose that action, and how did you check the result?"
        )
    elif kind == "salary":
        covered = "a pay expectation or flexibility" if signals["salary"] else "an answer without a clear pay expectation"
        gap = "a truthful range, amount, or clear statement of flexibility" if not signals["salary"] else "the basis for that expectation, if you can explain it"
        follow_up = "What range or flexibility can you genuinely offer for this role?" if not signals["salary"] else "What factors shaped the expectation you stated?"
    elif kind == "work_setup":
        covered = "a specific work arrangement" if signals["setup"] else "a response without a clear work arrangement"
        gap = "the arrangement or schedule you can actually maintain" if not signals["setup"] else "any real condition that affects your availability"
        follow_up = "Which arrangement or hours can you reliably work?" if not signals["setup"] else "Is there any real limit on the arrangement you described?"
    elif kind == "motivation":
        covered = "a reason for your interest" if signals["reason"] else "an interest in the role without a stated reason"
        gap = "why this particular role or organization interests you" if not signals["reason"] else "a link between that reason and one job responsibility"
        follow_up = "Why does this specific role interest you?" if not signals["reason"] else "Which responsibility connects most clearly to your stated reason?"
    elif kind == "weakness":
        covered = "an area for improvement" if re.search(r"\b(?:weakness|improv|develop|practice|working on)\b", answer_text, re.IGNORECASE) else "an answer without a clear development area"
        gap = "a real improvement habit and a sign of progress"
        follow_up = "What are you doing to improve that area, and how can you tell it is working?"
    elif kind == "strength":
        covered = "a stated strength" if re.search(r"\b(?:strength|skilled|good at|experience|able to)\b", answer_text, re.IGNORECASE) else "an answer without a clearly named strength"
        gap = "one true example that shows the strength in use"
        follow_up = "Which real example best shows the strength you named?"
    elif kind == "introduction":
        covered = "a background detail" if re.search(r"\b(?:experience|worked|working|graduate|student|background|skill)\b", answer_text, re.IGNORECASE) else "an introduction without a clear background detail"
        gap = "a true background detail connected to this role"
        follow_up = "Which part of your background is most relevant to this role?"
    elif kind == "situational":
        covered = "a proposed first step" if re.search(r"\b(?:first|would|start|check|ask|assess)\b", answer_text, re.IGNORECASE) else "a response without a clear first step"
        gap = "the first action you would take and how you would check its effect"
        follow_up = "What would you do first, and how would you know it worked?"
    elif kind == "technical":
        covered = "a technical explanation" if signals["reason"] or focus else "an answer whose technical link is unclear"
        gap = "why the proposed approach works or how you would test it"
        follow_up = "How would you verify that your approach works?"
    else:
        covered = "a point connected to " + focus if focus else "a response whose link to the prompt is unclear"
        gap = "one reason or example that directly supports the point asked"
        follow_up = "What true reason or example would make your answer more direct?"

    if missing:
        gap = 'the guide point "' + missing[0] + '"'
    if topic_status != "strong" and kind == "direct":
        covered = "an answer detail"
    if topic_status != "strong" and kind == "technical":
        covered = "an explanation detail"
    return {"covered": covered, "gap": gap, "follow_up": follow_up,
            "topic_status": topic_status, "matched_term": matched[0] if matched else ""}


def feedback_text(question: str, quote: str, alignment: str, assessment: dict[str, str], too_short: bool) -> str:
    if too_short:
        return (
            f'For "{question}", you wrote "{quote}". That is too short to judge how fully it answers the question. '
            f'Next step: state your main point, then add {assessment["gap"]} if it is true.'
        )

    if assessment["topic_status"] != "strong" and alignment != "not_addressed":
        uncertainty = (
            f'It shares only "{assessment["matched_term"]}" with the question; that alone cannot confirm the answer covers the prompt.'
            if assessment["topic_status"] == "one_term"
            else "The local check cannot confirm from these words how the answer connects to the question."
        )
        return (
            f'For "{question}", you wrote "{quote}". '
            f'This gives {assessment["covered"]}. {uncertainty} '
            f'Next step: explain {assessment["gap"]} using only details that are true for you.'
        )

    relation = {
        "directly_addressed": "addresses the question directly",
        "partially_addressed": "addresses part of the question",
        "not_addressed": "does not yet clearly address the question",
    }.get(alignment, "needs more detail")
    return (
        f'For "{question}", you wrote "{quote}". '
        f'This {relation}: it gives {assessment["covered"]}. '
        f'Next step: explain {assessment["gap"]} using only details that are true for you.'
    )


def coaching_for(question: str, quote: str, alignment: str, assessment: dict[str, str], skipped: bool, too_short: bool) -> dict[str, Any]:
    question_label = excerpt(question, 140) or "this question"
    quote_label = excerpt(quote, 100) or question_label
    missing_label = assessment["gap"]

    if skipped:
        return {
            "keep": f'For "{question_label}", the saved record clearly shows the question was skipped.',
            "improve": f'For "{question_label}", submit a complete answer with one true detail.',
            "impact": f'Skipping "{question_label}" gives the interviewer no answer detail to judge, so the next attempt needs a clear response.',
            "next_try": f'Answer "{question_label}" first, then add one specific example or result.',
            "next_attempt_steps": [
                f'Start with a direct answer to "{question_label}".',
                f'Add one true detail that fits "{question_label}".',
                f'Close with the result, effect, or lesson for "{question_label}".',
            ],
            "success_check": f'The retry works when it gives a complete answer to "{question_label}" with one true detail.',
        }

    if too_short:
        return {
            "keep": f'For "{question_label}", keep the answer idea from "{quote_label}" if it is true.',
            "improve": f'For "{question_label}", expand "{quote_label}" with {missing_label}.',
            "impact": f'The short answer "{quote_label}" is not enough for the interviewer to judge the answer to "{question_label}" well.',
            "next_try": f'Turn "{quote_label}" into a full answer to "{question_label}".',
            "next_attempt_steps": [
                f'Start with a full sentence that answers "{question_label}".',
                f'Explain {missing_label} if it is true for you.',
                f'Check that the expanded answer responds to "{question_label}".',
            ],
            "success_check": f'The retry works when "{quote_label}" is developed with {missing_label}.',
        }

    if alignment == "directly_addressed":
        keep = f'Keep "{quote_label}" as the evidence for {assessment["covered"]}.'
        improve = f'Explain {missing_label} for "{question_label}".'
    elif alignment == "partially_addressed":
        keep = f'Keep "{quote_label}" because it gives {assessment["covered"]}.'
        improve = f'Cover {missing_label} so "{question_label}" is answered more fully.'
    else:
        keep = f'Use "{quote_label}" only if it truly supports "{question_label}".'
        improve = f'Make the answer connect directly to "{question_label}" and explain {missing_label}.'

    return {
        "keep": keep,
        "improve": improve,
        "impact": f'The interviewer can judge "{question_label}" better when "{quote_label}" is linked to {missing_label}.',
        "next_try": f'Rewrite the answer so "{quote_label}" clearly supports "{question_label}".',
        "next_attempt_steps": [
            f'Open with a direct answer to "{question_label}".',
            f'Use "{quote_label}" as the proof point only if it is true.',
            f'Explain {missing_label} before ending the answer.',
        ],
        "success_check": f'The retry works when "{question_label}" is answered directly and {missing_label} is clear.',
    }


def fallback_answer(answer_text: str) -> str:
    # Repeating an answer unchanged would mislabel it as an improvement. The
    # PHP evidence-based revision builder can make a safer grounded revision.
    return ""


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
    answered = [item for item in items if item.get("evidence_quotes")]
    if not answered:
        return {
            "overall_readiness_score": 0,
            "star_method_score": 0,
            "strengths": "No submitted answer detail is available to describe a strength.",
            "weaknesses": "The saved questions were skipped.",
            "improvement_suggestions": "Choose one skipped question and give a direct, truthful answer.",
        }
    best = max(answered, key=lambda item: int(item.get("score", 0)))
    weakest = min(items, key=lambda item: int(item.get("score", 0)))
    weakest_coaching = weakest.get("coaching", {})
    return {
        "overall_readiness_score": average("score"),
        "star_method_score": average("star_method_score"),
        "strengths": f'The saved answer for "{best.get("question_focus", "this question")}" included "{(best.get("evidence_quotes") or [""])[0]}". This is the detail available to review.',
        "weaknesses": str(weakest_coaching.get("improve", "")),
        "improvement_suggestions": str(weakest_coaching.get("next_try", "")),
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
        kind = question_kind(question, answer)
        signals = answer_signals(answer_text)
        scores = predict_scores(model, {"input": dict(answer, answer=answer_text)}, star_applicable)

        if skipped:
            for field in SCORE_FIELDS:
                scores[field] = 0
        elif too_short:
            for field in SCORE_FIELDS:
                scores[field] = min(scores[field], 10)
        else:
            # The trained score is only one signal. A fluent but unrelated
            # answer must not become "directly addressed" merely because the
            # model assigned a high score or the answer supplied a long quote.
            topic_status = topic_match_status(question, answer_text, kind, signals)
            missing_primary = (
                (kind == "salary" and not signals["salary"])
                or (kind == "work_setup" and not signals["setup"])
            )
            if missing_primary:
                scores["relevance_score"] = min(scores["relevance_score"], 49)
            elif topic_status != "strong" or (kind == "behavioral" and (not signals["personal_action"] or not signals["result"])):
                scores["relevance_score"] = min(scores["relevance_score"], 74)
            scores["score"] = weighted_score(scores, star_applicable)

        gaps = [] if skipped else missing_criteria(dict(answer, answer=answer_text), scores, signals)
        if gaps and not skipped and not too_short and scores["relevance_score"] >= 75:
            scores["relevance_score"] = 74
            scores["score"] = weighted_score(scores, star_applicable)
        alignment = alignment_for(scores.get("relevance_score", 0), skipped, too_short)
        assessment = question_assessment(question, answer_text, kind, signals, gaps)
        text = (
            f'The question "{question}" was skipped, so there is no answer to check for that question. '
            "Next attempt: answer the question first, then add one true detail."
            if skipped
            else feedback_text(question, quote, alignment, assessment, too_short)
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
                "follow_up_question": f'For "{question}", {assessment["follow_up"]}',
                "coaching": coaching_for(question, quote, alignment, assessment, skipped, too_short),
                "topic_match_status": assessment["topic_status"],
                "evaluation_source": "local_trained_model",
            }
        )

    print(json.dumps({"per_question_feedback": items, "session_feedback": session_feedback(items)}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
