"""
Fig. 1 — WebSocket stroke latency distribution across all benchmark runs.
Reads: benchmarks/latency_*.csv
Output: figures/fig1_latency_histogram.png
"""
import csv
import glob
import matplotlib.pyplot as plt

# ---- Load all latency CSVs ----
latencies = []
for path in glob.glob("benchmarks/latency_*.csv"):
    with open(path) as f:
        for row in csv.DictReader(f):
            try:
                latencies.append(float(row["latency_ms"]))
            except (KeyError, ValueError):
                continue

print(f"Loaded {len(latencies)} samples")

# ---- Plot ----
fig, ax = plt.subplots(figsize=(3.5, 2.4), dpi=300)
ax.hist(latencies, bins=40, color="#2E5B9A", edgecolor="black", linewidth=0.4)
ax.set_xlabel("Latency (ms)", fontsize=8)
ax.set_ylabel("Frequency", fontsize=8)
ax.tick_params(labelsize=7)
ax.grid(axis="y", linestyle=":", linewidth=0.4, alpha=0.5)
for spine in ("top", "right"):
    ax.spines[spine].set_visible(False)
fig.tight_layout()
fig.savefig("figures/fig1_latency_histogram.png", dpi=300, bbox_inches="tight")
print("Wrote figures/fig1_latency_histogram.png")