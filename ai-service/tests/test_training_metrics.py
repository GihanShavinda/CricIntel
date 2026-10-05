import pandas as pd

from app.training import ModelTrainer


def test_metrics_return_mae_rmse_and_r2():
    metrics = ModelTrainer._metrics(
        pd.Series([10.0, 20.0, 30.0]),
        [12.0, 18.0, 29.0],
    )

    assert metrics["mae"] == 1.6667
    assert metrics["rmse"] is not None
    assert metrics["r2"] is not None
