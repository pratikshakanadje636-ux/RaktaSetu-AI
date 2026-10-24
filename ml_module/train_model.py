
import pandas as pd
from pathlib import Path

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error
from sklearn.model_selection import TimeSeriesSplit
import joblib


def main():
    folder = Path(__file__).resolve().parent
    data_file = folder / "demand_features.csv"
    model_file = folder / "demand_model.joblib"

    if not data_file.exists():
        print("ERROR: demand_features.csv not found.")
        return

    df = pd.read_csv(data_file)
    df["date"] = pd.to_datetime(df["date"], errors="coerce")
    df = df.dropna(subset=["date", "requested_units"])
    df = df.sort_values("date")

    unique_dates = df["date"].nunique()

    print("===== RaktaSetu Model Training =====")
    print("Rows:", len(df))
    print("Distinct dates:", unique_dates)

    # Require enough history before attempting model training.
    if unique_dates < 30:
        print("\nTRAINING SKIPPED.")
        print(
            "At least 30 distinct dates are required by this "
            "starter script. More history may be needed for "
            "reliable real-world predictions."
        )
        print("No model was trained or saved.")
        return

    features = [
        "day_of_week",
        "day_of_month",
        "month",
        "previous_day_demand",
        "previous_7_day_average",
    ]

    if "history_available" in df.columns:
        df = df[df["history_available"] == True].copy()

    if df.empty or df["date"].nunique() < 30:
        print("\nTRAINING SKIPPED: insufficient usable history.")
        return

    X = df[features]
    y = df["requested_units"]

    # Keep the latest dates separate for a basic time-ordered evaluation.
    dates = sorted(df["date"].unique())
    split_index = int(len(dates) * 0.8)
    train_dates = dates[:split_index]
    test_dates = dates[split_index:]

    if not train_dates or not test_dates:
        print("\nTRAINING SKIPPED: unable to create a time split.")
        return

    train_mask = df["date"].isin(train_dates)
    test_mask = df["date"].isin(test_dates)

    model = RandomForestRegressor(
        n_estimators=100,
        random_state=42
    )
    model.fit(X.loc[train_mask], y.loc[train_mask])

    predictions = model.predict(X.loc[test_mask])
    mae = mean_absolute_error(y.loc[test_mask], predictions)

    print(f"\nHoldout MAE: {mae:.3f} units")
    print("Evaluation uses later dates held out from training.")

    # Train final model on all usable history after evaluation.
    final_model = RandomForestRegressor(
        n_estimators=100,
        random_state=42
    )
    final_model.fit(X, y)

    joblib.dump(
        {"model": final_model, "features": features},
        model_file
    )

    print("\nModel saved to:", model_file)


if __name__ == "__main__":
    main()
