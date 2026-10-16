"""
Fig. 2 — WebSocket latency over time across three benchmark runs.
Reads: benchmarks/latency_*.csv
Output: figures/fig2_latency_over_time.png
"""
import csv
import glob
import matplotlib.pyplot as plt

# ---- Group samples by run ----
runs = {}
for path in sorted(glob.glob("benchmarks/latency_*.csv")):
    with open(path) as f:
        for row in csv.DictReader(f):
            try:
                run = int(row["run"])
                lat = float(row["latency_ms"])
            except (KeyError, ValueError):
                continue
            runs.setdefault(run, []).append(lat)

# ---- Plot ----
fig, ax = plt.subplots(figsize=(3.5, 2.4), dpi=300)
colors = ["#2E5B9A", "#D55E00", "#009E73"]
for i, (run, values) in enumerate(sorted(runs.items())):
    ax.plot(range(len(values)), values,
            linewidth=0.6, color=colors[i % len(colors)],
            label=f"Run {run}")
ax.set_xlabel("Sample index", fontsize=8)
ax.set_ylabel("Latency (ms)", fontsize=8)
ax.tick_params(labelsize=7)
ax.legend(fontsize=6, frameon=False)
ax.grid(linestyle=":", linewidth=0.4, alpha=0.5)
for spine in ("top", "right"):
    ax.spines[spine].set_visible(False)
fig.tight_layout()
fig.savefig("figures/fig2_latency_over_time.png", dpi=300, bbox_inches="tight")
print("Wrote figures/fig2_latency_over_time.png")