"""
Fig. 5 — Bot replay accuracy per approved pattern.
Reads: benchmarks/bot_replay_*.csv
Output: figures/fig5_bot_replay.png
"""
import csv
import glob
import os
import numpy as np
import matplotlib.pyplot as plt

path = sorted(glob.glob("benchmarks/bot_replay_*.csv"), key=os.path.getmtime)[-1]
print(f"Using {path}")

pids, cosine, hausdorff = [], [], []
with open(path) as f:
    for row in csv.DictReader(f):
        pids.append(int(row["pattern_id"]))
        cosine.append(float(row["cosine_sim"]))
        hausdorff.append(float(row["hausdorff"]))

x = np.arange(len(pids))
width = 0.35

fig, ax1 = plt.subplots(figsize=(3.5, 2.4), dpi=300)
b1 = ax1.bar(x - width / 2, cosine, width,
             label="Cosine similarity", color="#2E5B9A",
             edgecolor="black", linewidth=0.4)
ax1.set_ylabel("Cosine similarity", fontsize=8)
ax1.set_ylim(0, 1.2)
ax1.tick_params(labelsize=7)

ax2 = ax1.twinx()
b2 = ax2.bar(x + width / 2, [h if h > 0 else 0.05 for h in hausdorff], width,
             label="Hausdorff distance", color="#D55E00",
             edgecolor="black", linewidth=0.4)
ax2.set_ylabel("Hausdorff distance (px)", fontsize=8)
ax2.tick_params(labelsize=7)

ax1.set_xticks(x)
ax1.set_xticklabels([f"P{p}" for p in pids], fontsize=7)
ax1.set_xlabel("Pattern ID", fontsize=8)
ax1.grid(axis="y", linestyle=":", linewidth=0.4, alpha=0.5)
for spine in ("top",):
    ax1.spines[spine].set_visible(False)
    ax2.spines[spine].set_visible(False)

# Combined legend
h1, l1 = ax1.get_legend_handles_labels()
h2, l2 = ax2.get_legend_handles_labels()
ax1.legend(h1 + h2, l1 + l2, fontsize=6, frameon=False, loc="lower center")
fig.tight_layout()
fig.savefig("figures/fig5_bot_replay.png", dpi=300, bbox_inches="tight")
print("Wrote figures/fig5_bot_replay.png")