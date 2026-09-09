#!/usr/bin/env python3
"""Validate SpeakReady question-bank structure and source reliability metadata."""

from __future__ import annotations

import argparse
import csv
import collections
import json
import re
import sys
from pathlib import Path
from typing import Any


DATASET_NAME = "speakready_reliable_questions"
TRUSTED_RELIABILITY = {
 "official_government_source",
 "official_university_source",
 "established_career_platform",
 "established_recruitment_firm",
 "career_platform",
 "user_supplied_private_dataset",
}
REVIEW_REQUIRED_RELIABILITY = {
 "internal_generated_review_required",
}
QUESTION_TYPES = {"behavioral", "situational", "technical", "personal"}
PROMPT_START_PATTERN = re.compile(
 r"^(?:"
 r"tell|describe|share|give|walk|explain|define|differentiate|distinguish|draw|"
 r"name|list|compare|contrast|outline|discuss|provide|recall|sell|talk|"
 r"suppose|scenario|assume|assuming|consider|what|what(?:'|’)?s|whats|why|how|when|"
 r"where|which|who|can|could|would|should|do|does|did|is|are|has|have|had|"
 r"given|imagine|if|as|during|last|introduce|basically|typically|it(?:'|’)?s|"
 r"you(?:'|’)?ve|you(?:'|’)?re|you are|you have|your team|"
 r"our|we want|there is|in|a time|a client|a customer|a critical|a major|the customer|since|with|"
 r"i can|something"
 r")\b",
 flags=re.IGNORECASE,
)


def clean_text(value: Any) -> str:
 return re.sub(r"\s+", " ", str(value or "")).strip()


def normalized_text(value: Any) -> str:
 return re.sub(r"[^a-z0-9]+", " ", str(value or "").lower()).strip()


def latest_manifest(root: Path) -> Path:
 manifests = []
 for path in (root / "manifests").glob(f"{DATASET_NAME}_*.json"):
 match = re.match(rf"{DATASET_NAME}_(\d{{4}}-\d{{2}}-\d{{2}})\.json$", path.name)
 if match:
 manifests.append((match.group(1), path))
 if not manifests:
 raise SystemExit("No question-bank manifest found.")
 return sorted(manifests, reverse=True)[0][1]


def read_jsonl(path: Path) -> list[dict[str, Any]]:
 rows: list[dict[str, Any]] = []
 with path.open("r", encoding="utf-8") as handle:
 for line_no, line in enumerate(handle, 1):
 line = line.strip()
 if not line:
 continue
 try:
 row = json.loads(line)
 except json.JSONDecodeError as exc:
 raise SystemExit(f"Invalid JSONL at {path}:{line_no}: {exc}") from exc
 if not isinstance(row, dict):
 raise SystemExit(f"Invalid row type at {path}:{line_no}: expected object")
 rows.append(row)
 return rows


def normalize_header(value: Any) -> str:
 value = str(value or "").lstrip("\ufeff")
 return re.sub(r"[^a-z0-9]+", "_", value.lower()).strip("_")


def split_list(value: Any) -> list[str]:
 return [item.strip() for item in re.split(r"\s*[;,]\s*", str(value or "")) if item.strip()]


def csv_value(row: dict[str, Any], *keys: str) -> str:
 for key in keys:
 value = clean_text(row.get(key))
 if value:
 return value
 return ""


def detect_csv_delimiter(path: Path) -> str:
 with path.open("r", encoding="utf-8-sig", newline="") as handle:
 for line in handle:
 line = line.strip()
 if not line:
 continue
 counts = {
 ",": line.count(","),
 ";": line.count(";"),
 "\t": line.count("\t"),
 }
 return max(counts, key=counts.get)
 return ","


def csv_dataset_defaults(path: Path, role: str, answer: str) -> dict[str, str]:
 normalized_path = str(path).replace("\\", "/").lower()
 if "career_qa_dataset" in normalized_path:
 return {
 "id_prefix": "career-qa-csv",
 "source_key": "career_qa_dataset_csv",
 "provenance": "user_supplied_career_qa_csv",
 "archive_category": "Career QA",
 "category": "Job Interview",
 "mapped_skills": [],
 "archive_roles": [],
 }
 if "full_interview_questions_dataset" in normalized_path:
 return {
 "id_prefix": "full-interview-csv",
 "source_key": "full_interview_questions_dataset_csv",
 "provenance": "user_supplied_full_interview_questions_csv",
 "archive_category": "Full Interview Questions",
 "category": "Job Interview",
 "mapped_skills": [],
 "archive_roles": [],
 }
 if "quality_assurance_interview_questions_dataset" in normalized_path:
 return {
 "id_prefix": "qa-interview-csv",
 "source_key": "quality_assurance_interview_questions_csv",
 "provenance": "user_supplied_quality_assurance_interview_csv",
 "archive_category": "Quality Assurance",
 "category": "Technical",
 "mapped_skills": ["Quality Assurance", "Software Testing", "Technical Interview"],
 "archive_roles": ["Quality Assurance"],
 }
 return {
 "id_prefix": "question-csv",
 "source_key": "uploaded_hr_interview_questions_archive",
 "provenance": "user_supplied_hr_archive_normalized",
 "archive_category": "Career QA" if role and answer else "",
 "category": "Job Interview",
 "mapped_skills": [],
 "archive_roles": [],
 }


