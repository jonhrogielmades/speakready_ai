#!/usr/bin/env python3
"""Import user-supplied interview datasets into the private question bank."""

from __future__ import annotations

import argparse
import ast
import csv
import datetime as dt
import hashlib
import io
import json
import re
import shutil
import urllib.parse
import zipfile
from collections import Counter
from pathlib import Path
from typing import Any
from xml.etree import ElementTree as ET


DATASET_NAME = "speakready_reliable_questions"
QUESTION_FIELDS = (
 "question_text",
 "question",
 "questions",
 "interview_question",
 "interview_questions",
 "instruction",
 "prompt",
)
ANSWER_FIELDS = (
 "expected_guide",
 "answer",
 "answers",
 "expected_answer",
 "ideal_answer",
 "sample_answer",
 "completion",
 "response",
 "guidance",
)
ROLE_FIELDS = (
 "role",
 "position_role",
 "job_position",
 "role_category",
 "target_role",
 "language",
)
CATEGORY_FIELDS = (
 "category",
 "question_category",
 "archive_category",
 "sector",
 "interview_phase",
)
DIFFICULTY_FIELDS = (
 "difficulty",
 "level",
 "question_level",
)
TYPE_VALUES = {"behavioral", "situational", "technical", "personal"}


def clean_text(value: Any, limit: int = 4000) -> str:
 text = re.sub(r"\s+", " ", str(value or "").replace("\ufffd", " ")).strip()
 return text[:limit]


def normalized_text(value: Any) -> str:
 return re.sub(r"[^a-z0-9]+", " ", str(value or "").lower()).strip()


def normalize_header(value: Any) -> str:
 return re.sub(r"[^a-z0-9]+", "_", str(value or "").lstrip("\ufeff").lower()).strip("_")


def slugify(value: Any, fallback: str = "dataset") -> str:
 decoded = urllib.parse.unquote(str(value or ""))
 slug = re.sub(r"[^a-z0-9]+", "_", decoded.lower()).strip("_")
 return slug or fallback


def file_sha256(path: Path) -> str:
 digest = hashlib.sha256()
 with path.open("rb") as handle:
 for chunk in iter(lambda: handle.read(1024 * 1024), b""):
 digest.update(chunk)
 return digest.hexdigest()


def unique_path(path: Path) -> Path:
 if not path.exists():
 return path
 stem = path.stem
 suffix = path.suffix
 for index in range(2, 1000):
 candidate = path.with_name(f"{stem}_{index}{suffix}")
 if not candidate.exists():
 return candidate
 raise RuntimeError(f"Could not create unique path for {path}")


def open_text(path: Path) -> tuple[str, str]:
 for encoding in ("utf-8-sig", "utf-8", "cp1252", "latin-1"):
 try:
 return path.read_text(encoding=encoding), encoding
 except UnicodeDecodeError:
 continue
 return path.read_text(errors="replace"), "replace"


def detect_delimiter_from_text(text: str) -> str:
 for line in text.splitlines():
 if not line.strip():
 continue
 counts = {
 ",": line.count(","),
 ";": line.count(";"),
 "\t": line.count("\t"),
 "|": line.count("|"),
 }
 return max(counts, key=counts.get)
 return ","


def get_value(row: dict[str, Any], *keys: str) -> str:
 for key in keys:
 value = clean_text(row.get(key))
 if value:
 return value
 return ""


def split_list(value: Any) -> list[str]:
 if isinstance(value, list):
 return [clean_text(item, 120) for item in value if clean_text(item)]

 text = clean_text(value, 1200)
 if not text:
 return []

 try:
 parsed = ast.literal_eval(text)
 if isinstance(parsed, list):
 return [clean_text(item, 120) for item in parsed if clean_text(item)]
 except (SyntaxError, ValueError):
 pass

 return [item.strip() for item in re.split(r"\s*[;,]\s*", text) if item.strip()]


def title_from_slug(slug: str) -> str:
 words = [word for word in slug.split("_") if word]
 if not words:
 return "Uploaded Dataset"
 return " ".join(word.upper() if word in {"qa", "hr", "cvowl"} else word.title() for word in words)


