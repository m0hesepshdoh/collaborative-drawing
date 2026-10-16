"""
Fig. 4 — Reconnect and resubscribe time after controlled network drops.
Reads: benchmarks/reconnect_*.csv
Output: figures/fig4_reconnect.png
"""
import csv
import glob
import os
import matplotlib.pyplot as plt

path = sorted(glob.glob("benchmarks/reconnect_*.csv"), key=os.path.getmtime)[-1]
print(f"Using {path}")

drops, reconnect, resubscribe = [], [], []
with open(path) as f:
    for row in csv.DictReader(f):
        drops.append(int(row["drop_seconds"]))
        reconnect.append(float(row["reconnect_ms"]))
        resubscribe.append(float(row["resubscribe_ms"]))

# ---- Plot grouped bars ----
import numpy as np
x = np.arange(len(drops))
width = 0.35

fig, ax = plt.subplots(figsize=(3.5, 2.4), dpi=300)
b1 = ax.bar(x - width / 2, reconnect, width,
            label="Reconnect", color="#2E5B9A", edgecolor="black", linewidth=0.4)
b2 = ax.bar(x + width / 2, [max(r, 0.5) for r in resubscribe], width,
            label="Resubscribe", color="#D55E00", edgecolor="black", linewidth=0.4)

# Annotate values
for bars in (b1, b2):
    for bar in bars:
        h = bar.get_height()
        ax.text(bar.get_x() + bar.get_width() / 2, h * 1.1,
                f"{h:.0f}", ha="center", va="bottom", fontsize=5.5)

ax.set_xticks(x)
ax.set_xticklabels([f"{d} s" for d in drops], fontsize=7)
ax.set_yscale("log")
ax.set_ylabel("Time (ms, log scale)", fontsize=8)
ax.set_xlabel("Drop duration", fontsize=8)
ax.tick_params(labelsize=7)
ax.legend(fontsize=6, frameon=False)
ax.grid(axis="y", linestyle=":", linewidth=0.4, alpha=0.5)
for spine in ("top", "right"):
    ax.spines[spine].set_visible(False)
fig.tight_layout()
fig.savefig("figures/fig4_reconnect.png", dpi=300, bbox_inches="tight")
print("Wrote figures/fig4_reconnect.png")