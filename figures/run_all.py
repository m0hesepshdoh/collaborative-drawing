"""
Run all figure scripts in order.
Usage: python figures/run_all.py
"""
import subprocess
import sys
from pathlib import Path

scripts = [
    "fig1_latency_histogram.py",
    "fig2_latency_over_time.py",
    "fig3_throughput.py",
    "fig4_reconnect.py",
    "fig5_bot_replay.py",
    "fig6_ai_duration.py",
]

here = Path(__file__).resolve().parent

for s in scripts:
    script_path = here / s
    print(f"\n=== Running {s} ===")
    result = subprocess.run([sys.executable, str(script_path)], cwd=here.parent)
    if result.returncode != 0:
        print(f"FAILED: {s}")
        sys.exit(result.returncode)

print("\nAll figures generated in figures/")