def infer_type(question: str, category: str, role: str, answer: str) -> str:
 category_text = normalized_text(category)
 question_text = normalized_text(question)
 role_text = normalized_text(role)

 for type_value in TYPE_VALUES:
 if type_value in category_text:
 return type_value.title()

 technical_terms = (
 "technical",
 "data science",
 "machine learning",
 "software",
 "programming",
 "engineering",
 "quality assurance",
 "testing",
 "java",
 "python",
 "sql",
 "network",
 "security",
 "healthcare",
 "medical",
 )
 if any(term in category_text or term in role_text for term in technical_terms):
 return "Technical"
 if re.search(r"\b(suppose|scenario|what would|how would|if you|you are tasked|you've been tasked)\b", question_text):
 return "Situational"
 if answer and any(term in question_text for term in ("define", "explain", "difference", "what is", "how can")):
 return "Technical"
 return "Behavioral"


def infer_difficulty(value: str) -> str:
 text = normalized_text(value)
 if not text:
 return "Medium"
 if any(term in text for term in ("easy", "beginner", "basic", "foundational", "level 1")):
 return "Easy"
 if any(term in text for term in ("hard", "advanced", "expert", "edge", "conflict", "level 3", "level 4", "level 5")):
 return "Hard"
 return "Medium"


def default_expected_guide(role: str, category: str) -> str:
 if role:
 return f"Frame the answer for the {role} role. Include a clear explanation, a practical example, and a concise takeaway."
 if category:
 return f"Frame the answer for this {category} interview prompt. Include a clear explanation, a practical example, and a concise takeaway."
 return "Give a clear interview answer with a practical example and a concise takeaway."


def mapped_skills(row: dict[str, Any], role: str, category: str, source_title: str) -> list[str]:
 skills: list[str] = []
 for key in ("mapped_skills", "keywords", "skill", "skills", "language", "sector", "question_category"):
 for item in split_list(row.get(key)):
 if item and item not in skills:
 skills.append(item)
 for item in (role, category, source_title):
 item = clean_text(item, 120)
 if item and item not in skills:
 skills.append(item)
 if "Career Readiness" not in skills:
 skills.append("Career Readiness")
 return skills[:12]


def read_csv_rows(path: Path) -> tuple[list[dict[str, Any]], dict[str, Any]]:
 text, encoding = open_text(path)
 delimiter = detect_delimiter_from_text(text)
 rows: list[dict[str, Any]] = []
 reader = csv.DictReader(io.StringIO(text), delimiter=delimiter)
 fieldnames = [normalize_header(field) for field in reader.fieldnames or []]
 reader.fieldnames = fieldnames
 for source_row in reader:
 rows.append({normalize_header(key): value for key, value in source_row.items()})
 return rows, {"encoding": encoding, "delimiter": delimiter, "headers": fieldnames}


def shared_strings(workbook: zipfile.ZipFile) -> list[str]:
 try:
 root = ET.fromstring(workbook.read("xl/sharedStrings.xml"))
 except KeyError:
 return []
 namespace = {"a": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}
 strings: list[str] = []
 for item in root.findall("a:si", namespace):
 strings.append("".join(node.text or "" for node in item.findall(".//a:t", namespace)))
 return strings


def worksheet_paths(workbook: zipfile.ZipFile) -> list[tuple[str, str]]:
 namespace = {
 "a": "http://schemas.openxmlformats.org/spreadsheetml/2006/main",
 "r": "http://schemas.openxmlformats.org/officeDocument/2006/relationships",
 }
 root = ET.fromstring(workbook.read("xl/workbook.xml"))
 relationships = ET.fromstring(workbook.read("xl/_rels/workbook.xml.rels"))
 relationship_map = {item.attrib["Id"]: item.attrib["Target"] for item in relationships}
 paths: list[tuple[str, str]] = []
 for sheet in root.findall(".//a:sheet", namespace):
 name = sheet.attrib.get("name", "Sheet")
 relationship_id = sheet.attrib.get("{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id")
 target = relationship_map.get(relationship_id or "", "")
 if target and not target.startswith("xl/"):
 target = f"xl/{target}"
 paths.append((name, target))
 return paths


