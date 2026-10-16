"""
Fig. 6 — AI image-edit duration distribution.
Reads: benchmarks/ai_*.csv (successful requests only)
Output: figures/fig6_ai_duration.png
"""
import csv
import glob
import os
import matplotlib.pyplot as plt

# Use the cleanest AI run — hardcode the file if you prefer
path = "benchmarks/ai_2026-10-10T00-54-19-520Z.csv"
if not os.path.exists(path):
    path = sorted(glob.glob("benchmarks/ai_*.csv"), key=os.path.getmtime)[-1]
print(f"Using {path}")

durations = []
with open(path) as f:
    for row in csv.DictReader(f):
        if row.get("success", "").lower() == "true":
            try:
                durations.append(float(row["duration_ms"]) / 1000.0)
            except (KeyError, ValueError):
                continue

print(f"Successful samples: {len(durations)}")

fig, ax = plt.subplots(figsize=(3.5, 2.4), dpi=300)
ax.hist(durations, bins=15, color="#2E5B9A", edgecolor="black", linewidth=0.4)
ax.set_xlabel("Duration (s)", fontsize=8)
ax.set_ylabel("Frequency", fontsize=8)
ax.tick_params(labelsize=7)
ax.grid(axis="y", linestyle=":", linewidth=0.4, alpha=0.5)
for spine in ("top", "right"):
    ax.spines[spine].set_visible(False)
fig.tight_layout()
fig.savefig("figures/fig6_ai_duration.png", dpi=300, bbox_inches="tight")
print("Wrote figures/fig6_ai_duration.png")