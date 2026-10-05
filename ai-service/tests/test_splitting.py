import pandas as pd

from app.splitting import temporal_split


def test_temporal_split_keeps_whole_matches_together():
    rows = []
    for match_id in range(1, 11):
        for player_id in (1, 2):
            rows.append({
                "match_id": match_id,
                "innings_number": 1,
                "scheduled_at": pd.Timestamp(f"2026-01-{match_id:02d}", tz="UTC"),
                "player_id": player_id,
            })

    frame = pd.DataFrame(rows)
    split = temporal_split(frame)

    train_ids = set(split.train["match_id"])
    val_ids = set(split.validation["match_id"])
    test_ids = set(split.test["match_id"])

    assert train_ids.isdisjoint(val_ids)
    assert train_ids.isdisjoint(test_ids)
    assert val_ids.isdisjoint(test_ids)
    assert max(train_ids) < min(val_ids)
    assert max(val_ids) < min(test_ids)
