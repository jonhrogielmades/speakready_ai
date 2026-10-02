#!/usr/bin/env python3
"""Generate a reproducible unique feedback training dataset for SpeakReady."""

from __future__ import annotations

import csv
import hashlib
import json
import random
from collections import Counter, defaultdict
from datetime import datetime, timezone, timedelta
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "storage" / "app" / "private" / "datasets" / "normalized" / "training"
CSV_PATH = OUT_DIR / "feedback_train.csv"
JSONL_PATH = OUT_DIR / "feedback_train.jsonl"
SAMPLE_2000_PATH = OUT_DIR / "feedback_train_sample_2000.jsonl"
SAMPLE_200_PATH = OUT_DIR / "feedback_train_sample_200.jsonl"
CSV_MANIFEST_PATH = OUT_DIR / "feedback_train_csv_manifest.json"
JSONL_MANIFEST_PATH = OUT_DIR / "feedback_train_manifest.json"
VALIDATION_PATH = OUT_DIR / "feedback_train_validation_report.json"

SOURCE = "speakready_internal_training_bank_v3_unique"
DATA_ORIGIN = "simulated_bootstrap_unique"
LABEL_POLICY = "rubric_scored_simulated_interview_answer"
CREATED_AT = datetime(2026, 10, 2, tzinfo=timezone(timedelta(hours=8))).isoformat()
ROWS_PER_ROLE = 2000
SEED = 20261002

CSV_FIELDS = [
    "schema_version",
    "source",
    "record_id",
    "audit_status",
    "data_origin",
    "label_policy",
    "scoring_confidence",
    "created_at",
    "question",
    "question_type",
    "expected_guide",
    "mapped_skills",
    "category",
    "target_position",
    "difficulty",
    "interview_focus",
    "answer",
    "response_mode",
    "voice_duration",
    "wpm",
    "filler_words_count",
    "pause_count",
    "score",
    "clarity_score",
    "relevance_score",
    "grammar_score",
    "professionalism_score",
    "star_method_score",
    "ai_feedback",
]

