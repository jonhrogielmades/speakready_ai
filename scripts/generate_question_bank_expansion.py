#!/usr/bin/env python3
"""Expand SpeakReady's normalized question bank with generated practice prompts."""

from __future__ import annotations

import argparse
import datetime as dt
import hashlib
import json
import re
from pathlib import Path
from typing import Any


DATASET_NAME = "speakready_reliable_questions"
SOURCE_KEY = "speakready_generated_question_expansion_2026_09_07"
JOB_SOURCE_KEYS = [
 SOURCE_KEY,
 "ny_dol_p679_interviewing",
 "ny_dol_interviewing",
 "ny_civil_service_interviewing",
 "jobstreet_ph_common_interview",
 "michaelpage_ph_common_interview",
 "bossjob_ph_interview_questions",
]
SCHOOL_SOURCE_KEYS = [
 SOURCE_KEY,
 "upcat_about_admissions",
]


JOB_ROLES = [
 "software developer", "data analyst", "project coordinator", "marketing associate",
 "customer support representative", "accounting assistant", "sales associate",
 "human resources assistant", "teacher", "nurse", "administrative assistant",
 "graphic designer", "quality assurance analyst", "operations associate",
 "business analyst", "IT support specialist",
 "content writer", "social media specialist", "financial analyst", "product specialist",
]
JOB_TOPICS = [
 ("teamwork", ["Teamwork", "Communication", "Collaboration"]),
 ("conflict at work", ["Conflict Resolution", "Professionalism", "Judgment"]),
 ("deadline pressure", ["Prioritization", "Ownership", "Time Management"]),
 ("learning a new tool", ["Learning Agility", "Adaptability", "Evidence"]),
 ("receiving feedback", ["Coachability", "Growth Mindset", "Self Awareness"]),
 ("solving a customer problem", ["Customer Service", "Problem Solving", "Empathy"]),
 ("leading without authority", ["Leadership", "Influence", "Communication"]),
 ("making a mistake", ["Accountability", "Resilience", "Professionalism"]),
 ("improving a process", ["Process Improvement", "Initiative", "Impact"]),
 ("handling ambiguity", ["Adaptability", "Judgment", "Problem Solving"]),
]
JOB_TEMPLATES = [
 ("Behavioral", "Medium", "Tell me about a time you handled {topic} as a {role}."),
 ("Behavioral", "Medium", "Describe a specific example where {topic} affected your work as a {role}."),
 ("Behavioral", "Hard", "Walk me through a challenging {role} situation involving {topic}, including what you did and what changed."),
 ("Situational", "Medium", "How would you approach {topic} in your first month as a {role}?"),
 ("Situational", "Hard", "If {topic} created a risk in your {role} team, what steps would you take?"),
 ("Personal", "Easy", "How has your experience with {topic} prepared you for a {role} position?"),
 ("Personal", "Medium", "What have you learned about yourself from {role} experiences involving {topic}?"),
 ("Technical", "Hard", "What tools, checks, or methods would help you manage {topic} in a {role} role?"),
]

SCHOOL_PROGRAMS = [
 "computer science", "business administration", "nursing", "education", "engineering",
 "psychology", "accountancy", "hospitality management", "information technology",
 "communication arts", "biology", "public administration",
 "architecture", "criminology", "medical technology", "tourism management",
 "civil engineering", "entrepreneurship",
]
SCHOOL_TOPICS = [
 ("choosing this program", ["Program Fit", "Academic Motivation", "Self Awareness"]),
 ("study habits", ["Academic Readiness", "Discipline", "Time Management"]),
 ("group projects", ["Collaboration", "Communication", "Responsibility"]),
 ("academic challenges", ["Resilience", "Growth Mindset", "Problem Solving"]),
 ("community contribution", ["Community Fit", "Service Mindset", "Communication"]),
 ("career goals", ["Career Planning", "Program Fit", "Commitment"]),
 ("leadership experience", ["Leadership", "Initiative", "Evidence"]),
 ("balancing responsibilities", ["Prioritization", "Independence", "Academic Readiness"]),
 ("asking for help", ["Help Seeking", "Resourcefulness", "Communication"]),
 ("personal values", ["Self Awareness", "Values Fit", "Authenticity"]),
]
SCHOOL_TEMPLATES = [
 ("Personal", "Easy", "Why are you interested in studying {program}?"),
 ("Personal", "Medium", "How does {topic} connect to your readiness for {program}?"),
 ("Behavioral", "Medium", "Tell me about a time when {topic} shaped your academic growth for {program}."),
 ("Behavioral", "Hard", "Describe a difficult experience with {topic} and what it taught you about preparing for {program}."),
 ("Situational", "Medium", "How would you handle {topic} while studying {program}?"),
 ("Situational", "Hard", "If you struggled with {topic} in your first semester of {program}, what would you do?"),
 ("Personal", "Medium", "What should the admissions committee understand about your experience with {topic} and your interest in {program}?"),
 ("Personal", "Hard", "What evidence shows that {topic} will help you succeed in {program}?"),
 ("Situational", "Medium", "How would you use campus resources to strengthen your ability to manage {topic} as a {program} student?"),
 ("Behavioral", "Medium", "Share an example of {topic} that would help you contribute to a {program} class."),
]


