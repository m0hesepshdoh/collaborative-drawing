"""
Fig. 3 — Throughput versus concurrent clients.
Reads: benchmarks/throughput_*.csv (uses the most recent clean run)
Output: figures/fig3_throughput.png
"""
import csv
import glob
import os
import matplotlib.pyplot as plt

# ---- Pick the most recent throughput CSV ----
path = sorted(glob.glob("benchmarks/throughput_*.csv"), key=os.path.getmtime)[-1]
print(f"Using {path}")

clients, tput = [], []
with open(path) as f:
    for row in csv.DictReader(f):
        clients.append(int(row["clients"]))
        tput.append(float(row["throughput_per_s"]))

# ---- Plot ----
fig, ax = plt.subplots(figsize=(3.5, 2.4), dpi=300)
bars = ax.bar([str(c) for c in clients], tput,
              color="#2E5B9A", edgecolor="black", linewidth=0.4)
for bar, val in zip(bars, tput):
    ax.text(bar.get_x() + bar.get_width() / 2,
            bar.get_height() + max(tput) * 0.02,
            f"{val:.1f}", ha="center", va="bottom", fontsize=6)
ax.set_xlabel("Concurrent clients", fontsize=8)
ax.set_ylabel("Throughput (strokes/s)", fontsize=8)
ax.tick_params(labelsize=7)
ax.grid(axis="y", linestyle=":", linewidth=0.4, alpha=0.5)
for spine in ("top", "right"):
    ax.spines[spine].set_visible(False)
fig.tight_layout()
fig.savefig("figures/fig3_throughput.png", dpi=300, bbox_inches="tight")
print("Wrote figures/fig3_throughput.png")