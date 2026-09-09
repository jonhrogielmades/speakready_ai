#!/usr/bin/env python3
"""Build a compact SpeakReady question bank from a user-supplied dataset.

The uploaded HR dataset is a large JSON array with many duplicate question
rows. This script streams either the raw JSON file or a zip containing it,
deduplicates question text, and writes a new normalized question-bank version
that the Laravel app can load through QuestionDatasetProvider.
"""

from __future__ import annotations

import argparse
import codecs
import collections
import datetime as dt
import hashlib
import json
import re
import shutil
import time
import zipfile
from pathlib import Path
from typing import Any, Iterable


DATASET_NAME = "speakready_reliable_questions"
ARCHIVE_SOURCE_KEY = "uploaded_hr_interview_questions_archive"
DEFAULT_ARCHIVE = r"C:\Users\LENOVO\Downloads\archive.zip"
EXCLUDED_DATASET_KEYS = {"ph_it_programming", "ph_scholarship"}


def clean_text(value: Any) -> str:
 return re.sub(r"\s+", " ", str(value or "")).strip()


def normalized_text(value: Any) -> str:
 return re.sub(r"[^a-z0-9]+", " ", str(value or "").lower()).strip()


def title_skill(value: str) -> str:
 words = re.split(r"[\s_-]+", clean_text(value))
 return " ".join(word.capitalize() for word in words if word)


def file_sha256(path: Path) -> str:
 digest = hashlib.sha256()
 with path.open("rb") as handle:
 for chunk in iter(lambda: handle.read(1024 * 1024), b""):
 digest.update(chunk)

 return digest.hexdigest()


def payload_sha256(chunks: Iterable[bytes]) -> tuple[str, Iterable[bytes]]:
 digest = hashlib.sha256()

 def generator() -> Iterable[bytes]:
 for chunk in chunks:
 digest.update(chunk)
 yield chunk

 return digest.hexdigest(), generator()


def latest_manifest(root: Path, version: str) -> Path | None:
 manifest_dir = root / "manifests"
 if not manifest_dir.is_dir():
 return None

 candidates = []
 for path in manifest_dir.glob(f"{DATASET_NAME}_*.json"):
 match = re.match(rf"{re.escape(DATASET_NAME)}_(\d{{4}}-\d{{2}}-\d{{2}})\.json$", path.name)
 if match and match.group(1) < version:
 candidates.append((match.group(1), path))

 return sorted(candidates, reverse=True)[0][1] if candidates else None


def read_json_file(path: Path) -> dict[str, Any]:
 return json.loads(path.read_text(encoding="utf-8"))


def read_jsonl(path: Path) -> list[dict[str, Any]]:
 rows: list[dict[str, Any]] = []
 if not path.is_file():
 return rows

 with path.open("r", encoding="utf-8") as handle:
 for line_no, line in enumerate(handle, 1):
 line = line.strip()
 if not line:
 continue
 try:
 row = json.loads(line)
 except json.JSONDecodeError as exc:
 raise SystemExit(f"Invalid JSONL at {path}:{line_no}: {exc}") from exc
 if isinstance(row, dict):
 rows.append(row)

 return rows


def load_previous_bank(root: Path, version: str) -> tuple[list[dict[str, Any]], list[dict[str, Any]], dict[str, Any] | None]:
 manifest_path = latest_manifest(root, version)
 if manifest_path is None:
 return [], [], None

 manifest = read_json_file(manifest_path)
 normalized_path = manifest.get("normalized_files", [{}])[0].get("path")
 source_index_path = manifest.get("raw_source_index", {}).get("path")

 rows = read_jsonl(root / str(normalized_path)) if normalized_path else []
 sources: list[dict[str, Any]] = []
 if source_index_path:
 source_index_file = root / str(source_index_path)
 if source_index_file.is_file():
 sources = read_json_file(source_index_file).get("sources", [])

 rows = [
 row
 for row in rows
 if clean_text(row.get("dataset_key")) not in EXCLUDED_DATASET_KEYS
 ]

 return rows, sources, manifest


def archive_json_entry(zip_file: zipfile.ZipFile) -> zipfile.ZipInfo:
 entries = [
 entry
 for entry in zip_file.infolist()
 if not entry.is_dir() and entry.filename.lower().endswith(".json")
 ]
 if not entries:
 raise SystemExit("No JSON file found in the archive.")
 if len(entries) > 1:
 entries.sort(key=lambda entry: entry.file_size, reverse=True)

 return entries[0]


