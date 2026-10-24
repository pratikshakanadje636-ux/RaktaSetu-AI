
import mysql.connector

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

    cursor = connection.cursor()

    cursor.execute(
        "SELECT blood_group, quantity FROM blood_stock"
    )

    records = cursor.fetchall()

    print("\n===== RaktaSetu Blood Stock Test =====\n")
    print("Blood Group | Quantity")
    print("-" * 25)

    for blood_group, quantity in records:
        print(f"{blood_group:<11} | {quantity}")

    print(f"\nTotal records fetched: {len(records)}")

    cursor.close()
    connection.close()

except mysql.connector.Error as error:
    print("DATABASE ERROR:", error)
