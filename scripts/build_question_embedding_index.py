#!/usr/bin/env python3
"""Build a private question embedding database for SpeakReady AI."""

from __future__ import annotations

import argparse
import csv
import datetime as dt
import hashlib
import json
import math
import re
from pathlib import Path
from typing import Any


DATASET_NAME = "speakready_reliable_questions"
DEFAULT_MODEL = "sentence-transformers/all-MiniLM-L6-v2"
LEXICAL_DIMENSIONS = 384
QUESTION_TYPES = {"behavioral", "situational", "technical", "personal", "role fit"}
DIFFICULTIES = {"easy", "medium", "hard"}


def clean_text(value: Any, limit: int = 4000) -> str:
    return re.sub(r"\s+", " ", str(value or "")).strip()[:limit]


def normalized_text(value: Any) -> str:
    return re.sub(r"[^a-z0-9]+", " ", str(value or "").lower()).strip()


def tokens(value: Any) -> list[str]:
    return re.findall(r"[a-z0-9][a-z0-9']{1,}", str(value or "").lower())


def title_value(value: Any, allowed: set[str], fallback: str) -> str:
    normalized = normalized_text(value)
    if normalized in allowed:
        return normalized.title()
    return fallback


def file_sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def combined_sha256(paths: list[Path]) -> str:
    digest = hashlib.sha256()
    for path in paths:
        digest.update(str(path).encode("utf-8"))
        digest.update(file_sha256(path).encode("utf-8"))
    return digest.hexdigest()


def latest_normalized_question_files(root: Path) -> list[Path]:
    manifest_dir = root / "manifests"
    if not manifest_dir.is_dir():
        return []

    candidates: list[tuple[str, Path]] = []
    for path in manifest_dir.glob(f"{DATASET_NAME}_*.json"):
        match = re.match(rf"{re.escape(DATASET_NAME)}_(\d{{4}}-\d{{2}}-\d{{2}})\.json$", path.name)
        if match:
            candidates.append((match.group(1), path))

    for _, manifest_path in sorted(candidates, reverse=True):
        try:
            manifest = json.loads(manifest_path.read_text(encoding="utf-8-sig"))
        except json.JSONDecodeError:
            continue

        paths = []
        for file_info in manifest.get("normalized_files", []):
            rel_path = file_info.get("path") if isinstance(file_info, dict) else None
            if isinstance(rel_path, str) and (root / rel_path).is_file():
                paths.append(root / rel_path)
        if paths:
            return paths

    return []


def read_jsonl(path: Path) -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []
    with path.open("r", encoding="utf-8-sig") as handle:
        for line_no, raw_line in enumerate(handle, 1):
            line = raw_line.strip()
            if not line:
                continue
            try:
                row = json.loads(line)
            except json.JSONDecodeError as exc:
                raise SystemExit(f"Invalid JSONL at {path}:{line_no}: {exc}") from exc
            if isinstance(row, dict):
                rows.append(row)
    return rows


def normalize_header(value: Any) -> str:
    return re.sub(r"[^a-z0-9]+", "_", str(value or "").lstrip("\ufeff").lower()).strip("_")


def split_list(value: Any) -> list[str]:
    if isinstance(value, list):
        return [clean_text(item, 120) for item in value if clean_text(item, 120)]
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
            if line:
                counts = {",": line.count(","), ";": line.count(";"), "\t": line.count("\t")}
                return max(counts, key=counts.get)
    return ","


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
            category = csv_value(row, "category") or "Job Interview"
            mapped_skills = split_list(row.get("mapped_skills")) or ([role, "Role Knowledge"] if role else [])
            archive_roles = split_list(row.get("archive_roles")) or ([role] if role else [])
            source_keys = split_list(row.get("source_keys")) or ["uploaded_hr_interview_questions_archive"]

            rows.append(
                {
                    "id": csv_value(row, "record_id", "id") or f"question-csv-{index:04d}",
                    "dataset_key": csv_value(row, "dataset_key") or "ph_job_interview",
                    "category": category,
                    "country": csv_value(row, "country") or "General",
                    "question_text": question,
                    "type": csv_value(row, "type") or ("Technical" if role and answer else "Behavioral"),
                    "difficulty": csv_value(row, "difficulty") or "Medium",
                    "expected_guide": csv_value(row, "expected_guide") or answer or "Give a clear answer with one practical example and a concise takeaway.",
                    "mapped_skills": mapped_skills,
                    "source_keys": source_keys,
                    "provenance": csv_value(row, "provenance") or "user_supplied_hr_archive_normalized",
                    "archive_category": csv_value(row, "archive_category") or category,
                    "archive_roles": archive_roles,
                    "archive_experience_levels": split_list(row.get("archive_experience_levels")),
                }
            )
    return rows


