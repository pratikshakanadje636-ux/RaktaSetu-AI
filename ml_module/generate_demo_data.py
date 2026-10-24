
import pandas as pd
import numpy as np
from pathlib import Path

# SYNTHETIC DEMO DATA - NOT REAL HOSPITAL RECORDS
rng = np.random.default_rng(42)

blood_groups = ["A+", "A-", "B+", "B-", "O+", "O-", "AB+", "AB-"]

base_demand = {
    "A+": 5,
    "A-": 2,
    "B+": 4,
    "B-": 2,
    "O+": 6,
    "O-": 2,
    "AB+": 2,
    "AB-": 1
}

dates = pd.date_range(end=pd.Timestamp.today().normalize(), periods=180)
rows = []

for group in blood_groups:
    for day_number, date in enumerate(dates):
        seasonal_factor = 1 + 0.2 * np.sin(2 * np.pi * day_number / 30)
        average = max(0.2, base_demand[group] * seasonal_factor)
        units = int(rng.poisson(average))

        rows.append({
            "date": date.strftime("%Y-%m-%d"),
            "blood_group": group,
            "requested_units": units
        })

df = pd.DataFrame(rows)

output_file = Path(__file__).resolve().parent / "daily_demand.csv"
df.to_csv(output_file, index=False)

print("Synthetic demo dataset created successfully!")
print("This is generated demo data, NOT real hospital history.")
print("Distinct dates:", df["date"].nunique())
print("Total rows:", len(df))
print("Saved to:", output_file)
print(df.head(8).to_string(index=False))