ROLES: list[dict[str, Any]] = [
    {
        "role": "Customer Service Representative",
        "skills": ["Communication", "Customer Service", "Problem Solving"],
        "settings": ["billing queue", "account update desk", "refund review lane", "chat support pod"],
        "issues": ["missing account detail", "delayed refund explanation", "repeat billing question", "policy misunderstanding"],
        "tools": ["CRM notes", "billing history", "knowledge base", "callback tracker"],
        "stakeholders": ["customer", "team lead", "billing specialist", "quality analyst"],
    },
    {
        "role": "Call Center Agent",
        "skills": ["Call Handling", "Listening", "De-escalation"],
        "settings": ["inbound queue", "peak call block", "after-call work review", "callback campaign"],
        "issues": ["upset caller", "long hold time", "unclear request", "repeat transfer"],
        "tools": ["call script", "disposition codes", "ticket log", "call recording notes"],
        "stakeholders": ["caller", "floor support", "workforce lead", "supervisor"],
    },
    {
        "role": "Customer Service Agent",
        "skills": ["Service Recovery", "Documentation", "Empathy"],
        "settings": ["email support queue", "live chat shift", "returns desk", "service recovery list"],
        "issues": ["late delivery complaint", "unclear warranty request", "duplicate ticket", "missing order update"],
        "tools": ["case history", "order tracker", "reply template", "escalation sheet"],
        "stakeholders": ["customer", "warehouse contact", "service lead", "account owner"],
    },
    {
        "role": "Administrative Assistant",
        "skills": ["Organization", "Scheduling", "Document Control"],
        "settings": ["front office calendar", "records clean-up", "meeting room booking", "vendor file review"],
        "issues": ["conflicting schedules", "missing attachment", "late document routing", "unclear approval chain"],
        "tools": ["shared calendar", "tracking spreadsheet", "document folder", "email rules"],
        "stakeholders": ["manager", "vendor", "department assistant", "finance contact"],
    },
    {
        "role": "Executive Assistant",
        "skills": ["Prioritization", "Confidentiality", "Executive Communication"],
        "settings": ["executive calendar", "board packet prep", "travel change desk", "leadership meeting cycle"],
        "issues": ["double-booked meeting", "sensitive itinerary change", "late briefing note", "urgent stakeholder request"],
        "tools": ["priority matrix", "briefing tracker", "travel portal", "calendar hold"],
        "stakeholders": ["executive", "board coordinator", "client contact", "chief of staff"],
    },
    {
        "role": "Receptionist",
        "skills": ["Front Desk Service", "Screening", "Coordination"],
        "settings": ["front desk", "visitor check-in", "phone switchboard", "appointment lobby"],
        "issues": ["walk-in without appointment", "late visitor list", "busy phone period", "unclear delivery"],
        "tools": ["visitor log", "phone directory", "appointment calendar", "handoff note"],
        "stakeholders": ["visitor", "security guard", "department contact", "caller"],
    },
    {
        "role": "Office Manager",
        "skills": ["Operations", "Vendor Management", "Team Coordination"],
        "settings": ["office supplies cycle", "facility request board", "onboarding checklist", "monthly operations review"],
        "issues": ["vendor delay", "supply shortage", "facility request backlog", "unclear office process"],
        "tools": ["inventory tracker", "vendor scorecard", "request log", "checklist"],
        "stakeholders": ["staff member", "vendor", "building contact", "department head"],
    },
    {
        "role": "Sales Manager",
        "skills": ["Coaching", "Forecasting", "Pipeline Management"],
        "settings": ["pipeline review", "territory planning", "weekly coaching block", "proposal follow-up cycle"],
        "issues": ["stalled opportunity", "missed forecast signal", "low conversion segment", "unclear handoff"],
        "tools": ["CRM pipeline", "call notes", "forecast sheet", "deal review template"],
        "stakeholders": ["sales representative", "prospect", "regional lead", "customer sponsor"],
    },
    {
        "role": "Marketing Associate",
        "skills": ["Campaign Execution", "Content Coordination", "Reporting"],
        "settings": ["campaign calendar", "content handoff", "event promotion sprint", "weekly performance review"],
        "issues": ["late creative asset", "low email engagement", "unclear campaign brief", "missed tracking tag"],
        "tools": ["content calendar", "email platform", "UTM tracker", "asset checklist"],
        "stakeholders": ["designer", "sales contact", "campaign owner", "vendor"],
    },
    {
        "role": "Digital Marketing Analyst",
        "skills": ["Analytics", "Experimentation", "Dashboarding"],
        "settings": ["paid search review", "conversion dashboard", "A/B test readout", "traffic quality check"],
        "issues": ["tracking mismatch", "low conversion rate", "rising cost per lead", "unclear attribution"],
        "tools": ["analytics dashboard", "ad platform report", "tag manager", "spreadsheet model"],
        "stakeholders": ["campaign manager", "paid media lead", "web developer", "sales operations"],
    },
    {
        "role": "Nurse",
        "skills": ["Patient Care", "Prioritization", "Clinical Communication"],
        "settings": ["shift handoff", "patient discharge prep", "medication reconciliation support", "triage desk"],
        "issues": ["conflicting patient needs", "late lab update", "family question", "handoff detail gap"],
        "tools": ["care notes", "handoff sheet", "patient chart", "SBAR format"],
        "stakeholders": ["patient", "charge nurse", "physician", "family member"],
    },
    {
        "role": "Medical Assistant",
        "skills": ["Patient Intake", "Accuracy", "Clinic Coordination"],
        "settings": ["clinic intake", "vitals station", "appointment rooming", "lab follow-up list"],
        "issues": ["missing intake form", "late patient flow", "unclear follow-up instruction", "duplicate chart detail"],
        "tools": ["EHR checklist", "rooming notes", "appointment schedule", "lab tracker"],
        "stakeholders": ["patient", "provider", "front desk", "clinic lead"],
    },
    {
        "role": "Home Health Aide",
        "skills": ["Care Support", "Reliability", "Observation"],
        "settings": ["morning care visit", "mobility support routine", "meal prep plan", "home safety check"],
        "issues": ["changed client routine", "missed supply item", "mobility concern", "family update request"],
        "tools": ["care plan", "visit notes", "safety checklist", "family message log"],
        "stakeholders": ["client", "family member", "care coordinator", "nurse supervisor"],
    },
    {
        "role": "Software Engineer",
        "skills": ["Software Design", "Debugging", "Collaboration"],
        "settings": ["release sprint", "incident review", "feature build", "code review cycle"],
        "issues": ["production bug", "unclear requirement", "slow endpoint", "integration failure"],
        "tools": ["logs", "unit tests", "pull request", "feature flag"],
        "stakeholders": ["product manager", "designer", "QA engineer", "team lead"],
    },
    {
        "role": "Web Developer",
        "skills": ["Frontend Development", "Accessibility", "Testing"],
        "settings": ["landing page refresh", "checkout flow update", "responsive layout pass", "CMS migration"],
        "issues": ["mobile layout issue", "slow page load", "broken form validation", "accessibility gap"],
        "tools": ["browser dev tools", "component library", "Lighthouse report", "CSS audit"],
        "stakeholders": ["designer", "content owner", "QA tester", "marketing lead"],
    },
    {
        "role": "Technical Support Engineer",
        "skills": ["Troubleshooting", "Customer Communication", "Root Cause Analysis"],
        "settings": ["support escalation", "customer call", "bug triage", "knowledge base update"],
        "issues": ["intermittent outage", "configuration error", "unclear error message", "integration issue"],
        "tools": ["logs", "diagnostic script", "support ticket", "runbook"],
        "stakeholders": ["customer admin", "engineering contact", "support lead", "account manager"],
    },
    {
        "role": "Data Analyst",
        "skills": ["Data Cleaning", "Analysis", "Insight Communication"],
        "settings": ["monthly reporting cycle", "dashboard refresh", "data quality review", "ad hoc analysis request"],
        "issues": ["missing source field", "duplicate rows", "metric definition gap", "unexpected trend"],
        "tools": ["SQL query", "spreadsheet model", "dashboard", "data dictionary"],
        "stakeholders": ["business owner", "data engineer", "manager", "operations lead"],
    },
    {
        "role": "Business Analyst",
        "skills": ["Requirements Analysis", "Process Mapping", "Stakeholder Management"],
        "settings": ["requirements workshop", "process review", "system change request", "UAT planning"],
        "issues": ["conflicting requirements", "unclear acceptance criteria", "manual process bottleneck", "scope change"],
        "tools": ["process map", "requirements log", "UAT script", "decision register"],
        "stakeholders": ["product owner", "operations manager", "developer", "end user"],
    },
    {
        "role": "QA Analyst",
        "skills": ["Test Planning", "Defect Analysis", "Quality Reporting"],
        "settings": ["regression cycle", "release validation", "defect triage", "test case review"],
        "issues": ["flaky test", "unclear expected result", "missed edge case", "late build change"],
        "tools": ["test case suite", "bug tracker", "test data sheet", "automation report"],
        "stakeholders": ["developer", "product owner", "release manager", "support analyst"],
    },
    {
        "role": "Operations Manager",
        "skills": ["Process Improvement", "People Management", "Execution"],
        "settings": ["daily operations review", "staffing plan", "service level review", "workflow redesign"],
        "issues": ["missed SLA", "handoff delay", "capacity gap", "unclear ownership"],
        "tools": ["operations dashboard", "RACI chart", "shift plan", "process tracker"],
        "stakeholders": ["team lead", "frontline employee", "client contact", "senior manager"],
    },
]