def csv_question_type(category: str, role: str, answer: str) -> str:
 category_type = normalized_text(category)
 if category_type in QUESTION_TYPES:
 return category_type.title()
 return "Technical" if role and answer else "Behavioral"


def csv_default_expected_guide(role: str, category: str) -> str:
 if role:
 return f"Frame the answer for the {role} role. Include a clear explanation, a practical example, and a concise takeaway."
 if category:
 return f"Frame the answer for this {category} interview prompt. Include a clear explanation, a practical example, and a concise takeaway."
 return "Give a clear interview answer with a practical example and a concise takeaway."


def read_csv_rows(path: Path) -> list[dict[str, Any]]:
 rows: list[dict[str, Any]] = []
 with path.open("r", encoding="utf-8-sig", newline="") as handle:
 reader = csv.DictReader(handle, delimiter=detect_csv_delimiter(path))
 reader.fieldnames = [normalize_header(field) for field in reader.fieldnames or []]
 for index, source_row in enumerate(reader, 1):
 row = {normalize_header(key): value for key, value in source_row.items()}
 question = csv_value(row, "question_text", "question")
 if not question:
 continue
 role = csv_value(row, "role", "target_role")
 answer = csv_value(row, "answer", "expected_answer", "ideal_answer", "sample_answer")
 defaults = csv_dataset_defaults(path, role, answer)
 category = csv_value(row, "category") or defaults["category"]
 source_keys = split_list(row.get("source_keys"))
 if not source_keys:
 source_keys = [defaults["source_key"]]
 mapped_skills = split_list(row.get("mapped_skills"))
 if not mapped_skills and role:
 mapped_skills = [role, "Role Knowledge", "Career Readiness"]
 elif not mapped_skills:
 mapped_skills = defaults["mapped_skills"]
 archive_roles = split_list(row.get("archive_roles"))
 if not archive_roles and role:
 archive_roles = [role]
 elif not archive_roles:
 archive_roles = defaults["archive_roles"]
 rows.append({
 "id": csv_value(row, "record_id", "id") or f"{defaults['id_prefix']}-{index:04d}",
 "dataset_key": csv_value(row, "dataset_key") or "ph_job_interview",
 "category": category,
 "country": csv_value(row, "country") or "General",
 "question_text": question,
 "type": csv_value(row, "type") or csv_question_type(category, role, answer),
 "difficulty": csv_value(row, "difficulty") or "Medium",
 "expected_guide": csv_value(row, "expected_guide") or answer or csv_default_expected_guide(role, category),
 "mapped_skills": mapped_skills,
 "source_keys": source_keys,
 "provenance": csv_value(row, "provenance") or defaults["provenance"],
 "archive_category": csv_value(row, "archive_category") or defaults["archive_category"],
 "archive_record_count": csv_value(row, "archive_record_count"),
 "archive_roles": archive_roles,
 "archive_experience_levels": split_list(row.get("archive_experience_levels")),
 })
 return rows


def read_normalized_file(path: Path, file_format: str) -> list[dict[str, Any]]:
 return read_csv_rows(path) if file_format.lower() == "csv" else read_jsonl(path)


def unique_question_rows(rows: list[dict[str, Any]]) -> list[dict[str, Any]]:
 unique: list[dict[str, Any]] = []
 seen: set[str] = set()
 for row in rows:
 key = normalized_text(row.get("question_text"))
 if not key or key in seen:
 continue
 unique.append(row)
 seen.add(key)
 return unique


def source_quality(source: dict[str, Any] | None) -> str:
 reliability = clean_text((source or {}).get("reliability"))
 if reliability in TRUSTED_RELIABILITY:
 return "trusted"
 if reliability in REVIEW_REQUIRED_RELIABILITY:
 return "review_required"
 return "unknown"


def is_valid_prompt_text(question: str) -> bool:
 if question.endswith("?"):
 return True
 if "?" in question:
 return True

 return bool(PROMPT_START_PATTERN.match(question))