def stream_json_array_from_zip(archive_path: Path) -> tuple[Iterable[dict[str, Any]], dict[str, Any]]:
 zip_file = zipfile.ZipFile(archive_path)
 entry = archive_json_entry(zip_file)
 payload_digest = hashlib.sha256()
 decoder = json.JSONDecoder()
 utf8 = codecs.getincrementaldecoder("utf-8")()

 def rows() -> Iterable[dict[str, Any]]:
 buffer = ""
 position = 0
 started = False
 try:
 with zip_file.open(entry) as handle:
 while True:
 chunk = handle.read(1024 * 1024)
 if not chunk:
 buffer += utf8.decode(b"", final=True)
 break

 payload_digest.update(chunk)
 buffer += utf8.decode(chunk)

 while True:
 length = len(buffer)
 while position < length and buffer[position].isspace():
 position += 1

 if not started:
 if position < length and buffer[position] == "[":
 position += 1
 started = True
 else:
 break

 while position < length and (buffer[position].isspace() or buffer[position] == ","):
 position += 1

 if position < length and buffer[position] == "]":
 position += 1
 break

 try:
 item, end = decoder.raw_decode(buffer, position)
 except json.JSONDecodeError:
 break

 position = end
 if isinstance(item, dict):
 yield item

 if position > 1024 * 1024:
 buffer = buffer[position:]
 position = 0
 finally:
 zip_file.close()

 metadata = {
 "entry_name": entry.filename,
 "entry_bytes": entry.file_size,
 "entry_compressed_bytes": entry.compress_size,
 "payload_sha256": lambda: payload_digest.hexdigest(),
 }

 return rows(), metadata


def stream_json_array_from_file(json_path: Path) -> tuple[Iterable[dict[str, Any]], dict[str, Any]]:
 payload_digest = hashlib.sha256()
 decoder = json.JSONDecoder()
 utf8 = codecs.getincrementaldecoder("utf-8")()

 def rows() -> Iterable[dict[str, Any]]:
 buffer = ""
 position = 0
 started = False
 with json_path.open("rb") as handle:
 while True:
 chunk = handle.read(1024 * 1024)
 if not chunk:
 buffer += utf8.decode(b"", final=True)
 break

 payload_digest.update(chunk)
 buffer += utf8.decode(chunk)

 while True:
 length = len(buffer)
 while position < length and buffer[position].isspace():
 position += 1

 if not started:
 if position < length and buffer[position] == "[":
 position += 1
 started = True
 else:
 break

 while position < length and (buffer[position].isspace() or buffer[position] == ","):
 position += 1

 if position < length and buffer[position] == "]":
 position += 1
 break

 try:
 item, end = decoder.raw_decode(buffer, position)
 except json.JSONDecodeError:
 break

 position = end
 if isinstance(item, dict):
 yield item

 if position > 1024 * 1024:
 buffer = buffer[position:]
 position = 0

 metadata = {
 "entry_name": json_path.name,
 "entry_bytes": json_path.stat().st_size,
 "entry_compressed_bytes": None,
 "payload_sha256": lambda: payload_digest.hexdigest(),
 }

 return rows(), metadata


def stream_json_array(source_path: Path) -> tuple[Iterable[dict[str, Any]], dict[str, Any]]:
 if source_path.suffix.lower() == ".zip":
 return stream_json_array_from_zip(source_path)

 return stream_json_array_from_file(source_path)


def infer_type(question: str) -> str:
 text = question.lower()
 if re.search(
 r"\b(tell me about|describe|share|give (?:me )?an example|walk me through)\b.*\b(time|situation|experience|project|incident|challenge|conflict|problem|feedback|change|goal)\b",
 text,
 ):
 return "Behavioral"
 if re.search(r"\b(change|challenge|conflict|situation|problem|project|failure|success)\b.*\byou(?:'ve| have| had)\b", text):
 return "Behavioral"
 if re.search(r"\bwhat would you do\b|\bhow would you\b|\bif you\b|\bif.* happened\b", text):
 return "Situational"
 if re.match(r"how do you\b", text):
 return "Situational"

 return "Personal"