def column_index(cell_ref: str) -> int:
 letters = "".join(char for char in cell_ref if char.isalpha())
 index = 0
 for char in letters:
 index = index * 26 + ord(char.upper()) - 64
 return max(0, index - 1)


def xlsx_cell_value(cell: ET.Element, strings: list[str]) -> str:
 namespace = "{http://schemas.openxmlformats.org/spreadsheetml/2006/main}"
 cell_type = cell.attrib.get("t")
 value = cell.find(f"{namespace}v")
 if value is None:
 inline = cell.find(f"{namespace}is")
 if inline is None:
 return ""
 return "".join(node.text or "" for node in inline.findall(f".//{namespace}t"))
 text = value.text or ""
 if cell_type == "s":
 try:
 return strings[int(text)]
 except (IndexError, ValueError):
 return text
 return text


def read_xlsx_rows(path: Path) -> tuple[list[dict[str, Any]], dict[str, Any]]:
 rows: list[dict[str, Any]] = []
 sheets: list[dict[str, Any]] = []
 with zipfile.ZipFile(path) as workbook:
 strings = shared_strings(workbook)
 namespace = "{http://schemas.openxmlformats.org/spreadsheetml/2006/main}"
 for sheet_name, sheet_path in worksheet_paths(workbook):
 if not sheet_path or sheet_path not in workbook.namelist():
 continue
 root = ET.fromstring(workbook.read(sheet_path))
 raw_rows: list[list[str]] = []
 for xml_row in root.findall(f".//{namespace}sheetData/{namespace}row"):
 values: list[str] = []
 for cell in xml_row.findall(f"{namespace}c"):
 index = column_index(cell.attrib.get("r", "A1"))
 while len(values) <= index:
 values.append("")
 values[index] = xlsx_cell_value(cell, strings)
 if any(clean_text(value) for value in values):
 raw_rows.append(values)
 if not raw_rows:
 continue
 headers = [normalize_header(value) for value in raw_rows[0]]
 sheet_rows = 0
 for raw_row in raw_rows[1:]:
 row = {
 headers[index] if index < len(headers) and headers[index] else f"col_{index + 1}": value
 for index, value in enumerate(raw_row)
 }
 row["_sheet"] = sheet_name
 rows.append(row)
 sheet_rows += 1
 sheets.append({"name": sheet_name, "rows": sheet_rows, "headers": headers})
 return rows, {"sheets": sheets}


def read_jsonl_rows(path: Path) -> tuple[list[dict[str, Any]], dict[str, Any]]:
 text, encoding = open_text(path)
 rows: list[dict[str, Any]] = []
 for line_no, line in enumerate(text.splitlines(), 1):
 if not line.strip():
 continue
 payload = json.loads(line)
 if isinstance(payload, dict):
 rows.append({normalize_header(key): value for key, value in payload.items()})
 else:
 rows.append({"prompt": str(payload), "_line": line_no})
 return rows, {"encoding": encoding}


def strip_markdown_heading(line: str) -> str:
 return re.sub(r"^\s*#+\s*", "", line).strip()


def read_ipynb_rows(path: Path) -> tuple[list[dict[str, Any]], dict[str, Any]]:
 notebook = json.loads(path.read_text(encoding="utf-8"))
 rows: list[dict[str, Any]] = []
 for cell_index, cell in enumerate(notebook.get("cells", []), 1):
 if cell.get("cell_type")!= "markdown":
 continue
 source = cell.get("source", "")
 text = "".join(source) if isinstance(source, list) else str(source)
 lines = [line.rstrip() for line in text.splitlines()]
 first_line = next((line for line in lines if line.strip()), "")
 if not first_line.lstrip().startswith("#"):
 continue
 question = strip_markdown_heading(first_line)
 if not is_question_like(question):
 continue
 answer = "\n".join(lines[lines.index(first_line) + 1:]).strip()
 answer = re.sub(r"!\[[^\]]*\]\([^)]+\)", "", answer)
 answer = re.sub(r"<img\b[^>]*>", "", answer, flags=re.IGNORECASE)
 rows.append({
 "question": question,
 "answer": answer,
 "role": "Data Scientist",
 "category": "Data Science",
 "_cell": cell_index,
 })
 return rows, {"cells": len(notebook.get("cells", []))}


