
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

    query = "SELECT blood_group, quantity FROM blood_stock"
    df = pd.read_sql(query, connection)

    connection.close()

    print("\n===== RaktaSetu Pandas Test =====\n")
    print(df.to_string(index=False))

    print("\n===== Stock Summary =====\n")
    summary = (
        df.groupby("blood_group", as_index=False)["quantity"]
        .sum()
    )
    print(summary.to_string(index=False))

    print("\nPandas test completed successfully!")

except Exception as error:
    print("TEST FAILED:", repr(error))