def read_question_rows(path: Path) -> list[dict[str, Any]]:
    return read_csv_rows(path) if path.suffix.lower() == ".csv" else read_jsonl(path)


def normalize_list(value: Any, limit: int = 12) -> list[str]:
    return [clean_text(item, 160) for item in split_list(value) if clean_text(item, 160)][:limit]


def source_text(row: dict[str, Any]) -> str:
    parts = [
        "question:",
        clean_text(row.get("question_text"), 1200),
        "expected guide:",
        clean_text(row.get("expected_guide"), 1200),
        "category:",
        clean_text(row.get("category"), 200),
        clean_text(row.get("archive_category"), 200),
        "type:",
        clean_text(row.get("type"), 80),
        "difficulty:",
        clean_text(row.get("difficulty"), 80),
        "skills:",
        " ".join(normalize_list(row.get("mapped_skills"), 16)),
        "roles:",
        " ".join(normalize_list(row.get("archive_roles"), 16)),
        "experience:",
        " ".join(normalize_list(row.get("archive_experience_levels"), 8)),
    ]
    return clean_text(" ".join(parts), 5000)


def cleaned_records(rows: list[dict[str, Any]], max_rows: int = 0) -> list[dict[str, Any]]:
    records: list[dict[str, Any]] = []
    seen: set[str] = set()

    for index, row in enumerate(rows, 1):
        question = clean_text(row.get("question_text"), 1200)
        key = normalized_text(question)
        if not question or key in seen:
            continue
        seen.add(key)

        record = {
            "id": clean_text(row.get("id")) or f"question-{index}",
            "dataset_key": clean_text(row.get("dataset_key")) or "ph_job_interview",
            "category": clean_text(row.get("category")) or "Job Interview",
            "country": clean_text(row.get("country")) or "General",
            "question_text": question,
            "type": title_value(row.get("type"), QUESTION_TYPES, "Behavioral"),
            "difficulty": title_value(row.get("difficulty"), DIFFICULTIES, "Medium"),
            "expected_guide": clean_text(row.get("expected_guide"), 1600),
            "mapped_skills": normalize_list(row.get("mapped_skills"), 12),
            "source_keys": normalize_list(row.get("source_keys"), 12),
            "provenance": clean_text(row.get("provenance"), 160),
            "archive_category": clean_text(row.get("archive_category"), 200),
            "archive_roles": normalize_list(row.get("archive_roles"), 16),
            "processed_text": "",
        }
        record["processed_text"] = source_text(record)
        records.append(record)

        if max_rows > 0 and len(records) >= max_rows:
            break

    return records


def normalize_vector(vector: list[float]) -> list[float]:
    magnitude = math.sqrt(sum(value * value for value in vector))
    if magnitude <= 0:
        return vector
    return [round(value / magnitude, 8) for value in vector]


def lexical_vector(text: str, dimensions: int = LEXICAL_DIMENSIONS) -> list[float]:
    vector = [0.0] * dimensions
    terms = tokens(text)
    terms += ["_".join(terms[index : index + 2]) for index in range(max(0, len(terms) - 1))]

    for term in terms[:1200]:
        digest = hashlib.sha1(term.encode("utf-8")).hexdigest()
        bucket = int(digest[:8], 16) % dimensions
        sign = 1.0 if int(digest[8:10], 16) % 2 == 0 else -1.0
        vector[bucket] += sign

    return normalize_vector(vector)


