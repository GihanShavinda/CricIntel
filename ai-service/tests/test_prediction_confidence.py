from app.prediction import PredictionService


def test_small_history_is_low_confidence():
    label = PredictionService._confidence_label(
        sample_size=3,
        half_width=20.0,
        prediction=30.0,
        test_mae=15.0,
    )

    assert label == "low"


def test_larger_tighter_history_can_be_higher_confidence():
    label = PredictionService._confidence_label(
        sample_size=15,
        half_width=5.0,
        prediction=40.0,
        test_mae=6.0,
    )

    assert label == "higher"
