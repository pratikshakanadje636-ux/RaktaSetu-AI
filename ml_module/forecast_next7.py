
import pandas as pd
from pathlib import Path
import joblib


def main():
    folder = Path(__file__).resolve().parent

    data_file = folder / "demand_features.csv"
    model_file = folder / "demand_model.joblib"
    output_file = folder / "forecast_next7_days.csv"

    if not data_file.exists() or not model_file.exists():
        print("ERROR: Training data or trained model not found.")
        print("Run build_features.py and train_model.py first.")
        return

    df = pd.read_csv(data_file)
    df["date"] = pd.to_datetime(df["date"], errors="coerce")
    df = df.dropna(subset=["date", "requested_units"])

    required_columns = [
        "date",
        "blood_group",
        "requested_units",
    ]

    missing = [
        column for column in required_columns
        if column not in df.columns
    ]

    if missing:
        print("ERROR: Missing columns:", missing)
        print("Check the columns in demand_features.csv.")
        return

    saved_model = joblib.load(model_file)
    model = saved_model["model"]
    features = saved_model["features"]

    df = df.sort_values("date")
    last_date = df["date"].max()

    forecast_rows = []

    for blood_group, group in df.groupby("blood_group"):
        group = group.sort_values("date")

        history = group["requested_units"].astype(float).tolist()

        if not history:
            continue

        for day in range(1, 8):
            forecast_date = last_date + pd.Timedelta(days=day)

            previous_day = history[-1]
            previous_7_day_average = sum(history[-7:]) / min(
                len(history), 7
            )

            row = {
                "day_of_week": forecast_date.dayofweek,
                "day_of_month": forecast_date.day,
                "month": forecast_date.month,
                "previous_day_demand": previous_day,
                "previous_7_day_average": previous_7_day_average,
            }

            input_data = pd.DataFrame([row])[features]
            prediction = float(model.predict(input_data)[0])

            # Demand cannot be negative.
            prediction = max(0.0, prediction)

            forecast_rows.append({
                "date": forecast_date.strftime("%Y-%m-%d"),
                "blood_group": blood_group,
                "predicted_demand_units": round(prediction, 2),
                "data_source": "Synthetic demo data",
            })

            # Use the forecast as input for the next forecast day.
            history.append(prediction)

    if not forecast_rows:
        print("ERROR: No forecasts generated.")
        return

    forecast_df = pd.DataFrame(forecast_rows)
    forecast_df.to_csv(output_file, index=False)

    print("\n===== RaktaSetu: 7-Day Demand Forecast =====")
    print("Historical data ends:", last_date.strftime("%Y-%m-%d"))
    print("Forecast period:")
    print(forecast_df["date"].min(), "to", forecast_df["date"].max())
    print("\nIMPORTANT: Predictions use synthetic demo data.")
    print("They are NOT validated real-world blood demand forecasts.\n")

    print(forecast_df.to_string(index=False))
    print("\nForecast saved to:", output_file)


if __name__ == "__main__":
    main()