def clean_text(value: Any) -> str:
 return re.sub(r"\s+", " ", str(value or "")).strip()


def normalized_text(value: Any) -> str:
 return re.sub(r"[^a-z0-9]+", " ", str(value or "").lower()).strip()


def file_sha256(path: Path) -> str:
 digest = hashlib.sha256()
 with path.open("rb") as handle:
 for chunk in iter(lambda: handle.read(1024 * 1024), b""):
 digest.update(chunk)
 return digest.hexdigest()


def read_jsonl(path: Path) -> list[dict[str, Any]]:
 rows: list[dict[str, Any]] = []
 with path.open("r", encoding="utf-8") as handle:
 for line in handle:
 line = line.strip()
 if line:
 rows.append(json.loads(line))
 return rows


def write_jsonl(path: Path, rows: list[dict[str, Any]]) -> None:
 with path.open("w", encoding="utf-8") as handle:
 for row in rows:
 handle.write(json.dumps(row, ensure_ascii=False, separators=(",", ":")) + "\n")


def next_id(rows: list[dict[str, Any]]) -> int:
 highest = 0
 for row in rows:
 match = re.match(r"srq-\d{4}-(\d+)$", str(row.get("id", "")))
 if match:
 highest = max(highest, int(match.group(1)))
 return highest + 1


def guide(question_type: str, category: str, topic: str) -> str:
 if question_type == "Behavioral":
 return f"Use STAR: describe the {topic} context, your responsibility, the action you took, and the result or lesson."
 if question_type == "Situational":
 return f"Explain realistic steps for {topic}, including priorities, communication, support needed, and how you would check progress."
 if category == "School Admission":
 return f"Connect {topic} to your academic motivation, preparation, values, and fit for the program."
 return f"Answer directly, give concrete evidence about {topic}, and connect it to the role or organization."


def generated_job_records(count: int, start: int) -> list[dict[str, Any]]:
 rows: list[dict[str, Any]] = []
 seen: set[str] = set()
 for role in JOB_ROLES:
 for topic, skills in JOB_TOPICS:
 for question_type, difficulty, template in JOB_TEMPLATES:
 question_text = template.format(role=role, topic=topic)
 key = normalized_text(question_text)
 if key in seen:
 continue
 seen.add(key)
 rows.append({
 "id": f"srq-2026-{start + len(rows):04d}",
 "dataset_key": "ph_job_interview_expanded",
 "category": "Job Interview",
 "country": "General",
 "question_text": question_text,
 "type": question_type,
 "difficulty": difficulty,
 "expected_guide": guide(question_type, "Job Interview", topic),
 "mapped_skills": skills + (["STAR Method"] if question_type == "Behavioral" else ["Role Fit"]),
 "source_keys": JOB_SOURCE_KEYS,
 "provenance": "source_grounded_generated_practice_prompt",
 })
 if len(rows) >= count:
 return rows
 return rows


def generated_school_records(count: int, start: int) -> list[dict[str, Any]]:
 rows: list[dict[str, Any]] = []
 seen: set[str] = set()
 for program in SCHOOL_PROGRAMS:
 for topic, skills in SCHOOL_TOPICS:
 for question_type, difficulty, template in SCHOOL_TEMPLATES:
 question_text = template.format(program=program, topic=topic)
 key = normalized_text(question_text)
 if key in seen:
 continue
 seen.add(key)
 rows.append({
 "id": f"srq-2026-{start + len(rows):04d}",
 "dataset_key": "ph_school_admission_expanded",
 "category": "School Admission",
 "country": "General",
 "question_text": question_text,
 "type": question_type,
 "difficulty": difficulty,
 "expected_guide": guide(question_type, "School Admission", topic),
 "mapped_skills": skills,
 "source_keys": SCHOOL_SOURCE_KEYS,
 "provenance": "source_grounded_generated_practice_prompt",
 })
 if len(rows) >= count:
 return rows
 return rows