QUESTION_PATTERNS = [
    ("Behavioral", "Tell me about a time you handled {issue} in a {setting} as a {role}. What did you do, and what changed afterward?"),
    ("Situational", "If you noticed {issue} during a {setting}, how would you decide what to do first as a {role}?"),
    ("Problem Solving", "Walk me through how you would investigate {issue} using {tool} in the {role} role."),
    ("Communication", "Describe how you would explain {issue} to a {stakeholder} while keeping trust as a {role}."),
    ("Prioritization", "Give an example of how you balanced {issue} with another urgent request in a {setting}."),
    ("Technical", "What steps would you take with {tool} when {issue} affects the work of a {role}?"),
    ("Conflict", "Tell me about a time you worked through disagreement with a {stakeholder} about {issue}."),
    ("Learning", "Describe a skill you improved after dealing with {issue} in a {setting}."),
    ("Ownership", "Share a specific example where you took ownership of {issue} before it became a bigger problem."),
    ("Role Fit", "Why does your experience with {issue} prepare you for the {role} position?"),
]

GUIDES = {
    "Behavioral": "Use STAR: situation, task, action, and result. Include ownership and a measurable or observable outcome.",
    "Situational": "Explain the first step, the reasoning, the people involved, and how success would be checked.",
    "Problem Solving": "Show the diagnostic path, evidence used, decision made, and result or expected result.",
    "Communication": "Use clear language, audience awareness, empathy, and a next step.",
    "Prioritization": "Name the priorities, tradeoff, communication step, and result.",
    "Technical": "Use role-relevant tools or process details without overclaiming.",
    "Conflict": "Show listening, facts, shared goal, action, and outcome.",
    "Learning": "Name the gap, practice action, feedback used, and improvement.",
    "Ownership": "Show early action, accountability, follow-through, and impact.",
    "Role Fit": "Connect past work to the role, skills, and practical contribution.",
}

