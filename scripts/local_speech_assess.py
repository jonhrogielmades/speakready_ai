#!/usr/bin/env python3
"""Optional local speech assessment backend for SpeakReady AI.

The script is deliberately dependency-tolerant. It runs Whisper, Conformer/HF
ASR, wav2vec/HuBERT/WavLM-style CTC scoring, Montreal Forced Aligner, and GOP
only when those tools are installed and configured on the host machine.
"""

from __future__ import annotations

import argparse
import difflib
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path
from typing import Any, Optional

VERSION = 1


def component(name: str, status: str = "not_measured", **values: Any) -> dict[str, Any]:
    payload = {"name": name, "status": status}
    payload.update({key: value for key, value in values.items() if value is not None})
    return payload


def normalize_text(value: str) -> str:
    return " ".join(re.findall(r"[a-z0-9']+", value.lower()))


def bounded_text(value: Any, limit: int = 4000) -> str:
    return str(value or "").strip()[:limit]


def import_unavailable(name: str, package: str) -> dict[str, Any]:
    return component(
        name,
        "unavailable",
        reason=f"{package}_not_installed",
        setup=f"Install the local {package} package on this machine to enable {name}.",
    )


def run_whisper(args: argparse.Namespace) -> dict[str, Any]:
    try:
        import whisper  # type: ignore
    except Exception:
        return import_unavailable("asr", "whisper")

    model_name = args.asr_model or "base"
    try:
        model = whisper.load_model(model_name)
        options: dict[str, Any] = {"word_timestamps": True}
        if args.language:
            options["language"] = args.language
        result = model.transcribe(args.audio, **options)
    except Exception as error:
        return component("asr", "failed", provider="whisper", model=model_name, error=bounded_text(error, 800))

    segments = []
    for segment in result.get("segments", [])[:80]:
        segments.append(
            {
                "start": segment.get("start"),
                "end": segment.get("end"),
                "text": bounded_text(segment.get("text"), 500),
                "avg_logprob": segment.get("avg_logprob"),
                "no_speech_prob": segment.get("no_speech_prob"),
            }
        )

    return component(
        "asr",
        "measured",
        provider="whisper",
        model=model_name,
        transcript=bounded_text(result.get("text"), 20000),
        language=result.get("language"),
        segments=segments,
    )


def run_faster_whisper(args: argparse.Namespace) -> dict[str, Any]:
    try:
        from faster_whisper import WhisperModel  # type: ignore
    except Exception:
        return import_unavailable("asr", "faster_whisper")

    model_name = args.asr_model or "base"
    try:
        device = None if args.asr_device in ("", "auto") else args.asr_device
        model = WhisperModel(model_name, device=device or "auto")
        segments_iter, info = model.transcribe(args.audio, language=args.language or None, word_timestamps=True)
        segments_raw = list(segments_iter)
    except Exception as error:
        return component("asr", "failed", provider="faster_whisper", model=model_name, error=bounded_text(error, 800))

    transcript_parts = []
    segments = []
    for segment in segments_raw[:80]:
        text = bounded_text(getattr(segment, "text", ""), 500)
        transcript_parts.append(text)
        segments.append(
            {
                "start": getattr(segment, "start", None),
                "end": getattr(segment, "end", None),
                "text": text,
            }
        )

    return component(
        "asr",
        "measured",
        provider="faster_whisper",
        model=model_name,
        transcript=bounded_text(" ".join(transcript_parts), 20000),
        language=getattr(info, "language", None),
        segments=segments,
    )


def run_transformers_asr(args: argparse.Namespace, provider: str) -> dict[str, Any]:
    try:
        from transformers import pipeline  # type: ignore
    except Exception:
        return import_unavailable("asr", "transformers")

    model_name = args.asr_model
    if not model_name:
        model_name = "openai/whisper-base" if provider == "transformers" else "facebook/wav2vec2-conformer-rel-pos-large-960h-ft"

    try:
        pipe = pipeline("automatic-speech-recognition", model=model_name)
        try:
            result = pipe(args.audio, return_timestamps="word")
        except TypeError:
            result = pipe(args.audio)
    except Exception as error:
        return component("asr", "failed", provider=provider, model=model_name, error=bounded_text(error, 800))

    chunks = result.get("chunks", []) if isinstance(result, dict) else []
    segments = []
    for chunk in chunks[:120]:
        timestamp = chunk.get("timestamp") if isinstance(chunk, dict) else None
        segments.append(
            {
                "start": timestamp[0] if isinstance(timestamp, (list, tuple)) and len(timestamp) > 0 else None,
                "end": timestamp[1] if isinstance(timestamp, (list, tuple)) and len(timestamp) > 1 else None,
                "text": bounded_text(chunk.get("text") if isinstance(chunk, dict) else chunk, 200),
            }
        )

    return component(
        "asr",
        "measured",
        provider=provider,
        model=model_name,
        transcript=bounded_text(result.get("text") if isinstance(result, dict) else result, 20000),
        segments=segments,
    )


