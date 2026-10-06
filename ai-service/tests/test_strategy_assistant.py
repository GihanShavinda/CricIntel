import json

import pytest

from app.assistant import (
    SYSTEM_PROMPT,
    StrategyAssistantService,
)


def test_system_prompt_contains_grounding_rules():
    lowered = SYSTEM_PROMPT.lower()

    assert "never invent" in lowered
    assert "evidence id" in lowered
    assert "coach remains responsible" in lowered
    assert "only the structured cricintel context" in lowered


def test_json_parser_accepts_plain_json():
    payload = StrategyAssistantService._parse_json(
        json.dumps({
            "claims": [{
                "statement": "Supported claim",
                "evidence_ids": ["E0001"],
                "confidence": "low",
            }],
            "recommendations": [],
            "limitations": [],
        })
    )

    assert payload["claims"][0]["evidence_ids"] == ["E0001"]


def test_json_parser_accepts_json_fence():
    payload = StrategyAssistantService._parse_json(
        """```json
        {
          "claims": [],
          "recommendations": [],
          "limitations": ["Insufficient sample"]
        }
        ```"""
    )

    assert payload["limitations"] == ["Insufficient sample"]


def test_json_parser_rejects_non_json():
    with pytest.raises(RuntimeError):
        StrategyAssistantService._parse_json(
            "I think this batter is definitely going to score 100."
        )