DIFFICULTIES = ["Easy", "Medium", "Hard"]
FOCUSES = ["role fit", "communication", "problem solving", "teamwork", "customer impact", "technical accuracy", "ownership"]
MONTHS = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"]
WORK_EVENTS = ["handoff", "review", "pilot", "cleanup", "rollout", "audit", "shift", "planning block", "follow-up", "training week"]
CONSTRAINTS = ["same-day deadline", "short-staffed shift", "new process", "unclear notes", "busy queue", "late update", "tight approval window"]
IMPROVEMENTS = ["reduced rework", "shortened the handoff", "improved follow-up", "raised completion", "lowered repeat questions", "made the process easier to audit"]


def choose(items: list[str], index: int, salt: int = 0) -> str:
    return items[(index + salt) % len(items)]


def clamp(value: int, low: int = 0, high: int = 100) -> int:
    return max(low, min(high, value))


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def tier_for(index: int) -> str:
    cycle = index % 20
    if cycle in {0, 1, 2, 3, 4}:
        return "excellent"
    if cycle in {5, 6, 7, 8, 9, 10, 11}:
        return "strong"
    if cycle in {12, 13, 14, 15, 16}:
        return "adequate"
    if cycle in {17, 18}:
        return "weak"
    return "poor"


def score_bundle(tier: str, row_index: int, question_type: str) -> dict[str, int]:
    jitter = (row_index * 7) % 5 - 2
    base_ranges = {
        "excellent": (91, 97),
        "strong": (80, 88),
        "adequate": (66, 77),
        "weak": (48, 62),
        "poor": (28, 44),
    }
    low, high = base_ranges[tier]
    span = high - low + 1
    base = low + ((row_index * 13) % span)
    star = {
        "excellent": 100,
        "strong": 75 if row_index % 4 else 100,
        "adequate": 50 if row_index % 3 else 75,
        "weak": 25 if row_index % 2 else 50,
        "poor": 0 if row_index % 3 else 25,
    }[tier]
    if question_type in {"Behavioral", "Ownership", "Conflict"} and tier in {"excellent", "strong"}:
        star = max(star, 75)
    return {
        "score": clamp(base),
        "clarity_score": clamp(base + jitter + (2 if tier in {"excellent", "strong"} else -2)),
        "relevance_score": clamp(base + 2 + ((row_index * 3) % 5 - 2)),
        "grammar_score": clamp(base + (3 if tier != "poor" else -5) + ((row_index * 5) % 5 - 2)),
        "professionalism_score": clamp(base + (4 if tier in {"excellent", "strong"} else -3) + jitter),
        "star_method_score": clamp(star),
    }


