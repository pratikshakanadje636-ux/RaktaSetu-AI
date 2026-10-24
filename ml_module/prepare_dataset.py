
import mysql.connector
import pandas as pd

from pathlib import Path

DB_CONFIG = {
    "host": "127.0.0.1",
    "port": 3306,
    "user": "root",
    "password": "",
    "database": "new_blood_system",
    "connection_timeout": 5,
    "use_pure": True
}

BLOOD_GROUPS = ["A+", "A-", "B+", "B-", "O+", "O-", "AB+", "AB-"]


def fetch_dated_requests():
    connection = mysql.connector.connect(**DB_CONFIG)

    try:
        cursor = connection.cursor(dictionary=True)

        cursor.execute("""
            SELECT blood_group, units, request_date
            FROM `request`
            WHERE request_date IS NOT NULL
        """)

        return cursor.fetchall()

    finally:
        connection.close()


def main():
    print("\n===== RaktaSetu Dataset Preparation =====\n")

    try:
        records = fetch_dated_requests()

        if not records:
            print("No dated requests found. Dataset was not created.")
            return

        df = pd.DataFrame(records)

        df["blood_group"] = (
            df["blood_group"]
            .fillna("")
            .astype(str)
            .str.strip()
            .str.upper()
        )

        df["units"] = pd.to_numeric(
            df["units"], errors="coerce"
        )

        df["request_date"] = pd.to_datetime(
            df["request_date"], errors="coerce"
        )

        # Keep valid blood groups, positive quantities, and valid dates.
        df = df[
            df["blood_group"].isin(BLOOD_GROUPS)
            & (df["units"] > 0)
            & df["request_date"].notna()
        ].copy()

        if df.empty:
            print("No valid dated requests available.")
            print("Dataset was not created.")
            return

        df["date"] = df["request_date"].dt.normalize()

        # Total requested units per date and blood group.
        daily = (
            df.groupby(["date", "blood_group"])["units"]
            .sum()
            .reset_index()
        )

        daily["units"] = daily["units"].astype(int)

        # Build a continuous date range using observed dates.
        all_dates = pd.date_range(
            start=daily["date"].min(),
            end=daily["date"].max(),
            freq="D"
        )

        complete_index = pd.MultiIndex.from_product(
            [all_dates, BLOOD_GROUPS],
            names=["date", "blood_group"]
        )

        dataset = (
            daily.set_index(["date", "blood_group"])["units"]
            .reindex(complete_index, fill_value=0)
            .reset_index()
            .rename(columns={"units": "requested_units"})
        )

        dataset["date"] = dataset["date"].dt.strftime("%Y-%m-%d")

        output_path = Path(__file__).resolve().parent / "daily_demand.csv"
        dataset.to_csv(output_path, index=False)

        print("Valid dated request records:", len(df))
        print("Date range:", dataset["date"].min(), "to", dataset["date"].max())
        print("Rows in prepared dataset:", len(dataset))
        print("\nDataset preview:")
        print(dataset.to_string(index=False))
        print("\nDataset saved to:")
        print(output_path)

        print(
            "\nNote: zero means no valid request units were recorded "
            "for that blood group on that date. It does not prove "
            "that real-world demand was zero."
        )

        print(
            "\nThis dataset is a preparation step, not a trained "
            "forecasting model."
        )

    except mysql.connector.Error as error:
        print("Database error:", error)
        print("Check that XAMPP MySQL is running.")

    except Exception as error:
        print("Unexpected error:", repr(error))


if __name__ == "__main__":
    main()
