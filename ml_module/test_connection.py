
import mysql.connector

print("RaktaSetu Connection Test Started")

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

    print("SUCCESS: Database connected!")

    cursor = connection.cursor()

    cursor.execute("SELECT DATABASE()")
    print("Database:", cursor.fetchone()[0])

    cursor.execute("SELECT COUNT(*) FROM blood_stock")
    print("Blood stock records:", cursor.fetchone()[0])

    cursor.close()
    connection.close()

    print("SUCCESS: Connection closed!")
    
except mysql.connector.Error as error:
    print("DATABASE ERROR:", error)

except Exception as error:
    print("OTHER ERROR:", repr(error))