def delivery_metrics(tier: str, row_index: int) -> dict[str, int | str]:
    response_mode = "voice" if row_index % 3 != 0 else "text"
    if tier == "excellent":
        return {"response_mode": response_mode, "voice_duration": 120 + row_index % 76, "wpm": 132 + row_index % 35, "filler_words_count": row_index % 3, "pause_count": 1 + row_index % 4}
    if tier == "strong":
        return {"response_mode": response_mode, "voice_duration": 95 + row_index % 70, "wpm": 128 + row_index % 45, "filler_words_count": 1 + row_index % 5, "pause_count": 2 + row_index % 6}
    if tier == "adequate":
        return {"response_mode": response_mode, "voice_duration": 65 + row_index % 60, "wpm": 115 + row_index % 55, "filler_words_count": 3 + row_index % 8, "pause_count": 4 + row_index % 8}
    if tier == "weak":
        return {"response_mode": response_mode, "voice_duration": 35 + row_index % 50, "wpm": 95 + row_index % 75, "filler_words_count": 6 + row_index % 12, "pause_count": 7 + row_index % 10}
    return {"response_mode": response_mode, "voice_duration": 20 + row_index % 35, "wpm": 80 + row_index % 95, "filler_words_count": 8 + row_index % 18, "pause_count": 8 + row_index % 14}


def answer_text(profile: dict[str, Any], row_index: int, tier: str, question_type: str, issue: str, setting: str, tool: str, stakeholder: str) -> str:
    role = profile["role"]
    month = choose(MONTHS, row_index, 1)
    event = choose(WORK_EVENTS, row_index, 3)
    constraint = choose(CONSTRAINTS, row_index, 5)
    improvement = choose(IMPROVEMENTS, row_index, 7)
    before = 8 + (row_index * 11) % 43
    after = max(1, before - (2 + row_index % 11))
    percent = 5 + (row_index * 17) % 38
    people = 2 + row_index % 9
    detail = f"{month.lower()} {event} with {people} people"

    if tier == "excellent":
        return (
            f"During a {detail}, I handled {issue} in the {setting} while working around a {constraint}. "
            f"My responsibility as the {role} was to keep the work moving, protect accuracy, and keep the {stakeholder} informed. "
            f"I checked {tool}, confirmed the facts with the right person, explained the tradeoff in plain language, and documented the next action before closing the loop. "
            f"The result was that we moved the backlog from {before} items to {after}, {improvement} by {percent}%, and avoided the same problem on the next cycle."
        )
    if tier == "strong":
        return (
            f"In a {month.lower()} {event}, I dealt with {issue} while supporting the {setting}. "
            f"I used {tool}, asked the {stakeholder} for the missing context, and set a clear next step so the team knew what I owned. "
            f"We moved the work from {before} open items to {after}, with about a {percent}% improvement, and I learned to flag similar risks earlier for the {role} position."
        )
    if tier == "adequate":
        return (
            f"I had a situation during a {detail} where {issue} slowed the work in the {setting}. "
            f"I checked {tool}, talked with the {stakeholder}, and tried to organize the next steps. "
            f"It helped the task move forward from about {before} open items to {after}, but I would make the {percent}% impact clearer next time."
        )
    if tier == "weak":
        return (
            f"I remember a {detail} where there was {issue} in the {setting}. "
            f"I basically tried to use {tool} and asked the {stakeholder} what to do. "
            f"It worked out okay after {after} of about {before} items were checked, but I did not explain whether the {percent}% change came from my work."
        )
    return (
        f"I had {issue} once in the {setting} during a {detail}. "
        f"I think I used {tool} and told a {stakeholder}. "
        f"It was fine, I guess, after around {after} of {before} items and maybe {percent}%, and I can learn the rest for the {role} job."
    )


