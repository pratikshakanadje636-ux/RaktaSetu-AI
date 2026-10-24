
import mysql.connector
import pandas as pd

BLOOD_GROUPS = ["A+", "A-", "B+", "B-", "O+", "O-", "AB+", "AB-"]

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

    stock_df = pd.read_sql(
        "SELECT blood_group, quantity FROM blood_stock",
        connection
    )

    request_df = pd.read_sql(
        "SELECT blood_group, units FROM `request`",
        connection
    )

    connection.close()

    stock_df["blood_group"] = (
        stock_df["blood_group"].fillna("").astype(str).str.strip().str.upper()
    )
    stock_df["quantity"] = pd.to_numeric(
        stock_df["quantity"], errors="coerce"
    ).fillna(0)

    request_df["blood_group"] = (
        request_df["blood_group"].fillna("").astype(str).str.strip().str.upper()
    )
    request_df["units"] = pd.to_numeric(
        request_df["units"], errors="coerce"
    ).fillna(0)

    stock = stock_df.groupby("blood_group")["quantity"].sum().to_dict()

    valid_requests = request_df[
        request_df["blood_group"].isin(BLOOD_GROUPS)
        & (request_df["units"] > 0)
    ]

    demand = valid_requests.groupby("blood_group")["units"].sum().to_dict()

    results = []

    for group in BLOOD_GROUPS:
        current = int(stock.get(group, 0))
        requested = int(demand.get(group, 0))

        if current < 0:
            status = "INVALID STOCK - CHECK"
            indicator = "Not calculated"
        elif current == 0:
            status = "OUT OF STOCK"
            indicator = max(0, requested - current)
        elif current <= 2:
            status = "LOW STOCK"
            indicator = max(0, requested - current)
        else:
            status = "REVIEW"
            indicator = max(0, requested - current)

        results.append({
            "Blood Group": group,
            "Current Stock": current,
            "Recorded Requests": requested,
            "Planning Indicator": indicator,
            "Stock Status": status
        })

    print("\n===== RaktaSetu Stock & Demand Analysis =====\n")
    print(pd.DataFrame(results).to_string(index=False))
    print("\nAnalysis test completed successfully!")

except Exception as error:
    print("TEST FAILED:", repr(error))
