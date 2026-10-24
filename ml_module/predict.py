
import mysql.connector
import pandas as pd

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


def fetch_data():
    connection = mysql.connector.connect(**DB_CONFIG)

    try:
        cursor = connection.cursor(dictionary=True)

        cursor.execute("""
            SELECT blood_group, units AS quantity, status, request_date
            FROM `request`
        """)
        requests = cursor.fetchall()

        cursor.execute("""
            SELECT blood_group, quantity
            FROM blood_stock
        """)
        stock = cursor.fetchall()

        return requests, stock

    finally:
        connection.close()


def main():
    print("\n===== RaktaSetu Blood Demand Analysis =====\n")

    try:
        requests, stock = fetch_data()

        request_df = pd.DataFrame(requests)
        stock_df = pd.DataFrame(stock)

        if request_df.empty:
            print("No request records found.")
            return

        for df in [request_df, stock_df]:
            df["blood_group"] = (
                df["blood_group"]
                .fillna("")
                .astype(str)
                .str.strip()
                .str.upper()
            )

        request_df["quantity"] = pd.to_numeric(
            request_df["quantity"], errors="coerce"
        ).fillna(0)

        stock_df["quantity"] = pd.to_numeric(
            stock_df["quantity"], errors="coerce"
        ).fillna(0)

        # Analyse valid recorded requests.
        valid_requests = request_df[
            request_df["blood_group"].isin(BLOOD_GROUPS)
            & (request_df["quantity"] > 0)
        ]

        demand = valid_requests.groupby("blood_group")[
            "quantity"
        ].sum().to_dict()

        valid_stock = stock_df[
            stock_df["blood_group"].isin(BLOOD_GROUPS)
        ]

        stock_totals = valid_stock.groupby("blood_group")[
            "quantity"
        ].sum().to_dict()

        results = []

        for group in BLOOD_GROUPS:
            current_stock = int(stock_totals.get(group, 0))
            recorded_demand = int(demand.get(group, 0))

            if current_stock < 0:
                status = "INVALID STOCK - INVESTIGATE"
                indicator = "Not calculated"
            elif current_stock == 0:
                status = "OUT OF STOCK"
                indicator = max(0, recorded_demand - current_stock)
            elif current_stock <= 2:
                status = "LOW STOCK"
                indicator = max(0, recorded_demand - current_stock)
            else:
                status = "Review demand"
                indicator = max(0, recorded_demand - current_stock)

            results.append({
                "Blood Group": group,
                "Current Stock": current_stock,
                "Recorded Request Units": recorded_demand,
                "Planning Indicator": indicator,
                "Stock Status": status
            })

        print("===== CURRENT STOCK ANALYSIS =====\n")
        print(pd.DataFrame(results).to_string(index=False))

        # Analyse request quantities by date.
        valid_requests = valid_requests.copy()
        valid_requests["request_date"] = pd.to_datetime(
            valid_requests["request_date"], errors="coerce"
        )

        dated_requests = valid_requests.dropna(
            subset=["request_date"]
        ).copy()

        print("\n===== DAILY RECORDED DEMAND =====\n")

        if dated_requests.empty:
            print("No dated requests available yet.")
        else:
            daily = (
                dated_requests
                .assign(
                    request_day=dated_requests["request_date"].dt.date
                )
                .groupby(["request_day", "blood_group"])["quantity"]
                .sum()
                .reset_index()
                .sort_values(["request_day", "blood_group"])
            )

            print(daily.to_string(index=False))

            print(
                "\nDated requests:",
                len(dated_requests)
            )

        print("\nAnalysis complete.")
        print(
            "This report summarizes recorded requests; it is not yet "
            "a trained ML forecast. More reliable dated history is "
            "needed to train and evaluate a forecasting model."
        )

    except mysql.connector.Error as error:
        print("\nDatabase error:", error)
        print("Check that XAMPP MySQL is running.")

    except Exception as error:
        print("\nUnexpected error:", error)


if __name__ == "__main__":
    main()