def run_asr(args: argparse.Namespace) -> dict[str, Any]:
    backend = (args.asr_backend or "none").lower().replace("-", "_")
    if backend in ("none", "disabled", "off"):
        return component("asr", "not_measured", reason="asr_disabled")
    if backend in ("whisper", "openai_whisper"):
        return run_whisper(args)
    if backend == "faster_whisper":
        return run_faster_whisper(args)
    if backend in ("transformers", "huggingface"):
        return run_transformers_asr(args, "transformers")
    if backend == "conformer":
        return run_transformers_asr(args, "conformer")

    return component("asr", "unavailable", reason="unsupported_asr_backend", provider=backend)


def run_pronunciation(args: argparse.Namespace, reference_text: str, asr_transcript: str) -> dict[str, Any]:
    backend = (args.pronunciation_backend or "none").lower().replace("-", "_")
    if backend in ("none", "disabled", "off"):
        return component("pronunciation", "not_measured", reason="pronunciation_disabled")
    if reference_text.strip() == "":
        return component("pronunciation", "not_measured", reason="reference_text_missing")

    try:
        from transformers import pipeline  # type: ignore
    except Exception:
        return import_unavailable("pronunciation", "transformers")

    model_name = args.pronunciation_model or "facebook/wav2vec2-base-960h"
    try:
        pipe = pipeline("automatic-speech-recognition", model=model_name)
        result = pipe(args.audio)
    except Exception as error:
        return component("pronunciation", "failed", provider=backend, model=model_name, error=bounded_text(error, 800))

    hypothesis = bounded_text(result.get("text") if isinstance(result, dict) else result, 20000)
    reference_norm = normalize_text(reference_text)
    hypothesis_norm = normalize_text(hypothesis)
    agreement = difflib.SequenceMatcher(None, reference_norm, hypothesis_norm).ratio() if reference_norm else 0.0
    score = int(round(agreement * 100))

    reference_words = reference_norm.split()
    hypothesis_words = hypothesis_norm.split()
    word_scores = []
    matcher = difflib.SequenceMatcher(None, reference_words, hypothesis_words)
    for tag, i1, i2, j1, j2 in matcher.get_opcodes()[:120]:
        words = reference_words[i1:i2]
        if not words and j1 < j2:
            words = hypothesis_words[j1:j2]
        for word in words:
            word_scores.append({"word": word, "score": 100 if tag == "equal" else 45 if tag == "replace" else 20, "match_type": tag})

    return component(
        "pronunciation",
        "measured",
        provider=backend,
        model=model_name,
        score=score,
        confidence=min(95, max(20, score)),
        method="local_ctc_transcript_agreement",
        hypothesis=hypothesis,
        reference=bounded_text(reference_text, 2000),
        word_scores=word_scores[:120],
        limitation=(
            "This uses a local CTC speech model such as wav2vec 2.0, HuBERT, or WavLM as a pronunciation proxy. "
            "For phoneme-level pronunciation, enable MFA alignment plus a true GOP backend."
        ),
    )