def is_question_like(question: str) -> bool:
 text = clean_text(question, 500)
 if "?" in text:
 return True
 return bool(re.match(
 r"^(?:what|why|how|when|where|explain|describe|define|differentiate|distinguish|name|list|compare|contrast|outline|discuss|suppose|tell|give)\b",
 text,
 flags=re.IGNORECASE,
 ))


def read_source_rows(path: Path) -> tuple[list[dict[str, Any]], dict[str, Any]]:
 suffix = path.suffix.lower()
 if suffix == ".csv":
 return read_csv_rows(path)
 if suffix == ".xlsx":
 return read_xlsx_rows(path)
 if suffix == ".jsonl":
 return read_jsonl_rows(path)
 if suffix == ".ipynb":
 return read_ipynb_rows(path)
 return [], {"unsupported": True}


def source_question(row: dict[str, Any]) -> str:
 return get_value(row, *QUESTION_FIELDS)


def source_answer(row: dict[str, Any]) -> str:
 return get_value(row, *ANSWER_FIELDS)


def source_role(row: dict[str, Any]) -> str:
 return get_value(row, *ROLE_FIELDS)


def source_category(row: dict[str, Any], source_title: str) -> str:
 category = get_value(row, *CATEGORY_FIELDS)
 if category:
 if normalized_text(category) in TYPE_VALUES:
 return category.title()
 return category
 if "healthcare" in normalized_text(source_title):
 return "Healthcare"
 if "data science" in normalized_text(source_title):
 return "Data Science"
 return "Job Interview"


def normalize_row(row: dict[str, Any], source_key: str, source_title: str, provenance: str, row_number: int) -> dict[str, Any] | None:
 question = source_question(row)
 if not question:
 return None

 answer = source_answer(row)
 role = source_role(row)
 category = source_category(row, source_title)
 difficulty = infer_difficulty(get_value(row, *DIFFICULTY_FIELDS))
 question_type = get_value(row, "type", "question_type") or infer_type(question, category, role, answer)
 archive_roles = [role] if role else []

 return {
 "id": get_value(row, "record_id", "id", "qid") or f"{source_key}-{row_number:05d}",
 "dataset_key": "ph_job_interview",
 "category": category,
 "country": "General",
 "question_text": question,
 "type": question_type,
 "difficulty": difficulty,
 "expected_guide": answer or default_expected_guide(role, category),
 "mapped_skills": mapped_skills(row, role, category, source_title),
 "source_keys": [source_key],
 "provenance": provenance,
 "archive_category": get_value(row, "archive_category", "sector") or source_title,
 "archive_record_count": 0,
 "archive_roles": archive_roles,
 "archive_experience_levels": [],
 }


def latest_manifest(root: Path) -> Path:
 manifest_dir = root / "manifests"
 manifests: list[tuple[str, Path]] = []
 for path in manifest_dir.glob(f"{DATASET_NAME}_*.json"):
 match = re.match(rf"{DATASET_NAME}_(\d{{4}}-\d{{2}}-\d{{2}})\.json$", path.name)
 if match:
 manifests.append((match.group(1), path))
 if not manifests:
 raise SystemExit("No question-bank manifest found.")
 return sorted(manifests, reverse=True)[0][1]


