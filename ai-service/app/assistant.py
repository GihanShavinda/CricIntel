from __future__ import annotations

import json
import re
from typing import Any

import httpx

from .config import settings


SYSTEM_PROMPT = """
You are CricIntel's AI Strategy Assistant.

NON-NEGOTIABLE RULES:
1. Use ONLY the structured CricIntel context supplied in the request.
2. NEVER invent, estimate, infer, or fabricate cricket statistics that are not present.
3. NEVER claim certainty about a future cricket outcome.
4. Explicitly mention insufficient sample sizes where relevant.
5. The coach remains responsible for final decisions.
6. Every factual/statistical claim MUST cite one or more exact evidence IDs from context.evidence.
7. Do not use outside cricket knowledge as factual evidence.
8. Do not introduce player facts, venue facts, trends, averages, rates, rankings, or matchup records that are absent from the supplied context.
9. If the requested answer is unsupported, say that CricIntel does not currently contain enough evidence.
10. Return JSON only.

Required JSON shape:
{
  "claims": [
    {
      "statement": "Grounded statement using only supplied evidence.",
      "evidence_ids": ["E0001"],
      "confidence": "high|moderate|low"
    }
  ],
  "recommendations": [
    {
      "statement": "Advisory tactical recommendation grounded in cited CricIntel evidence.",
      "evidence_ids": ["E0002"],
      "confidence": "high|moderate|low"
    }
  ],
  "limitations": [
    "Important limitation or sample-size warning."
  ]
}

Do not return markdown.
""".strip()


class StrategyAssistantService:
    async def ask(
        self,
        question: str,
        context: dict[str, Any],
        deterministic_recommendations: list[dict[str, Any]],
        rules: list[str],
    ) -> dict[str, Any]:
        if not settings.strategy_assistant_enabled:
            raise RuntimeError(
                "The FastAPI strategy-assistant kill switch is active."
            )

        if settings.llm_provider == "disabled":
            raise RuntimeError(
                "LLM_PROVIDER is disabled. Configure an OpenAI-compatible provider first."
            )

        if settings.llm_provider != "openai_compatible":
            raise RuntimeError(
                f"Unsupported LLM_PROVIDER: {settings.llm_provider}"
            )

        if not settings.llm_model:
            raise RuntimeError("LLM_MODEL is not configured.")

        payload = {
            "model": settings.llm_model,
            "temperature": settings.llm_temperature,
            "max_tokens": settings.llm_max_tokens,
            "messages": [
                {
                    "role": "system",
                    "content": SYSTEM_PROMPT,
                },
                {
                    "role": "user",
                    "content": json.dumps(
                        {
                            "question": question,
                            "context": context,
                            "deterministic_recommendations": deterministic_recommendations,
                            "additional_rules": rules,
                        },
                        ensure_ascii=False,
                        separators=(",", ":"),
                    ),
                },
            ],
        }

        headers = {
            "Content-Type": "application/json",
        }
        if settings.llm_api_key:
            headers["Authorization"] = f"Bearer {settings.llm_api_key}"

        url = settings.llm_base_url.rstrip("/") + "/chat/completions"

        async with httpx.AsyncClient(
            timeout=settings.llm_timeout_seconds
        ) as client:
            response = await client.post(
                url,
                headers=headers,
                json=payload,
            )

        if response.status_code >= 400:
            raise RuntimeError(
                f"LLM provider returned HTTP {response.status_code}: "
                f"{response.text[:1000]}"
            )

        provider_payload = response.json()

        content = (
            provider_payload.get("choices", [{}])[0]
            .get("message", {})
            .get("content", "")
        )

        structured = self._parse_json(content)

        usage = provider_payload.get("usage") or {}

        return {
            "provider": settings.llm_provider,
            "model": settings.llm_model,
            "response": structured,
            "usage": {
                "prompt_tokens": usage.get("prompt_tokens"),
                "completion_tokens": usage.get("completion_tokens"),
            },
        }

    @staticmethod
    def _parse_json(content: str) -> dict[str, Any]:
        cleaned = content.strip()

        if cleaned.startswith("```"):
            cleaned = re.sub(
                r"^```(?:json)?\s*",
                "",
                cleaned,
                flags=re.IGNORECASE,
            )
            cleaned = re.sub(r"\s*```$", "", cleaned)

        try:
            payload = json.loads(cleaned)
        except json.JSONDecodeError as exc:
            raise RuntimeError(
                "The LLM did not return valid JSON."
            ) from exc

        if not isinstance(payload, dict):
            raise RuntimeError(
                "The LLM response root must be a JSON object."
            )

        payload.setdefault("claims", [])
        payload.setdefault("recommendations", [])
        payload.setdefault("limitations", [])

        return payload