def feedback_text(role: str, tier: str, question_type: str) -> str:
    if tier == "excellent":
        return f"Excellent {role} answer. It gives context, responsibility, action, and result, and it directly answers the {question_type.lower()} question with measurable evidence."
    if tier == "strong":
        return f"Strong {role} answer. It is relevant and organized, but it would be stronger with one sharper metric or a clearer final result."
    if tier == "adequate":
        return f"Usable {role} answer. It answers the question, but the role ownership and outcome need more specific evidence."
    if tier == "weak":
        return f"Weak {role} answer. It mentions the situation, but it is vague about personal action, evidence, and result."
    return f"Poor {role} answer. It is too short and uncertain to support reliable scoring. Add a real example, clear action, and outcome."


def build_record(profile: dict[str, Any], role_index: int, offset: int, global_index: int) -> tuple[dict[str, Any], dict[str, Any]]:
    rng_index = global_index + role_index * 97
    issue = choose(profile["issues"], rng_index, role_index)
    setting = choose(profile["settings"], rng_index, role_index * 2)
    tool = choose(profile["tools"], rng_index, role_index * 3)
    stakeholder = choose(profile["stakeholders"], rng_index, role_index * 4)
    q_type, q_template = QUESTION_PATTERNS[rng_index % len(QUESTION_PATTERNS)]
    difficulty = DIFFICULTIES[(rng_index // 3) % len(DIFFICULTIES)]
    focus = choose(FOCUSES, rng_index, role_index)
    tier = tier_for(rng_index)
    question = q_template.format(issue=issue, setting=setting, role=profile["role"], tool=tool, stakeholder=stakeholder)
    question += (
        f" Use an example connected to a {choose(MONTHS, rng_index, 17).lower()} "
        f"{choose(WORK_EVENTS, rng_index, 11)}, a {choose(CONSTRAINTS, rng_index, 13)}, "
        f"and an outcome near {5 + (rng_index * 17) % 38}% or {8 + (rng_index * 11) % 43} items."
    )
    answer = answer_text(profile, rng_index, tier, q_type, issue, setting, tool, stakeholder)
    scores = score_bundle(tier, rng_index, q_type)
    delivery = delivery_metrics(tier, rng_index)
    confidence = {
        "excellent": 92,
        "strong": 87,
        "adequate": 78,
        "weak": 66,
        "poor": 55,
    }[tier] + (rng_index % 5 - 2)
    record_id = f"sr-v3-{global_index + 1:05d}"

    csv_row = {
        "schema_version": 1,
        "source": SOURCE,
        "record_id": record_id,
        "audit_status": "approved",
        "data_origin": DATA_ORIGIN,
        "label_policy": LABEL_POLICY,
        "scoring_confidence": clamp(confidence),
        "created_at": CREATED_AT,
        "question": question,
        "question_type": q_type,
        "expected_guide": GUIDES[q_type],
        "mapped_skills": "; ".join(profile["skills"]),
        "category": "Job Interview",
        "target_position": profile["role"],
        "difficulty": difficulty,
        "interview_focus": focus,
        "answer": answer,
        **delivery,
        **scores,
        "ai_feedback": feedback_text(profile["role"], tier, q_type),
    }

    json_row = {
        "schema_version": 1,
        "source": SOURCE,
        "input": {
            "record_id": record_id,
            "question": question,
            "question_type": q_type,
            "expected_guide": GUIDES[q_type],
            "mapped_skills": profile["skills"],
            "category": "Job Interview",
            "target_position": profile["role"],
            "difficulty": difficulty,
            "interview_focus": focus,
            "answer": answer,
            **delivery,
        },
        "output": {
            **scores,
            "ai_feedback": csv_row["ai_feedback"],
        },
        "metadata": {
            "audit_status": "approved",
            "data_origin": DATA_ORIGIN,
            "label_policy": LABEL_POLICY,
            "scoring_confidence": csv_row["scoring_confidence"],
            "created_at": CREATED_AT,
        },
    }
    return csv_row, json_row


def unique_rows() -> tuple[list[dict[str, Any]], list[dict[str, Any]]]:
    random.seed(SEED)
    csv_rows: list[dict[str, Any]] = []
    json_rows: list[dict[str, Any]] = []
    answers: set[str] = set()
    questions: set[str] = set()
    full_rows: set[str] = set()

    global_index = 0
    for role_index, profile in enumerate(ROLES):
        for offset in range(ROWS_PER_ROLE):
            csv_row, json_row = build_record(profile, role_index, offset, global_index)
            answer = csv_row["answer"]
            question = csv_row["question"]
            row_key = json.dumps(csv_row, sort_keys=True, ensure_ascii=False)
            if answer in answers:
                raise RuntimeError(f"Duplicate answer generated at {csv_row['record_id']}")
            if row_key in full_rows:
                raise RuntimeError(f"Duplicate full row generated at {csv_row['record_id']}")
            answers.add(answer)
            questions.add(question)
            full_rows.add(row_key)
            csv_rows.append(csv_row)
            json_rows.append(json_row)
            global_index += 1

    if len(questions) < len(csv_rows):
        raise RuntimeError(f"Questions are not fully unique: {len(questions)} of {len(csv_rows)}")
    return csv_rows, json_rows


def write_jsonl(path: Path, rows: list[dict[str, Any]]) -> None:
    with path.open("w", encoding="utf-8", newline="\n") as handle:
        for row in rows:
            handle.write(json.dumps(row, ensure_ascii=False, separators=(",", ":")) + "\n")


def write_csv(path: Path, rows: list[dict[str, Any]]) -> None:
    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=CSV_FIELDS)
        writer.writeheader()
        writer.writerows(rows)


def stratified_sample(rows: list[dict[str, Any]], per_role: int) -> list[dict[str, Any]]:
    by_role: dict[str, list[dict[str, Any]]] = defaultdict(list)
    for row in rows:
        by_role[row["input"]["target_position"]].append(row)

    selected: list[dict[str, Any]] = []
    for role in [profile["role"] for profile in ROLES]:
        role_rows = by_role[role]
        step = max(1, len(role_rows) // per_role)
        picked = [role_rows[index] for index in range(0, len(role_rows), step)][:per_role]
        selected.extend(picked)
    return selected


def validation_report(csv_rows: list[dict[str, Any]]) -> dict[str, Any]:
    answers = [row["answer"] for row in csv_rows]
    questions = [row["question"] for row in csv_rows]
    full = [json.dumps(row, sort_keys=True, ensure_ascii=False) for row in csv_rows]
    by_role = Counter(row["target_position"] for row in csv_rows)
    unique_answers_by_role = {
        role: len({row["answer"] for row in csv_rows if row["target_position"] == role})
        for role in sorted(by_role)
    }
    score_fields = ["score", "clarity_score", "relevance_score", "grammar_score", "professionalism_score", "star_method_score"]
    invalid_scores = [
        {"record_id": row["record_id"], "field": field, "value": row[field]}
        for row in csv_rows
        for field in score_fields
        if not isinstance(row[field], int) or row[field] < 0 or row[field] > 100
    ]
    missing = [
        {"record_id": row["record_id"], "field": field}
        for row in csv_rows
        for field in CSV_FIELDS
        if row.get(field) in ("", None)
    ]
    return {
        "dataset": "speakready_feedback_training_unique",
        "version": "v3",
        "source": SOURCE,
        "data_origin": DATA_ORIGIN,
        "record_count": len(csv_rows),
        "rows_per_role": ROWS_PER_ROLE,
        "role_count": len(by_role),
        "unique_answers": len(set(answers)),
        "unique_questions": len(set(questions)),
        "unique_full_rows": len(set(full)),
        "duplicate_answers": len(answers) - len(set(answers)),
        "duplicate_questions": len(questions) - len(set(questions)),
        "duplicate_full_rows": len(full) - len(set(full)),
        "rows_by_role": dict(sorted(by_role.items())),
        "unique_answers_by_role": unique_answers_by_role,
        "invalid_scores": invalid_scores[:20],
        "invalid_score_count": len(invalid_scores),
        "missing_required_count": len(missing),
        "missing_required_examples": missing[:20],
        "passed": (
            len(csv_rows) == len(ROLES) * ROWS_PER_ROLE
            and len(set(answers)) == len(csv_rows)
            and len(set(questions)) == len(csv_rows)
            and len(set(full)) == len(csv_rows)
            and len(invalid_scores) == 0
            and len(missing) == 0
        ),
    }


def write_manifests(csv_rows: list[dict[str, Any]], json_rows: list[dict[str, Any]], report: dict[str, Any]) -> None:
    csv_manifest = {
        "dataset": "speakready_feedback_training_csv",
        "version": "v3",
        "source": SOURCE,
        "data_origin": DATA_ORIGIN,
        "label_policy": LABEL_POLICY,
        "created_at": CREATED_AT,
        "record_count": len(csv_rows),
        "rows_per_role": ROWS_PER_ROLE,
        "positions": report["rows_by_role"],
        "unique_answers": report["unique_answers"],
        "unique_questions": report["unique_questions"],
        "csv_sha256": sha256(CSV_PATH),
        "jsonl_sha256": sha256(JSONL_PATH),
        "validation_report": str(VALIDATION_PATH.relative_to(ROOT)).replace("\\", "/"),
    }
    json_manifest = {
        "dataset": "speakready_feedback_training",
        "version": "v3",
        "created_at": CREATED_AT,
        "record_count": len(json_rows),
        "source": SOURCE,
        "data_origin": DATA_ORIGIN,
        "label_policy": LABEL_POLICY,
        "output_path": "normalized/training/feedback_train.jsonl",
        "jsonl_sha256": sha256(JSONL_PATH),
    }
    CSV_MANIFEST_PATH.write_text(json.dumps(csv_manifest, indent=4, ensure_ascii=False) + "\n", encoding="utf-8")
    JSONL_MANIFEST_PATH.write_text(json.dumps(json_manifest, indent=4, ensure_ascii=False) + "\n", encoding="utf-8")


def main() -> int:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    csv_rows, json_rows = unique_rows()
    report = validation_report(csv_rows)
    if not report["passed"]:
        raise RuntimeError(f"Dataset validation failed before write: {report}")

    write_csv(CSV_PATH, csv_rows)
    write_jsonl(JSONL_PATH, json_rows)
    write_jsonl(SAMPLE_2000_PATH, stratified_sample(json_rows, 100))
    write_jsonl(SAMPLE_200_PATH, stratified_sample(json_rows, 10))

    report["csv_sha256"] = sha256(CSV_PATH)
    report["jsonl_sha256"] = sha256(JSONL_PATH)
    report["sample_2000_sha256"] = sha256(SAMPLE_2000_PATH)
    report["sample_200_sha256"] = sha256(SAMPLE_200_PATH)
    VALIDATION_PATH.write_text(json.dumps(report, indent=4, ensure_ascii=False) + "\n", encoding="utf-8")
    write_manifests(csv_rows, json_rows, report)

    print(json.dumps({
        "status": "ok",
        "csv": str(CSV_PATH),
        "jsonl": str(JSONL_PATH),
        "rows": len(csv_rows),
        "unique_answers": report["unique_answers"],
        "unique_questions": report["unique_questions"],
        "passed": report["passed"],
    }, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
