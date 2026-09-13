#!/usr/bin/env python3
"""Make a logo background transparent, trim padding, and normalize width."""

from __future__ import annotations

import sys

from PIL import Image


def luminance(rgb: tuple[int, int, int]) -> float:
    r, g, b = rgb
    return 0.2126 * r + 0.7152 * g + 0.0722 * b


def color_distance(a: tuple[int, int, int], b: tuple[int, int, int]) -> float:
    return abs(a[0] - b[0]) + abs(a[1] - b[1]) + abs(a[2] - b[2])


def process(src: str, dst: str) -> None:
    img = Image.open(src).convert("RGBA")
    pixels = img.load()
    width, height = img.size

    alpha_values = [pixels[x, y][3] for y in range(0, height, max(1, height // 25)) for x in range(0, width, max(1, width // 25))]
    already_transparent = sum(1 for a in alpha_values if a < 16) > len(alpha_values) * 0.08

    if not already_transparent:
        # Sample corners/edges to detect solid canvas color (e.g. black square).
        edge_points = [
            (0, 0),
            (width - 1, 0),
            (0, height - 1),
            (width - 1, height - 1),
            (width // 2, 0),
            (width // 2, height - 1),
            (0, height // 2),
            (width - 1, height // 2),
        ]
        edge_colors = [pixels[x, y][:3] for x, y in edge_points]
        # Use the darkest or lightest edge tone that dominates.
        avg_edge = sum(luminance(c) for c in edge_colors) / len(edge_colors)
        bg = edge_colors[0]
        # Pick a representative background: median-ish by luminance.
        edge_colors_sorted = sorted(edge_colors, key=luminance)
        bg = edge_colors_sorted[len(edge_colors_sorted) // 2]

        threshold = 55 if avg_edge < 128 else 40
        for y in range(height):
            for x in range(width):
                r, g, b, a = pixels[x, y]
                if color_distance((r, g, b), bg) <= threshold:
                    pixels[x, y] = (r, g, b, 0)

    bbox = img.split()[-1].getbbox()
    if not bbox:
        # Nothing left; fall back to original pixels without chroma key.
        img = Image.open(src).convert("RGBA")
        bbox = img.split()[-1].getbbox() or (0, 0, img.width, img.height)

    img = img.crop(bbox)

    pad = 12
    padded = Image.new("RGBA", (img.width + pad * 2, img.height + pad * 2), (0, 0, 0, 0))
    padded.paste(img, (pad, pad), img)

    target_w = 640
    if padded.width != target_w:
        ratio = target_w / padded.width
        padded = padded.resize(
            (target_w, max(1, int(padded.height * ratio))),
            Image.Resampling.LANCZOS,
        )

    # Prefer dark ink for light CRM dashboards.
    opaque = [
        padded.getpixel((x, y))
        for y in range(0, padded.height, max(1, padded.height // 40))
        for x in range(0, padded.width, max(1, padded.width // 40))
        if padded.getpixel((x, y))[3] > 16
    ]
    if opaque:
        avg_ink = sum(luminance(px[:3]) for px in opaque) / len(opaque)
        if avg_ink > 160:
            inverted = Image.new("RGBA", padded.size, (0, 0, 0, 0))
            src_px = padded.load()
            dst_px = inverted.load()
            for y in range(padded.height):
                for x in range(padded.width):
                    r, g, b, a = src_px[x, y]
                    if a == 0:
                        continue
                    dst_px[x, y] = (255 - r, 255 - g, 255 - b, a)
            padded = inverted

    padded.save(dst, "PNG")


if __name__ == "__main__":
    if len(sys.argv) != 3:
        raise SystemExit("Usage: process_platform_logo.py <input> <output>")
    process(sys.argv[1], sys.argv[2])
