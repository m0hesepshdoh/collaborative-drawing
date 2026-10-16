"""
Figure 10. Entity-Relationship Diagram for the Collaborative Drawing System.

Generates a crow's-foot ERD in IEEE style using Graphviz.
Outputs: figures/fig10_erd.png (300 dpi) and figures/fig10_erd.svg (vector).
"""

import os
from graphviz import Digraph

OUT_DIR = "figures"
os.makedirs(OUT_DIR, exist_ok=True)

OUT_PNG = os.path.join(OUT_DIR, "fig10_erd.png")
OUT_SVG = os.path.join(OUT_DIR, "fig10_erd.svg")

# ---------------------------------------------------------------------------
# Entity definitions (verified against README.md schema)
# ---------------------------------------------------------------------------

entities = {
    "sessions": {
        "label": "sessions",
        "attrs": [
            ("PK", "id", "int"),
            ("",   "code", "varchar(6)"),
            ("FK", "background_id", "int"),
            ("",   "status", "varchar(20)"),
            ("",   "finish_state", "varchar(20)"),
            ("",   "finished_by_ip", "varchar(45)"),
            ("",   "finish_deadline_at", "timestamp"),
            ("",   "ai_image_url", "varchar(255)"),
            ("",   "ai_prompt", "text"),
            ("",   "created_at", "timestamp"),
            ("",   "updated_at", "timestamp"),
        ],
    },
    "players": {
        "label": "players",
        "attrs": [
            ("PK", "id", "int"),
            ("FK", "session_id", "int"),
            ("",   "ip_address", "varchar(45)"),
            ("",   "is_bot", "boolean"),
            ("",   "last_activity_at", "timestamp"),
            ("",   "joined_at", "timestamp"),
            ("",   "left_at", "timestamp"),
        ],
    },
    "backgrounds": {
        "label": "backgrounds",
        "attrs": [
            ("PK", "id", "int"),
            ("",   "filename", "varchar(255)"),
            ("",   "path", "varchar(255)"),
            ("",   "created_at", "timestamp"),
        ],
    },
    "recorded_strokes": {
        "label": "recorded_strokes",
        "attrs": [
            ("PK", "id", "int"),
            ("FK", "session_id", "int"),
            ("",   "player_ip", "varchar(45)"),
            ("",   "points", "json"),
            ("",   "color", "varchar(7)"),
            ("",   "size", "int"),
            ("",   "drawn_at", "timestamp"),
            ("",   "created_at", "timestamp"),
        ],
    },
    "reports": {
        "label": "reports",
        "attrs": [
            ("PK", "id", "int"),
            ("FK", "session_id", "int"),
            ("",   "reporter_ip", "varchar(45)"),
            ("",   "reported_ip", "varchar(45)"),
            ("",   "created_at", "timestamp"),
        ],
    },
    "bans": {
        "label": "bans",
        "attrs": [
            ("PK", "id", "int"),
            ("",   "ip_address", "varchar(45)"),
            ("",   "reason", "varchar(255)"),
            ("",   "created_at", "timestamp"),
        ],
    },
    "bot_patterns": {
        "label": "bot_patterns",
        "attrs": [
            ("PK", "id", "int"),
            ("",   "name", "varchar(255)"),
            ("FK", "source_session_id", "int"),
            ("",   "source_ip", "varchar(45)"),
            ("",   "strokes", "json"),
            ("",   "approved", "boolean"),
            ("",   "approved_at", "timestamp"),
            ("",   "created_at", "timestamp"),
        ],
    },
}

# ---------------------------------------------------------------------------
# Relationship definitions (verified against README.md)
# ---------------------------------------------------------------------------

relationships = [
    # (from, from_card, to, to_card, label)
    ("backgrounds", "1", "sessions", "N", "has"),
    ("sessions", "1", "players", "N", "contains"),
    ("sessions", "1", "recorded_strokes", "N", "records"),
    ("sessions", "1", "reports", "N", "is reported in"),
    ("sessions", "1", "bot_patterns", "N", "sources (optional)"),
]

# ---------------------------------------------------------------------------
# Crow's-foot cardinality markers
# ---------------------------------------------------------------------------

CROW = {
    "1":  "tee",        # one
    "N":  "crow",       # many
}

# ---------------------------------------------------------------------------
# Build HTML-like table nodes for each entity
# ---------------------------------------------------------------------------

def entity_html(name: str, attrs) -> str:
    """Return an HTML-like label for Graphviz."""
    rows = []
    rows.append(
        f'<TR><TD BGCOLOR="#2E5B9A" COLSPAN="3" ALIGN="CENTER">'
        f'<FONT COLOR="white" POINT-SIZE="11"><B>{name}</B></FONT></TD></TR>'
    )
    for key, col, typ in attrs:
        badge = {
            "PK": '<FONT COLOR="#B8860B"><B>PK</B></FONT>',
            "FK": '<FONT COLOR="#2E7D32"><B>FK</B></FONT>',
            "":   "",
        }[key]
        rows.append(
            f'<TR>'
            f'<TD ALIGN="LEFT" WIDTH="28">{badge}</TD>'
            f'<TD ALIGN="LEFT">{col}</TD>'
            f'<TD ALIGN="LEFT"><FONT COLOR="#555555">{typ}</FONT></TD>'
            f'</TR>'
        )
    return (
        '<<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" CELLPADDING="4" '
        'BGCOLOR="white" COLOR="black">'
        + "".join(rows)
        + "</TABLE>>"
    )

# ---------------------------------------------------------------------------
# Build the graph
# ---------------------------------------------------------------------------

dot = Digraph("ERD", format="png")
dot.attr(rankdir="LR", bgcolor="white", pad="0.3", nodesep="0.6", ranksep="0.9")
dot.attr("node", shape="plaintext", fontname="Helvetica")
dot.attr("edge", fontname="Helvetica", fontsize="10", color="black")

# Add entity nodes
for name, meta in entities.items():
    dot.node(name, label=entity_html(name, meta["attrs"]))

# Add relationship edges with crow's-foot notation
for src, src_card, dst, dst_card, label in relationships:
    dot.edge(
        src,
        dst,
        label=f"  {label}",
        arrowhead=CROW[dst_card],
        arrowtail=CROW[src_card],
        dir="both",
        fontsize="9",
        color="black",
    )

# ---------------------------------------------------------------------------
# Render
# ---------------------------------------------------------------------------

dot.render(OUT_PNG.replace(".png", ""), cleanup=True)
dot.render(OUT_SVG.replace(".svg", ""), cleanup=True)

print(f"Wrote {OUT_PNG}")
print(f"Wrote {OUT_SVG}")