def sentence_bert_vectors(documents: list[str], model_name: str) -> list[list[float]]:
    try:
        from sentence_transformers import SentenceTransformer  # type: ignore
    except Exception as exc:
        raise SystemExit(
            "Sentence-BERT backend requires sentence-transformers. "
            f"Original import error: {exc}"
        ) from exc

    model = SentenceTransformer(model_name)
    encoded = model.encode(documents, batch_size=32, convert_to_numpy=True, normalize_embeddings=True)
    return [[round(float(value), 8) for value in vector] for vector in encoded]


def build_index(input_paths: list[Path], output_path: Path, backend: str, model_name: str, max_rows: int) -> dict[str, Any]:
    rows: list[dict[str, Any]] = []
    for input_path in input_paths:
        rows.extend(read_question_rows(input_path))

    records = cleaned_records(rows, max_rows)
    if not records:
        raise SystemExit("No question records found after data cleaning.")

    documents = [record["processed_text"] for record in records]
    if backend == "sentence-bert":
        embeddings = sentence_bert_vectors(documents, model_name)
        embedding_model = model_name
    elif backend == "lexical_hash":
        embeddings = [lexical_vector(document) for document in documents]
        embedding_model = "local_lexical_hash_384"
    else:
        raise SystemExit(f"Unsupported backend: {backend}")

    for record, embedding in zip(records, embeddings):
        record["embedding"] = embedding

    artifact = {
        "schema_version": 1,
        "database_type": "question_embedding_database",
        "pipeline": [
            "HR Interview Dataset",
            "Data Cleaning",
            "Role + Category + Difficulty Processing",
            "Sentence-BERT Encoding" if backend == "sentence-bert" else "Lexical Hash Encoding",
            "Question Embedding Database",
        ],
        "embedding_backend": backend,
        "embedding_model": embedding_model,
        "embedding_dimensions": len(embeddings[0]) if embeddings else 0,
        "created_at_utc": dt.datetime.now(dt.timezone.utc).isoformat(timespec="seconds"),
        "source_dataset_path": str(input_paths[0]) if len(input_paths) == 1 else None,
        "source_dataset_paths": [str(path) for path in input_paths],
        "source_dataset_sha256": file_sha256(input_paths[0]) if len(input_paths) == 1 else combined_sha256(input_paths),
        "records": records,
    }

    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(artifact, ensure_ascii=False, separators=(",", ":")) + "\n", encoding="utf-8")
    return artifact


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--datasets-root", default="storage/app/private/datasets")
    parser.add_argument("--input", default="")
    parser.add_argument("--output", default="embeddings/questions/latest/question_embeddings.json")
    parser.add_argument("--backend", choices=["sentence-bert", "lexical_hash"], default="sentence-bert")
    parser.add_argument("--model", default=DEFAULT_MODEL)
    parser.add_argument("--max-rows", type=int, default=0)
    args = parser.parse_args()

    root = Path(args.datasets_root).resolve()
    if args.input:
        input_path = Path(args.input)
        input_paths = [input_path if input_path.is_absolute() else root / input_path]
    else:
        input_paths = latest_normalized_question_files(root)

    missing_paths = [path for path in input_paths if not path.is_file()]
    if not input_paths or missing_paths:
        missing = ", ".join(str(path) for path in missing_paths) or "(none found)"
        raise SystemExit(f"Normalized question dataset not found: {missing}")

    output_path = Path(args.output)
    if not output_path.is_absolute():
        output_path = root / output_path

    resolved_inputs = [path.resolve() for path in input_paths]
    artifact = build_index(resolved_inputs, output_path.resolve(), args.backend, args.model, max(0, args.max_rows))
    print(
        json.dumps(
            {
                "status": "indexed",
                "backend": artifact["embedding_backend"],
                "model": artifact["embedding_model"],
                "records": len(artifact["records"]),
                "dimensions": artifact["embedding_dimensions"],
                "inputs": [str(path) for path in resolved_inputs],
                "output": str(output_path.resolve()),
            },
            indent=2,
        )
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