def read_manifest_rows(root: Path, manifest: dict[str, Any]) -> list[dict[str, Any]]:
 rows: list[dict[str, Any]] = []
 for file_info in manifest.get("normalized_files", []):
 if not isinstance(file_info, dict) or not isinstance(file_info.get("path"), str):
 continue
 path = root / file_info["path"]
 if not path.is_file():
 continue
 file_format = clean_text(file_info.get("format")) or path.suffix.lstrip(".")
 if file_format.lower() == "csv":
 source_rows, _meta = read_csv_rows(path)
 for source_row in source_rows:
 normalized = normalize_row(source_row, "manifest_csv", "Manifest CSV", "manifest_csv", len(rows) + 1)
 if normalized:
 rows.append(normalized)
 else:
 with path.open("r", encoding="utf-8-sig") as handle:
 for line in handle:
 if not line.strip():
 continue
 row = json.loads(line)
 if isinstance(row, dict) and clean_text(row.get("question_text")):
 rows.append(row)
 return rows


def write_json(path: Path, payload: dict[str, Any]) -> None:
 path.parent.mkdir(parents=True, exist_ok=True)
 path.write_text(json.dumps(payload, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")


def main() -> int:
 parser = argparse.ArgumentParser()
 parser.add_argument("--datasets-root", default="storage/app/private/datasets")
 parser.add_argument("--manifest", default="")
 parser.add_argument("--version", default="2026-09-08")
 parser.add_argument("--normalized-name", default="uploaded_question_sources_2026-09-08.jsonl")
 parser.add_argument("sources", nargs="+")
 args = parser.parse_args()

 root = Path(args.datasets_root).resolve()
 manifest_path = Path(args.manifest) if args.manifest else latest_manifest(root)
 if not manifest_path.is_absolute():
 manifest_path = root / manifest_path
 manifest = json.loads(manifest_path.read_text(encoding="utf-8-sig"))

 source_index_rel = manifest["raw_source_index"]["path"]
 source_index_path = root / source_index_rel
 source_index = json.loads(source_index_path.read_text(encoding="utf-8-sig"))
 existing_sources = {
 clean_text(source.get("key")): source
 for source in source_index.get("sources", [])
 if isinstance(source, dict) and clean_text(source.get("key"))
 }

 upload_root = root / "raw" / "question_sources" / args.version / "uploads"
 upload_root.mkdir(parents=True, exist_ok=True)
 normalized_rel = f"normalized/questions/{args.version}/{args.normalized_name}"
 normalized_path = root / normalized_rel

 normalized_rows: list[dict[str, Any]] = []
 imported_sources: list[dict[str, Any]] = []
 skipped_sources: list[dict[str, Any]] = []
 used_keys: set[str] = set(existing_sources)

 for source_arg in args.sources:
 source_path = Path(source_arg)
 if not source_path.is_file():
 skipped_sources.append({"source_path": str(source_path), "reason": "missing"})
 continue

 source_slug = slugify(source_path.stem)
 extension_slug = slugify(source_path.suffix.lstrip("."), "file")
 source_key_base = f"{source_slug}_{extension_slug}"
 source_key = source_key_base
 suffix_index = 2
 while source_key in used_keys:
 source_key = f"{source_key_base}_{suffix_index}"
 suffix_index += 1
 used_keys.add(source_key)
 provenance = f"user_supplied_{source_key}"
 source_title = title_from_slug(source_slug)

 safe_name = f"{source_slug}{source_path.suffix.lower()}"
 raw_path = upload_root / safe_name
 if raw_path.exists() and file_sha256(raw_path)!= file_sha256(source_path):
 raw_path = unique_path(raw_path)
 shutil.copy2(source_path, raw_path)
 raw_rel = raw_path.relative_to(root).as_posix()

 source_rows, metadata = read_source_rows(source_path)
 question_rows: list[dict[str, Any]] = []
 for index, row in enumerate(source_rows, 1):
 normalized = normalize_row(row, source_key, source_title, provenance, index)
 if normalized:
 question_rows.append(normalized)
 normalized_rows.extend(question_rows)

 questions = [normalized_text(row["question_text"]) for row in question_rows if normalized_text(row.get("question_text"))]
 role_counter = Counter(
 role
 for row in question_rows
 for role in row.get("archive_roles", [])
 if clean_text(role)
 )
 category_counter = Counter(clean_text(row.get("category")) for row in question_rows if clean_text(row.get("category")))
 source_record = {
 "key": source_key,
 "name": source_title,
 "url": None,
 "file": raw_rel,
 "publisher": "User-supplied dataset",
 "source_type": f"uploaded_{extension_slug}_dataset",
 "reliability": "user_supplied_private_dataset",
 "rows": len(source_rows),
 "question_rows": len(question_rows),
 "unique_questions": len(set(questions)),
 "bytes": source_path.stat().st_size,
 "sha256": file_sha256(source_path),
 "metadata": metadata,
 }
 if role_counter:
 source_record["role_counts"] = [{"name": key, "count": value} for key, value in role_counter.most_common(20)]
 if category_counter:
 source_record["category_counts"] = [{"name": key, "count": value} for key, value in category_counter.most_common(20)]

 existing_sources[source_key] = source_record
 imported_sources.append(source_record)
 if not question_rows:
 skipped_sources.append({
 "source_path": str(source_path),
 "stored_path": raw_rel,
 "reason": "no_supported_question_columns",
 "headers": metadata.get("headers") or metadata.get("sheets"),
 })

 normalized_path.parent.mkdir(parents=True, exist_ok=True)
 with normalized_path.open("w", encoding="utf-8", newline="\n") as handle:
 for row in normalized_rows:
 handle.write(json.dumps(row, ensure_ascii=False, separators=(",", ":")) + "\n")

 source_index["retrieved_at_utc"] = dt.datetime.now(dt.timezone.utc).isoformat(timespec="seconds")
 source_index["sources"] = list(existing_sources.values())
 write_json(source_index_path, source_index)

 normalized_info = {
 "path": normalized_rel,
 "format": "jsonl",
 "sha256": file_sha256(normalized_path),
 "bytes": normalized_path.stat().st_size,
 }
 manifest["normalized_files"] = [
 file_info
 for file_info in manifest.get("normalized_files", [])
 if not (isinstance(file_info, dict) and file_info.get("path") == normalized_rel)
 ]
 manifest["normalized_files"].append(normalized_info)
 manifest["description"] = (
 "Question-only normalized practice dataset for SpeakReady AI. This version combines the HR interview bank, "
 "CSV exports, and user-supplied interview datasets, deduped by question text."
 )
 manifest["raw_source_index"]["sha256"] = file_sha256(source_index_path)
 manifest["raw_source_index"]["bytes"] = source_index_path.stat().st_size
 manifest["source_count"] = len(source_index["sources"])
 manifest["uploaded_question_sources"] = {
 "normalized_path": normalized_rel,
 "imported_at_utc": dt.datetime.now(dt.timezone.utc).isoformat(timespec="seconds"),
 "sources": [
 {
 "key": source["key"],
 "name": source["name"],
 "file": source["file"],
 "rows": source["rows"],
 "question_rows": source["question_rows"],
 "unique_questions": source["unique_questions"],
 }
 for source in imported_sources
 ],
 "skipped_sources": skipped_sources,
 }

 all_rows = read_manifest_rows(root, manifest)
 seen: set[str] = set()
 categories: set[str] = set()
 for row in all_rows:
 key = normalized_text(row.get("question_text"))
 if key:
 seen.add(key)
 category = clean_text(row.get("category"))
 if category:
 categories.add(category)
 manifest["records"] = len(seen)
 manifest["dataset_keys"] = ["ph_job_interview"]
 manifest["categories"] = sorted(categories)
 write_json(manifest_path, manifest)

 print(json.dumps({
 "status": "imported",
 "normalized_path": str(normalized_path),
 "normalized_rows": len(normalized_rows),
 "normalized_unique_questions": len({normalized_text(row["question_text"]) for row in normalized_rows}),
 "manifest_records": manifest["records"],
 "source_count": manifest["source_count"],
 "imported_sources": manifest["uploaded_question_sources"]["sources"],
 "skipped_sources": skipped_sources,
 }, ensure_ascii=False, indent=2))
 return 0


if __name__ == "__main__":
 raise SystemExit(main())
