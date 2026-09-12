#!/usr/bin/env python3
"""Serve the SpeakReady trained question reranker as a local warm service."""

from __future__ import annotations

import argparse
import json
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from typing import Any

from rerank_question_candidates import classify_texts, rerank


class RerankServer(ThreadingHTTPServer):
    daemon_threads = True

    def __init__(self, server_address: tuple[str, int], handler_class: type[BaseHTTPRequestHandler], model_path: Path, label_map_path: Path):
        super().__init__(server_address, handler_class)
        self.model_path = model_path
        self.label_map_path = label_map_path


class RerankHandler(BaseHTTPRequestHandler):
    server: RerankServer

    def log_message(self, format: str, *args: Any) -> None:
        return

    def do_GET(self) -> None:
        if self.path.rstrip("/") != "/health":
            self.send_json(404, {"status": "not_found"})
            return

        self.send_json(200, {
            "status": "ok",
            "model_path": str(self.server.model_path),
            "label_map_path": str(self.server.label_map_path),
        })

    def do_POST(self) -> None:
        if self.path.rstrip("/") != "/rerank":
            self.send_json(404, {"status": "not_found"})
            return

        try:
            length = int(self.headers.get("Content-Length", "0"))
        except ValueError:
            length = 0

        if length <= 0:
            self.send_json(400, {"status": "invalid_request", "reason": "empty request body"})
            return

        try:
            payload = json.loads(self.rfile.read(length).decode("utf-8"))
        except Exception as exc:
            self.send_json(400, {"status": "invalid_request", "reason": str(exc)})
            return

        if not isinstance(payload, dict):
            self.send_json(400, {"status": "invalid_request", "reason": "request body must be a JSON object"})
            return

        limit = int(payload.get("limit") or 1)
        payload.pop("limit", None)
        self.send_json(200, rerank(payload, self.server.model_path, self.server.label_map_path, max(1, limit)))

    def send_json(self, status: int, payload: dict[str, Any]) -> None:
        body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=8765)
    parser.add_argument("--model", required=True)
    parser.add_argument("--label-map", default="")
    parser.add_argument("--warm", action="store_true")
    args = parser.parse_args()

    model_path = Path(args.model).resolve()
    label_map_path = Path(args.label_map).resolve() if args.label_map else model_path / "speakready_question_labels.json"

    if args.warm:
        classify_texts(model_path, ["warm up the SpeakReady question reranker"])

    server = RerankServer((args.host, args.port), RerankHandler, model_path, label_map_path)
    print(json.dumps({
        "status": "serving",
        "host": args.host,
        "port": args.port,
        "model_path": str(model_path),
        "label_map_path": str(label_map_path),
        "warm": bool(args.warm),
    }), flush=True)
    server.serve_forever()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