def main() -> int:
 parser = argparse.ArgumentParser()
 parser.add_argument("--datasets-root", default="storage/app/private/datasets")
 parser.add_argument("--version", default="2026-09-07")
 parser.add_argument("--job-count", type=int, default=1500)
 parser.add_argument("--school-count", type=int, default=1500)
 parser.add_argument("--replace-source", action="store_true")
 args = parser.parse_args()

 root = Path(args.datasets_root)
 normalized_path = root / "normalized" / "questions" / args.version / f"{DATASET_NAME}.jsonl"
 manifest_path = root / "manifests" / f"{DATASET_NAME}_{args.version}.json"
 source_index_path = root / "raw" / "question_sources" / args.version / "_source_index.json"
 if not normalized_path.is_file() or not manifest_path.is_file():
 raise SystemExit(f"Dataset version not found: {args.version}")

 existing = read_jsonl(normalized_path)
 if args.replace_source:
 existing = [
 row for row in existing
 if SOURCE_KEY not in [str(source_key) for source_key in row.get("source_keys", [])]
 ]
 seen = {normalized_text(row.get("question_text")) for row in existing}
 generated = generated_job_records(args.job_count, next_id(existing))
 generated += generated_school_records(args.school_count, next_id(existing) + len(generated))
 additions = []
 for row in generated:
 key = normalized_text(row.get("question_text"))
 if key in seen:
 continue
 additions.append(row)
 seen.add(key)

 merged = existing + additions
 write_jsonl(normalized_path, merged)

 manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
 manifest["description"] = "Question-only normalized practice dataset for SpeakReady AI, expanded with generated job interview and school admission prompts."
 manifest["created_at_utc"] = dt.datetime.now(dt.timezone.utc).isoformat(timespec="seconds")
 manifest["records"] = len(merged)
 manifest["dataset_keys"] = sorted({str(row.get("dataset_key")) for row in merged if row.get("dataset_key")})
 manifest["categories"] = sorted({str(row.get("category")) for row in merged if row.get("category")})
 manifest["normalized_files"][0]["sha256"] = file_sha256(normalized_path)
 manifest["normalized_files"][0]["bytes"] = normalized_path.stat().st_size
 manifest["generated_expansion"] = {
 "source_key": SOURCE_KEY,
 "job_interview_records_requested": args.job_count,
 "school_admission_records_requested": args.school_count,
 "records_added": len(additions),
 }
 manifest_path.write_text(json.dumps(manifest, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")

 if source_index_path.is_file():
 source_index = json.loads(source_index_path.read_text(encoding="utf-8"))
 sources = source_index.setdefault("sources", [])
 if not any(source.get("key") == SOURCE_KEY for source in sources if isinstance(source, dict)):
 sources.append({
 "key": SOURCE_KEY,
 "name": "SpeakReady generated question expansion",
 "url": None,
 "publisher": "SpeakReady AI",
 "source_type": "generated_practice_dataset",
 "reliability": "internal_generated_review_required",
 "notes": "Generated from bounded templates for local interview and school admission practice.",
 })
 source_index["retrieved_at_utc"] = dt.datetime.now(dt.timezone.utc).isoformat(timespec="seconds")
 source_index_path.write_text(json.dumps(source_index, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
 manifest["raw_source_index"]["sha256"] = file_sha256(source_index_path)
 manifest["raw_source_index"]["bytes"] = source_index_path.stat().st_size
 manifest["source_count"] = len(source_index.get("sources", []))
 manifest_path.write_text(json.dumps(manifest, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")

 print(json.dumps({
 "status": "expanded",
 "previous_records": len(existing),
 "records_added": len(additions),
 "records_written": len(merged),
 "job_interview_added": sum(1 for row in additions if row["category"] == "Job Interview"),
 "school_admission_added": sum(1 for row in additions if row["category"] == "School Admission"),
 "normalized_path": str(normalized_path),
 "manifest_path": str(manifest_path),
 }, indent=2))
 return 0


if __name__ == "__main__":
 raise SystemExit(main())