def expected_guide(question_type: str, archive_category: str, keywords: list[str]) -> str:
 category = clean_text(archive_category) or "interview readiness"
 if question_type == "Behavioral":
 guide = f"Use STAR with a specific {category.lower()} example: context, responsibility, action, and result or lesson."
 elif question_type == "Situational":
 guide = f"Explain a realistic {category.lower()} approach with clear steps, judgment, communication, and a way to verify the outcome."
 else:
 guide = f"Answer directly and connect the {category.lower()} point to the target role with concrete evidence."

 if keywords:
 guide += " Helpful focus words: " + ", ".join(keywords[:5]) + "."

 return guide


def mapped_skills(question_type: str, archive_category: str, keywords: list[str]) -> list[str]:
 skills: list[str] = []
 if archive_category:
 skills.append(title_skill(archive_category))
 for keyword in keywords:
 skill = title_skill(keyword)
 if skill and skill.lower() not in {item.lower() for item in skills}:
 skills.append(skill)

 category_l = archive_category.lower()
 if "team" in category_l:
 skills.append("Teamwork")
 if "conflict" in category_l:
 skills.append("Professionalism")
 if "career" in category_l or "motivation" in category_l:
 skills.append("Role Fit")
 if question_type == "Behavioral":
 skills.append("STAR Method")

 deduped: list[str] = []
 seen: set[str] = set()
 for skill in skills:
 key = skill.lower()
 if skill and key not in seen:
 deduped.append(skill)
 seen.add(key)

 return deduped[:8] or ["Communication", "Role Fit", "Evidence"]


def aggregate_archive_rows(archive_path: Path) -> tuple[list[dict[str, Any]], dict[str, Any]]:
 rows, metadata = stream_json_array(archive_path)
 buckets: dict[str, dict[str, Any]] = {}
 archive_categories = collections.Counter()
 roles = collections.Counter()
 difficulties = collections.Counter()
 source_types = collections.Counter()
 total_rows = 0
 started_at = time.time()

 for row in rows:
 total_rows += 1
 question = clean_text(row.get("question"))
 if not question:
 continue

 key = normalized_text(question)
 bucket = buckets.setdefault(
 key,
 {
 "question": question,
 "categories": collections.Counter(),
 "roles": collections.Counter(),
 "experiences": collections.Counter(),
 "difficulties": collections.Counter(),
 "source_types": collections.Counter(),
 "keywords": collections.Counter(),
 "ideal_answers": collections.Counter(),
 "records": 0,
 },
 )
 bucket["records"] += 1

 category = clean_text(row.get("category"))
 role = clean_text(row.get("role"))
 experience = clean_text(row.get("experience"))
 difficulty = clean_text(row.get("difficulty"))
 source_type = clean_text(row.get("source_type"))
 ideal_answer = clean_text(row.get("ideal_answer"))
 keywords = [clean_text(keyword) for keyword in row.get("keywords", []) if clean_text(keyword)]

 bucket["categories"].update([category] if category else [])
 bucket["roles"].update([role] if role else [])
 bucket["experiences"].update([experience] if experience else [])
 bucket["difficulties"].update([difficulty] if difficulty else [])
 bucket["source_types"].update([source_type] if source_type else [])
 bucket["ideal_answers"].update([ideal_answer] if ideal_answer else [])
 bucket["keywords"].update(keywords)

 archive_categories.update([category] if category else [])
 roles.update([role] if role else [])
 difficulties.update([difficulty] if difficulty else [])
 source_types.update([source_type] if source_type else [])

 normalized_rows: list[dict[str, Any]] = []
 for index, bucket in enumerate(sorted(buckets.values(), key=lambda item: normalized_text(item["question"])), 1):
 category = bucket["categories"].most_common(1)[0][0] if bucket["categories"] else "General HR"
 difficulty = bucket["difficulties"].most_common(1)[0][0] if bucket["difficulties"] else "Medium"
 question_type = infer_type(bucket["question"])
 keywords = [keyword for keyword, _ in bucket["keywords"].most_common(8)]
 roles_used = [role for role, _ in bucket["roles"].most_common(12)]
 experiences = [experience for experience, _ in bucket["experiences"].most_common(8)]

 normalized_rows.append(
 {
 "id": f"srq-2026-archive-{index:04d}",
 "dataset_key": "ph_job_interview",
 "category": "Job Interview",
 "country": "General",
 "question_text": bucket["question"],
 "type": question_type,
 "difficulty": difficulty.capitalize(),
 "expected_guide": expected_guide(question_type, category, keywords),
 "mapped_skills": mapped_skills(question_type, category, keywords),
 "source_keys": [ARCHIVE_SOURCE_KEY],
 "provenance": "user_supplied_hr_archive_normalized",
 "archive_category": category,
 "archive_record_count": bucket["records"],
 "archive_roles": roles_used,
 "archive_experience_levels": experiences,
 }
 )

 stats = {
 "input_rows": total_rows,
 "unique_question_texts": len(normalized_rows),
 "duplicate_rows_compacted": max(0, total_rows - len(normalized_rows)),
 "archive_categories": archive_categories.most_common(),
 "roles": roles.most_common(),
 "difficulties": difficulties.most_common(),
 "source_types": source_types.most_common(),
 "archive_entry": metadata["entry_name"],
 "archive_entry_bytes": metadata["entry_bytes"],
 "archive_entry_compressed_bytes": metadata["entry_compressed_bytes"],
 "archive_payload_sha256": metadata["payload_sha256"](),
 "processing_seconds": round(time.time() - started_at, 2),
 }

 return normalized_rows, stats


