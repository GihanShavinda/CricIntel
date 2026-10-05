from __future__ import annotations

from dataclasses import dataclass

import pandas as pd


@dataclass
class TemporalSplit:
    train: pd.DataFrame
    validation: pd.DataFrame
    test: pd.DataFrame


def temporal_split(
    frame: pd.DataFrame,
    train_fraction: float = 0.70,
    validation_fraction: float = 0.15,
) -> TemporalSplit:
    """
    Chronological split by whole match, never by individual row.

    Every row from a match remains in the same partition. This prevents
    same-match leakage where one player's target from a match could enter
    training while another player's row from that match is evaluated.
    """
    if frame.empty:
        return TemporalSplit(frame.copy(), frame.copy(), frame.copy())

    ordered = frame.sort_values(
        ["scheduled_at", "match_id", "innings_number"],
        kind="stable",
    ).reset_index(drop=True)

    matches = (
        ordered[["match_id", "scheduled_at"]]
        .drop_duplicates("match_id")
        .sort_values(["scheduled_at", "match_id"], kind="stable")
        .reset_index(drop=True)
    )

    n_matches = len(matches)
    if n_matches < 3:
        # Training is later blocked by readiness thresholds; this keeps the
        # function deterministic for unit tests and forced experimentation.
        train_ids = set(matches.iloc[: max(1, n_matches - 2)]["match_id"].tolist())
        val_ids = set(matches.iloc[max(1, n_matches - 2): max(1, n_matches - 1)]["match_id"].tolist())
        test_ids = set(matches.iloc[max(1, n_matches - 1):]["match_id"].tolist())
    else:
        train_end = max(1, int(n_matches * train_fraction))
        val_end = max(train_end + 1, int(n_matches * (train_fraction + validation_fraction)))
        val_end = min(val_end, n_matches - 1)

        train_ids = set(matches.iloc[:train_end]["match_id"].tolist())
        val_ids = set(matches.iloc[train_end:val_end]["match_id"].tolist())
        test_ids = set(matches.iloc[val_end:]["match_id"].tolist())

    return TemporalSplit(
        train=ordered[ordered["match_id"].isin(train_ids)].copy(),
        validation=ordered[ordered["match_id"].isin(val_ids)].copy(),
        test=ordered[ordered["match_id"].isin(test_ids)].copy(),
    )