def ensure_wav_for_mfa(args: argparse.Namespace, corpus_dir: Path) -> Optional[Path]:
    source = Path(args.audio)
    target = corpus_dir / "utterance.wav"
    if source.suffix.lower() == ".wav":
        shutil.copyfile(source, target)
        return target

    ffmpeg = args.ffmpeg_command or "ffmpeg"
    if not shutil.which(ffmpeg):
        return None

    command = [ffmpeg, "-y", "-i", str(source), "-ar", "16000", "-ac", "1", str(target)]
    completed = subprocess.run(command, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
    return target if completed.returncode == 0 and target.exists() else None


def parse_textgrid(path: Path) -> tuple[list[dict[str, Any]], list[dict[str, Any]]]:
    words: list[dict[str, Any]] = []
    phones: list[dict[str, Any]] = []
    current_tier = ""
    interval: dict[str, Any] = {}

    for raw_line in path.read_text(encoding="utf-8", errors="ignore").splitlines():
        line = raw_line.strip()
        name_match = re.match(r'name\s*=\s*"([^"]*)"', line)
        if name_match:
            current_tier = name_match.group(1).lower()
            continue

        xmin = re.match(r'xmin\s*=\s*([0-9.]+)', line)
        xmax = re.match(r'xmax\s*=\s*([0-9.]+)', line)
        text = re.match(r'text\s*=\s*"(.*)"', line)
        if xmin:
            interval["start"] = float(xmin.group(1))
        elif xmax:
            interval["end"] = float(xmax.group(1))
        elif text:
            label = text.group(1).strip()
            if label and label.lower() not in {"sp", "sil", "<eps>"} and "start" in interval and "end" in interval:
                item = {"start": interval["start"], "end": interval["end"], "label": label}
                if "phone" in current_tier or "phoneme" in current_tier:
                    phones.append(item)
                elif "word" in current_tier:
                    words.append(item)
            interval = {}

    return words[:300], phones[:600]


def run_mfa_alignment(args: argparse.Namespace, reference_text: str) -> dict[str, Any]:
    if not reference_text.strip():
        return component("forced_alignment", "not_measured", reason="reference_text_missing")
    if not args.mfa_dictionary or not args.mfa_acoustic_model:
        return component(
            "forced_alignment",
            "unavailable",
            provider="mfa",
            reason="mfa_models_not_configured",
            setup="Set MFA_DICTIONARY and MFA_ACOUSTIC_MODEL to enable Montreal Forced Aligner.",
        )
    if not shutil.which(args.mfa_command):
        return component(
            "forced_alignment",
            "unavailable",
            provider="mfa",
            reason="mfa_command_not_found",
            setup="Install Montreal Forced Aligner and set MFA_COMMAND if it is not on PATH.",
        )

    with tempfile.TemporaryDirectory(prefix="speakready_mfa_") as temp_name:
        temp_dir = Path(temp_name)
        corpus_dir = temp_dir / "corpus"
        output_dir = temp_dir / "aligned"
        corpus_dir.mkdir()
        output_dir.mkdir()
        wav_path = ensure_wav_for_mfa(args, corpus_dir)
        if wav_path is None:
            return component(
                "forced_alignment",
                "unavailable",
                provider="mfa",
                reason="ffmpeg_required_for_audio_conversion",
                setup="Install ffmpeg or upload WAV audio for MFA alignment.",
            )
        (corpus_dir / "utterance.lab").write_text(reference_text, encoding="utf-8")

        command = [
            args.mfa_command,
            "align",
            str(corpus_dir),
            args.mfa_dictionary,
            args.mfa_acoustic_model,
            str(output_dir),
            "--clean",
            "--overwrite",
            "--quiet",
        ]
        completed = subprocess.run(command, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=max(20, args.timeout))
        if completed.returncode != 0:
            return component(
                "forced_alignment",
                "failed",
                provider="mfa",
                error=bounded_text(completed.stderr or completed.stdout, 1200),
            )

        textgrids = list(output_dir.rglob("*.TextGrid"))
        if not textgrids:
            return component("forced_alignment", "failed", provider="mfa", reason="textgrid_missing")

        words, phones = parse_textgrid(textgrids[0])
        return component(
            "forced_alignment",
            "measured",
            provider="mfa",
            word_alignments=words,
            phoneme_alignments=phones,
            textgrid_file=str(textgrids[0]),
        )


def run_alignment(args: argparse.Namespace, reference_text: str) -> dict[str, Any]:
    backend = (args.alignment_backend or "none").lower().replace("-", "_")
    if backend in ("none", "disabled", "off"):
        return component("forced_alignment", "not_measured", reason="alignment_disabled")
    if backend == "mfa":
        return run_mfa_alignment(args, reference_text)
    return component("forced_alignment", "unavailable", reason="unsupported_alignment_backend", provider=backend)


def run_gop(args: argparse.Namespace, reference_text: str, alignment: dict[str, Any], pronunciation: dict[str, Any]) -> dict[str, Any]:
    backend = (args.gop_backend or "none").lower().replace("-", "_")
    if backend in ("none", "disabled", "off"):
        return component("gop", "not_measured", reason="gop_disabled")

    if args.gop_command:
        try:
            with tempfile.NamedTemporaryFile("w", suffix=".txt", delete=False, encoding="utf-8") as ref_file:
                ref_file.write(reference_text)
                ref_path = ref_file.name
            command = args.gop_command.format(audio=args.audio, reference_file=ref_path)
            completed = subprocess.run(command, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, shell=True, timeout=max(20, args.timeout))
            if completed.returncode != 0:
                return component("gop", "failed", provider="custom", error=bounded_text(completed.stderr or completed.stdout, 1200))
            decoded = json.loads(completed.stdout)
            if isinstance(decoded, dict):
                decoded.setdefault("name", "gop")
                decoded.setdefault("status", "measured")
                decoded.setdefault("provider", "custom")
                return decoded
        except Exception as error:
            return component("gop", "failed", provider="custom", error=bounded_text(error, 800))
        finally:
            try:
                os.unlink(ref_path)  # type: ignore[name-defined]
            except Exception:
                pass

    if backend in ("mfa", "phoneme_gop") and alignment.get("status") == "measured":
        return component(
            "gop",
            "unavailable",
            provider=backend,
            reason="gop_backend_not_configured",
            setup="Set LOCAL_GOP_COMMAND to a local GOP scorer that returns JSON with score and phoneme_scores.",
            limitation="MFA provides phoneme alignment, but it does not by itself return a calibrated GOP score.",
        )

    if backend == "ctc_proxy" and pronunciation.get("status") == "measured":
        return component(
            "gop",
            "measured",
            provider="ctc_proxy",
            score=pronunciation.get("score"),
            method="ctc_pronunciation_proxy_not_clinical_gop",
            limitation="This is a CTC transcript-agreement proxy, not a true phoneme log-likelihood GOP score.",
        )

    return component("gop", "not_measured", provider=backend, reason="gop_requires_alignment_or_custom_command")


def reliability(asr: dict[str, Any], pronunciation: dict[str, Any], alignment: dict[str, Any], phoneme_alignment: dict[str, Any], gop: dict[str, Any]) -> dict[str, Any]:
    components = []
    for name, item in [
        ("asr", asr),
        ("pronunciation", pronunciation),
        ("forced_alignment", alignment),
        ("phoneme_alignment", phoneme_alignment),
        ("gop", gop),
    ]:
        if item.get("status") == "measured":
            components.append(name)

    score = min(95, len(components) * 18)
    if "asr" in components:
        score += 5
    if "gop" in components:
        score += 10
    score = min(95, score)
    band = "High" if score >= 85 else "Moderate" if score >= 65 else "Limited" if score > 0 else "Unavailable"

    return {"score": score, "band": band, "measured_components": components}


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser()
    parser.add_argument("--audio", required=True)
    parser.add_argument("--reference", default="")
    parser.add_argument("--language", default="")
    parser.add_argument("--timeout", type=int, default=90)
    parser.add_argument("--asr-backend", default="whisper")
    parser.add_argument("--asr-model", default="base")
    parser.add_argument("--asr-device", default="auto")
    parser.add_argument("--pronunciation-backend", default="ctc")
    parser.add_argument("--pronunciation-model", default="facebook/wav2vec2-base-960h")
    parser.add_argument("--alignment-backend", default="mfa")
    parser.add_argument("--mfa-command", default="mfa")
    parser.add_argument("--mfa-dictionary", default="")
    parser.add_argument("--mfa-acoustic-model", default="")
    parser.add_argument("--ffmpeg-command", default="ffmpeg")
    parser.add_argument("--gop-backend", default="mfa")
    parser.add_argument("--gop-command", default="")
    return parser


def main() -> int:
    try:
        args = build_parser().parse_args()

        if not Path(args.audio).exists():
            print(json.dumps({"version": VERSION, "status": "failed", "reason": "audio_missing"}))
            return 2

        asr = run_asr(args)
        asr_transcript = bounded_text(asr.get("transcript"), 20000)
        reference_text = bounded_text(args.reference or asr_transcript, 20000)
        pronunciation = run_pronunciation(args, reference_text, asr_transcript)
        alignment = run_alignment(args, reference_text)
        phoneme_alignment = component(
            "phoneme_alignment",
            "measured" if alignment.get("status") == "measured" and alignment.get("phoneme_alignments") else "not_measured",
            provider=alignment.get("provider"),
            phoneme_alignments=alignment.get("phoneme_alignments", []),
            reason=None if alignment.get("phoneme_alignments") else "phoneme_alignment_unavailable",
        )
        gop = run_gop(args, reference_text, alignment, pronunciation)
        reliability_payload = reliability(asr, pronunciation, alignment, phoneme_alignment, gop)
        status = "measured" if reliability_payload["score"] >= 65 else "partial" if reliability_payload["score"] > 0 else "not_measured"

        limitations = []
        for item in [asr, pronunciation, alignment, phoneme_alignment, gop]:
            for key in ("limitation", "setup", "error", "reason"):
                value = item.get(key)
                if value:
                    limitations.append(str(value))

        result = {
            "version": VERSION,
            "status": status,
            "asr": asr,
            "pronunciation": pronunciation,
            "forced_alignment": alignment,
            "phoneme_alignment": phoneme_alignment,
            "gop": gop,
            "reliability": reliability_payload,
            "limitations": limitations[:12],
            "recommendations": [
                "Use local ASR plus MFA/GOP only when audio quality is clear and the configured acoustic models match the speaker language."
            ],
        }
        print(json.dumps(result, ensure_ascii=False))
        return 0
    except Exception as error:
        print(json.dumps({
            "version": VERSION,
            "status": "failed",
            "reason": "local_speech_runtime_error",
            "error": bounded_text(error, 1200),
        }))
        return 1


if __name__ == "__main__":
    sys.exit(main())
