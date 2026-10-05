from __future__ import annotations

import json
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import joblib

from .config import settings


@dataclass
class LoadedModel:
    pipeline: Any
    metadata: dict[str, Any]


class ModelRegistry:
    def __init__(self, base_dir: Path | None = None) -> None:
        self.base_dir = base_dir or settings.model_path
        self.base_dir.mkdir(parents=True, exist_ok=True)

    def save(
        self,
        organization_id: int,
        model_kind: str,
        pipeline: Any,
        metadata: dict[str, Any],
    ) -> dict[str, Any]:
        stamp = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
        version = f"{model_kind}-{stamp}"
        folder = self.base_dir / str(organization_id) / model_kind / version
        folder.mkdir(parents=True, exist_ok=True)

        joblib.dump(pipeline, folder / "model.joblib")

        metadata = {
            **metadata,
            "organization_id": organization_id,
            "model_kind": model_kind,
            "version": version,
            "created_at": datetime.now(timezone.utc).isoformat(),
        }

        (folder / "metadata.json").write_text(
            json.dumps(metadata, indent=2, default=str),
            encoding="utf-8",
        )

        latest_file = self.base_dir / str(organization_id) / model_kind / "latest.json"
        latest_file.write_text(
            json.dumps({"version": version}, indent=2),
            encoding="utf-8",
        )

        return metadata

    def load_latest(self, organization_id: int, model_kind: str) -> LoadedModel:
        model_root = self.base_dir / str(organization_id) / model_kind
        latest_file = model_root / "latest.json"

        if not latest_file.exists():
            raise FileNotFoundError(
                f"No trained {model_kind} model exists for organization {organization_id}."
            )

        version = json.loads(latest_file.read_text(encoding="utf-8"))["version"]
        folder = model_root / version
        pipeline = joblib.load(folder / "model.joblib")
        metadata = json.loads((folder / "metadata.json").read_text(encoding="utf-8"))

        return LoadedModel(pipeline=pipeline, metadata=metadata)

    def list_versions(self, organization_id: int, model_kind: str) -> list[dict[str, Any]]:
        root = self.base_dir / str(organization_id) / model_kind
        if not root.exists():
            return []

        rows = []
        for metadata_file in sorted(root.glob("*/metadata.json"), reverse=True):
            rows.append(json.loads(metadata_file.read_text(encoding="utf-8")))

        return rows
