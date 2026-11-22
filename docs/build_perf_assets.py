#!/usr/bin/env python3
"""Build AST-minified, route-tailored CSS and JS bundles from main.css, consent.js, and main.js."""
import gzip
import os
import shutil
import subprocess

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
_LOCAL_ESB = os.path.expanduser("~/.local/esbuild-tool/node_modules/.bin/esbuild")
ESBUILD = shutil.which("esbuild") or (_LOCAL_ESB if os.path.isfile(_LOCAL_ESB) else "/tmp/esbuild-tool/node_modules/.bin/esbuild")


def run_esbuild(content: str, loader: str) -> str:
    cmd = [ESBUILD, "--minify", f"--loader={loader}"]
    if loader == "js":
        cmd.append("--target=es2020")
    out = subprocess.check_output(cmd, input=content, text=True)
    return out.strip()


def slice_between(text: str, start_marker: str, end_marker: str) -> tuple[str, str]:
    s = text.index(start_marker)
    e = text.index(end_marker, s)
    return text[s:e], text[:s] + text[e:]


def build_css() -> None:
    css = open(os.path.join(ROOT, "assets/css/main.css"), encoding="utf-8").read()
    os.makedirs(os.path.join(ROOT, "assets/css/dist"), exist_ok=True)

    full_min = run_esbuild(css, "css")
    open(os.path.join(ROOT, "assets/css/main.min.css"), "w", encoding="utf-8").write(full_min)

    work = css
    prices_sec, work = slice_between(
        work,
        "/* ---------- Prices: stepper, tabs, tables, cart ---------- */",
        "/* ---------- Forms ---------- */",
    )
    print_sec, work = slice_between(
        work,
        "/* ==========================================================================\n   Print (price list → PDF)",
        ".newsletter__title {",
    )
    item_sec, work = slice_between(
        work,
        "/* Item Split Hero */",
        ".btn--block,",
    )
    calc_sec, work = slice_between(
        work,
        "/* ==========================================================================\n   Feature 1: Interactive Dive Budget & Fee Calculator",
        "/* ==========================================================================\n   Feature 2: 30-Second Dive / Course Recommender Quiz",
    )
    quiz_sec, work = slice_between(
        work,
        "/* ==========================================================================\n   Feature 2: 30-Second Dive / Course Recommender Quiz",
        "/* ==========================================================================\n   Feature 3: Interactive Dive Sites SVG Map",
    )
    sites_sec, work = slice_between(
        work,
        "/* ==========================================================================\n   Feature 3: Interactive Dive Sites SVG Map",
        "/* ==========================================================================\n   Feature 4: Underwater Photo/Video Gallery",
    )
    gallery_sec, work = slice_between(
        work,
        "/* ==========================================================================\n   Feature 4: Underwater Photo/Video Gallery",
        "@media (min-width: 700px) {",
    )
    item_media_sec, work = slice_between(
        work,
        "/* ==========================================================================\n   Item Page Multi-Photo & Underwater HD Video Gallery",
        "/* ==========================================================================\n   Site-Wide Internal Promo",
    )
    fleet_sec, work = slice_between(
        work,
        "/* ==========================================================================\n   Grouped Boat Fleet Albums",
        ".center-preview-img {",
    )
    qbook_sec, work = slice_between(
        work,
        "/* ==========================================================================\n   Item Quick-Book, Mobile Sticky Bar & Guides Article",
        "/* ==========================================================================\n   Pre-Arrival Diver Check-In Portal",
    )
    checkin_sec, work = slice_between(
        work,
        "/* ==========================================================================\n   Pre-Arrival Diver Check-In Portal",
        "/* === END === */",
    )

    bundles = {
        "home": work + "\n" + quiz_sec,
        "hub": work + "\n" + quiz_sec + "\n" + prices_sec,
        "prices": work + "\n" + prices_sec + "\n" + calc_sec + "\n" + quiz_sec + "\n" + print_sec,
        "item": work + "\n" + prices_sec + "\n" + item_sec + "\n" + item_media_sec + "\n" + qbook_sec + "\n" + print_sec,
        "sites": work + "\n" + prices_sec + "\n" + sites_sec,
        "gallery": work + "\n" + gallery_sec + "\n" + fleet_sec,
        "about": work + "\n" + gallery_sec + "\n" + fleet_sec,
        "base": work + "\n" + prices_sec + "\n" + checkin_sec,
    }
    print("=== CSS BUNDLES ===")
    for name, src in bundles.items():
        minified = run_esbuild(src, "css")
        out_path = os.path.join(ROOT, f"assets/css/dist/{name}.min.css")
        open(out_path, "w", encoding="utf-8").write(minified)
        gz = len(gzip.compress(minified.encode("utf-8")))
        print(f"  {name:10s}: {len(minified):6d} B minified ({gz:5d} B gzip)")


def build_js() -> None:
    consent = open(os.path.join(ROOT, "assets/js/consent.js"), encoding="utf-8").read()
    main = open(os.path.join(ROOT, "assets/js/main.js"), encoding="utf-8").read()
    os.makedirs(os.path.join(ROOT, "assets/js/dist"), exist_ok=True)

    f1_marker = "  /* ---------------- Feature 1: Interactive Dive Budget & Fee Calculator ---------------- */"
    f2_marker = "  /* ---------------- Feature 2: 30-Second Dive / Course Recommender Quiz ---------------- */"
    f3_marker = "  /* ---------------- Feature 3: Interactive Dive Sites SVG Map & Zone Filter ---------------- */"
    f4_marker = "  /* ---------------- Feature 4: Photo & Video Gallery Filter + Lightbox ---------------- */"
    item_marker = "  /* ---------------- Item Page Multi-Photo & Underwater Video Gallery ---------------- */"
    checkin_marker = "  /* ---------------- Pre-Arrival Diver Check-In Portal Helpers ---------------- */"
    tail_marker = "  /* Re-sync calculator & quiz numbers whenever currency changes */"

    head = main[: main.index(f1_marker)]
    f1 = main[main.index(f1_marker) : main.index(f2_marker)]
    f2 = main[main.index(f2_marker) : main.index(f3_marker)]
    f3 = main[main.index(f3_marker) : main.index(f4_marker)]
    f4 = main[main.index(f4_marker) : main.index(item_marker)]
    f_item = main[main.index(item_marker) : main.index(checkin_marker)]
    f_checkin = main[main.index(checkin_marker) : main.index(tail_marker)]
    tail = main[main.index(tail_marker) :]

    bundles = {
        "home": consent + "\n" + head + f2 + tail,
        "prices": consent + "\n" + head + f1 + f2 + tail,
        "item": consent + "\n" + head + f_item + tail,
        "sites": consent + "\n" + head + f3 + tail,
        "gallery": consent + "\n" + head + f4 + tail,
        "base": consent + "\n" + head + f_checkin + tail,
        "full": consent + "\n" + main,
    }
    print("=== JS BUNDLES ===")
    for name, src in bundles.items():
        minified = run_esbuild(src, "js")
        out_path = os.path.join(ROOT, f"assets/js/dist/{name}.min.js")
        open(out_path, "w", encoding="utf-8").write(minified)
        gz = len(gzip.compress(minified.encode("utf-8")))
        print(f"  {name:10s}: {len(minified):6d} B minified ({gz:5d} B gzip)")


if __name__ == "__main__":
    build_css()
    build_js()
