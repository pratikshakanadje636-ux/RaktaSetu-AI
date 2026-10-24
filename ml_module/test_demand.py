
import mysql.connector
import pandas as pd

try:
    connection = mysql.connector.connect(
        host="127.0.0.1",
        port=3306,
        user="root",
        password="",
        database="new_blood_system",
        connection_timeout=5,
        use_pure=True
    )

    cursor = connection.cursor(dictionary=True)

    cursor.execute("""
        SELECT blood_group, units
        FROM `request`
    """)

    records = cursor.fetchall()

    cursor.close()
    connection.close()

    print("\n===== RaktaSetu Demand Test =====\n")

    if not records:
        print("No records found in the request table.")
    else:
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
        ).fillna(0)

        # Count only recognized blood groups and positive quantities.
        valid_groups = [
            "A+", "A-", "B+", "B-", "O+", "O-", "AB+", "AB-"
        ]

        df = df[
            df["blood_group"].isin(valid_groups)
            & (df["units"] > 0)
        ]

        if df.empty:
            print("No valid positive-unit requests to summarize.")
        else:
            summary = (
                df.groupby("blood_group", as_index=False)["units"]
                .sum()
                .rename(columns={"units": "total_requested_units"})
            )

            print(summary.to_string(index=False))
            print("\nDemand test completed successfully!")

except mysql.connector.Error as error:
    print("DATABASE ERROR:", error)

except Exception as error:
    print("TEST FAILED:", repr(error))