def row_errors(row: dict[str, Any], sources: dict[str, dict[str, Any]]) -> list[str]:
 errors = []
 required = ["id", "dataset_key", "category", "question_text", "type", "difficulty", "expected_guide"]
 for field in required:
 if clean_text(row.get(field)) == "":
 errors.append(f"missing_{field}")

 source_keys = row.get("source_keys", [])
 if not isinstance(source_keys, list) or not source_keys:
 errors.append("missing_source_keys")
 else:
 for source_key in source_keys:
 if clean_text(source_key) not in sources:
 errors.append(f"unknown_source_key:{source_key}")

 mapped_skills = row.get("mapped_skills", [])
 if not isinstance(mapped_skills, list) or not [skill for skill in mapped_skills if clean_text(skill)]:
 errors.append("missing_mapped_skills")

 question = clean_text(row.get("question_text"))
 if question and not is_valid_prompt_text(question):
 errors.append("invalid_prompt_punctuation")
 if len(question.split()) < 4 and not is_valid_prompt_text(question):
 errors.append("question_too_short")

 return errors


def main() -> int:
 parser = argparse.ArgumentParser()
 parser.add_argument("--datasets-root", default="storage/app/private/datasets")
 parser.add_argument("--manifest", default="")
 parser.add_argument("--write-report", default="")
 args = parser.parse_args()

 root = Path(args.datasets_root)
 manifest_path = Path(args.manifest) if args.manifest else latest_manifest(root)
 if args.manifest and not manifest_path.is_absolute():
 manifest_path = root / manifest_path

 manifest = json.loads(manifest_path.read_text(encoding="utf-8-sig"))
 normalized_paths: list[str] = []
 rows: list[dict[str, Any]] = []
 for file_info in manifest.get("normalized_files", []):
 if not isinstance(file_info, dict):
 continue
 rel_path = file_info.get("path")
 if not isinstance(rel_path, str):
 continue
 normalized_path = root / rel_path
 normalized_paths.append(str(normalized_path))
 rows.extend(read_normalized_file(
 normalized_path,
 clean_text(file_info.get("format")) or normalized_path.suffix.lstrip("."),
 ))
 rows = unique_question_rows(rows)
 source_index_path = root / manifest["raw_source_index"]["path"]
 source_index = json.loads(source_index_path.read_text(encoding="utf-8-sig"))
 sources = {
 clean_text(source.get("key")): source
 for source in source_index.get("sources", [])
 if isinstance(source, dict) and clean_text(source.get("key"))
 }
 duplicate_count = 0
 seen_questions: set[str] = set()
 errors_by_type = collections.Counter()
 rows_by_quality = collections.Counter()
 rows_by_category = collections.Counter()
 rows_by_dataset = collections.Counter()
 rows_by_provenance = collections.Counter()

 for row in rows:
 key = normalized_text(row.get("question_text"))
 if key in seen_questions:
 duplicate_count += 1
 seen_questions.add(key)

 row_source_keys = [clean_text(source_key) for source_key in row.get("source_keys", [])]
 qualities = {source_quality(sources.get(source_key)) for source_key in row_source_keys}
 if "trusted" in qualities and "review_required" in qualities:
 quality = "source_grounded_review_required"
 elif "trusted" in qualities:
 quality = "trusted"
 elif "review_required" in qualities:
 quality = "review_required"
 else:
 quality = "unknown"

 rows_by_quality[quality] += 1
 rows_by_category[clean_text(row.get("category")) or "Unknown"] += 1
 rows_by_dataset[clean_text(row.get("dataset_key")) or "Unknown"] += 1
 rows_by_provenance[clean_text(row.get("provenance")) or "Unknown"] += 1

 for error in row_errors(row, sources):
 errors_by_type[error] += 1

 if duplicate_count:
 errors_by_type["duplicate_question_text"] = duplicate_count

 report = {
 "status": "passed" if not errors_by_type else "failed",
 "manifest": str(manifest_path),
 "normalized_path": normalized_paths[0] if normalized_paths else "",
 "normalized_paths": normalized_paths,
 "records": len(rows),
 "manifest_records": manifest.get("records"),
 "record_count_matches_manifest": len(rows) == manifest.get("records"),
 "rows_by_reliability_quality": dict(rows_by_quality),
 "rows_by_category": dict(rows_by_category),
 "rows_by_dataset_key": dict(rows_by_dataset),
 "rows_by_provenance": dict(rows_by_provenance),
 "source_reliability": {
 key: clean_text(source.get("reliability")) or "unknown"
 for key, source in sorted(sources.items())
 },
 "errors": dict(errors_by_type),
 "notes": [
 "Generated expansion rows are marked review_required, not official.",
 "Trusted rows are source-backed by official, established public, or private user-supplied sources.",
 ],
 }

 if args.write_report:
 output_path = Path(args.write_report)
 output_path.parent.mkdir(parents=True, exist_ok=True)
 output_path.write_text(json.dumps(report, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")

 print(json.dumps(report, ensure_ascii=False, indent=2))
 return 0 if report["status"] == "passed" else 1


if __name__ == "__main__":
 raise SystemExit(main())