def next_srq_number(rows: list[dict[str, Any]]) -> int:
 highest = 0
 for row in rows:
 match = re.match(r"srq-\d{4}-(\d+)$", str(row.get("id", "")))
 if match:
 highest = max(highest, int(match.group(1)))

 return highest + 1


def merge_rows(previous_rows: list[dict[str, Any]], archive_rows: list[dict[str, Any]]) -> list[dict[str, Any]]:
 merged: list[dict[str, Any]] = []
 seen: set[str] = set()

 for row in previous_rows:
 question_key = normalized_text(row.get("question_text"))
 if not question_key or question_key in seen:
 continue
 merged.append(row)
 seen.add(question_key)

 next_id = next_srq_number(merged)
 for row in archive_rows:
 question_key = normalized_text(row.get("question_text"))
 if not question_key or question_key in seen:
 continue
 updated = dict(row)
 updated["id"] = f"srq-2026-{next_id:04d}"
 next_id += 1
 merged.append(updated)
 seen.add(question_key)

 return merged


def write_json(path: Path, payload: dict[str, Any]) -> None:
 path.parent.mkdir(parents=True, exist_ok=True)
 path.write_text(json.dumps(payload, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")


def write_jsonl(path: Path, rows: list[dict[str, Any]]) -> None:
 path.parent.mkdir(parents=True, exist_ok=True)
 with path.open("w", encoding="utf-8") as handle:
 for row in rows:
 handle.write(json.dumps(row, ensure_ascii=False, separators=(",", ":")) + "\n")


def copy_raw_archive(archive_path: Path, target_dir: Path) -> Path:
 target_dir.mkdir(parents=True, exist_ok=True)
 target_path = target_dir / archive_path.name
 if archive_path.resolve()!= target_path.resolve():
 shutil.copy2(archive_path, target_path)

 return target_path


def main() -> int:
 parser = argparse.ArgumentParser()
 parser.add_argument("--archive", default=DEFAULT_ARCHIVE, help="Path to the uploaded zip archive or raw JSON file.")
 parser.add_argument("--datasets-root", default="storage/app/private/datasets")
 parser.add_argument("--version", default=dt.date.today().isoformat())
 parser.add_argument("--skip-raw-copy", action="store_true")
 args = parser.parse_args()

 archive_path = Path(args.archive).expanduser().resolve()
 if not archive_path.is_file():
 raise SystemExit(f"Archive not found: {archive_path}")

 root = Path(args.datasets_root).resolve()
 version = clean_text(args.version)
 if not re.match(r"\d{4}-\d{2}-\d{2}$", version):
 raise SystemExit("--version must use YYYY-MM-DD format.")

 previous_rows, previous_sources, previous_manifest = load_previous_bank(root, version)
 archive_rows, archive_stats = aggregate_archive_rows(archive_path)
 merged_rows = merge_rows(previous_rows, archive_rows)

 normalized_rel = Path("normalized") / "questions" / version / f"{DATASET_NAME}.jsonl"
 readme_rel = Path("normalized") / "questions" / version / "README.md"
 raw_rel = Path("raw") / "question_sources" / version
 source_index_rel = raw_rel / "_source_index.json"
 manifest_rel = Path("manifests") / f"{DATASET_NAME}_{version}.json"

 raw_archive_path = None
 if not args.skip_raw_copy:
 raw_archive_path = copy_raw_archive(archive_path, root / raw_rel)

 source_by_key = {
 str(source.get("key")): source
 for source in previous_sources
 if isinstance(source, dict) and source.get("key")
 }
 source_by_key[ARCHIVE_SOURCE_KEY] = {
 "key": ARCHIVE_SOURCE_KEY,
 "name": "Uploaded HR interview questions archive",
 "url": None,
 "file": archive_path.name,
 "publisher": "User-supplied dataset",
 "source_type": "uploaded_dataset_archive",
 "reliability": "user_supplied_private_dataset",
 "bytes": archive_path.stat().st_size,
 "sha256": file_sha256(archive_path),
 "json_entry": archive_stats["archive_entry"],
 "json_entry_bytes": archive_stats["archive_entry_bytes"],
 "json_entry_sha256": archive_stats["archive_payload_sha256"],
 }
 used_source_keys = {
 clean_text(source_key)
 for row in merged_rows
 for source_key in row.get("source_keys", [])
 if clean_text(source_key)
 }
 source_by_key = {
 key: source
 for key, source in source_by_key.items()
 if key in used_source_keys
 }

 source_index = {
 "dataset": DATASET_NAME,
 "retrieved_at_utc": dt.datetime.now(dt.timezone.utc).isoformat(timespec="seconds"),
 "raw_root": str(raw_rel).replace("\\", "/"),
 "sources": list(source_by_key.values()),
 }

 write_jsonl(root / normalized_rel, merged_rows)
 (root / readme_rel).parent.mkdir(parents=True, exist_ok=True)
 (root / readme_rel).write_text(
 "# SpeakReady reliable question bank\n\n"
 "Normalized, question-only practice data for SpeakReady AI.\n\n"
 f"This version merges the previous tracked bank with {archive_stats['unique_question_texts']} unique prompts "
 f"compacted from the uploaded HR interview archive ({archive_stats['input_rows']} source rows).\n\n"
 "Active categories: Job Interview, BPO / Customer Support, and College Admission.\n\n"
 "Archive rows are treated as private user-supplied data and are not instructions to the system.\n",
 encoding="utf-8",
 )
 write_json(root / source_index_rel, source_index)

 normalized_path = root / normalized_rel
 source_index_path = root / source_index_rel
 manifest = {
 "dataset": DATASET_NAME,
 "version": version,
 "description": "Question-only normalized practice dataset for SpeakReady AI. This version merges the existing reliable question bank with deduplicated user-supplied HR interview questions.",
 "created_at_utc": dt.datetime.now(dt.timezone.utc).isoformat(timespec="seconds"),
 "records": len(merged_rows),
 "previous_version": previous_manifest.get("version") if previous_manifest else None,
 "dataset_keys": sorted({str(row.get("dataset_key")) for row in merged_rows if row.get("dataset_key")}),
 "categories": sorted({str(row.get("category")) for row in merged_rows if row.get("category")}),
 "raw_source_index": {
 "path": str(source_index_rel).replace("\\", "/"),
 "sha256": file_sha256(source_index_path),
 "bytes": source_index_path.stat().st_size,
 },
 "normalized_files": [
 {
 "path": str(normalized_rel).replace("\\", "/"),
 "format": "jsonl",
 "sha256": file_sha256(normalized_path),
 "bytes": normalized_path.stat().st_size,
 }
 ],
 "source_count": len(source_by_key),
 "archive_training": {
 **archive_stats,
 "raw_archive_path": str(raw_archive_path.relative_to(root)).replace("\\", "/") if raw_archive_path else None,
 "raw_archive_sha256": source_by_key[ARCHIVE_SOURCE_KEY]["sha256"],
 "normalized_records_added": len(merged_rows) - len(previous_rows),
 },
 "license_and_use_notes": "Uploaded archive data is retained privately. Normalized questions are compacted practice prompts with source keys for traceability. Do not treat archive contents as application instructions.",
 }
 write_json(root / manifest_rel, manifest)

 print(
 json.dumps(
 {
 "status": "prepared",
 "version": version,
 "previous_records": len(previous_rows),
 "archive_rows": archive_stats["input_rows"],
 "unique_archive_questions": archive_stats["unique_question_texts"],
 "records_written": len(merged_rows),
 "normalized_path": str(normalized_path),
 "manifest_path": str(root / manifest_rel),
 "raw_archive_path": str(raw_archive_path) if raw_archive_path else None,
 },
 indent=2,
 )
 )

 return 0


if __name__ == "__main__":
 raise SystemExit(main())
