
import pandas as pd
from pathlib import Path

BLOOD_GROUPS = ["A+", "A-", "B+", "B-", "O+", "O-", "AB+", "AB-"]


def main():
    folder = Path(__file__).resolve().parent
    input_file = folder / "daily_demand.csv"
    output_file = folder / "demand_features.csv"

    if not input_file.exists():
        print("ERROR: daily_demand.csv was not found.")
        return

    df = pd.read_csv(input_file)

    required = {"date", "blood_group", "requested_units"}
    if not required.issubset(df.columns):
        print("ERROR: CSV is missing required columns:", required)
        return

    df["date"] = pd.to_datetime(df["date"], errors="coerce")
    df["blood_group"] = (
        df["blood_group"].astype(str).str.strip().str.upper()
    )
    df["requested_units"] = pd.to_numeric(
        df["requested_units"], errors="coerce"
    )

    df = df.dropna(
        subset=["date", "blood_group", "requested_units"]
    )

    df = df[
        df["blood_group"].isin(BLOOD_GROUPS)
        & (df["requested_units"] >= 0)
    ]

    df = df.sort_values(["blood_group", "date"])

    # Calendar features
    df["day_of_week"] = df["date"].dt.dayofweek
    df["day_of_month"] = df["date"].dt.day
    df["month"] = df["date"].dt.month

    # Historical features are calculated separately for each blood group.
    grouped = df.groupby("blood_group")["requested_units"]

    df["previous_day_demand"] = grouped.shift(1)

    df["previous_7_day_average"] = grouped.transform(
        lambda series: series.shift(1).rolling(
            window=7, min_periods=7
        ).mean()
    )

    df["previous_7_day_average"] = df[
        "previous_7_day_average"
    ].fillna(0)

    df["previous_day_demand"] = df[
        "previous_day_demand"
    ].fillna(0)

    df["history_available"] = (
        df.groupby("blood_group").cumcount() >= 7
    )

    df["date"] = df["date"].dt.strftime("%Y-%m-%d")

    df.to_csv(output_file, index=False)

    print("\n===== RaktaSetu Feature Preparation =====")
    print("Rows prepared:", len(df))
    print("Unique dates:", df["date"].nunique())
    print("\nFeature preview:")
    print(df.to_string(index=False))
    print("\nSaved to:", output_file)

    if df["date"].nunique() < 8:
        print(
            "\nWARNING: Fewer than 8 distinct dates are available. "
            "Historical features are not sufficient for training "
            "a reliable forecasting model."
        )
    else:
        print(
            "\nFeature file prepared. Data sufficiency and model "
            "performance still need to be evaluated."
        )


if __name__ == "__main__":
    main()
