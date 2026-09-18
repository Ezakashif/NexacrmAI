#!/usr/bin/env python3
"""Build Codester upload assets: 800x400 preview, 200x200 icon, screenshots ZIP.

Requires: Pillow, cairosvg

Outputs:
  public/branding/nexacrm-codester-preview-800x400.png
  public/branding/nexacrm-codester-icon-200x200.png
  codester-upload/nexacrm-codester-screenshots.zip
  codester-upload/ copies of preview + icon
"""

from __future__ import annotations

import io
import zipfile
from pathlib import Path

import cairosvg
from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = Path(__file__).resolve().parents[1]
BRAND = ROOT / "public" / "branding"
SHOTS = ROOT / "public" / "marketing" / "screenshots"
OUT = ROOT / "codester-upload"

# Unique product screens only (skip duplicates overview/sales-pipeline; skip pricing
# so buyers do not confuse in-app plan amounts with the Codester item price).
SCREENSHOTS = [
    "nexacrm-dashboard.png",
    "nexacrm-leads.png",
    "nexacrm-customers.png",
    "nexacrm-tasks.png",
    "nexacrm-reports.png",
    "nexacrm-user-management.png",
    "nexacrm-roles-permissions.png",
    "nexacrm-activity-log.png",
]


def load_font(paths: list[str], size: int) -> ImageFont.ImageFont:
    for path in paths:
        if Path(path).exists():
            return ImageFont.truetype(path, size)
    return ImageFont.load_default()


def render_mark(size: int) -> Image.Image:
    svg = (BRAND / "nexacrm-mark.svg").read_bytes()
    return Image.open(
        io.BytesIO(cairosvg.svg2png(bytestring=svg, output_width=size, output_height=size))
    ).convert("RGBA")


def build_icon() -> Image.Image:
    return render_mark(400).resize((200, 200), Image.Resampling.LANCZOS)


def build_preview() -> Image.Image:
    width, height = 800, 400
    base = Image.new("RGBA", (width, height), (15, 23, 42, 255))
    draw = ImageDraw.Draw(base)
    for x in range(width):
        t = x / (width - 1)
        draw.line(
            [(x, 0), (x, height)],
            fill=(int(15 + 20 * t), int(23 + 16 * t), int(42 + 32 * t), 255),
        )

    glow = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    gd = ImageDraw.Draw(glow)
    gd.ellipse([500, -120, 920, 280], fill=(56, 189, 248, 40))
    gd.ellipse([-120, 200, 260, 520], fill=(37, 99, 235, 45))
    preview = Image.alpha_composite(base, glow.filter(ImageFilter.GaussianBlur(2)))

    bold = load_font(["/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"], 40)
    reg = load_font(["/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"], 17)
    small = load_font(["/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"], 14)

    mark = render_mark(400).resize((52, 52), Image.Resampling.LANCZOS)
    preview.paste(mark, (36, 36), mark)
    d = ImageDraw.Draw(preview)
    d.text((102, 42), "nexacrm.", font=bold, fill=(248, 250, 252, 255))
    d.text((36, 110), "Modern Multi-Tenant CRM", font=reg, fill=(186, 230, 253, 255))
    d.text((36, 136), "Laravel source for agencies & SaaS", font=reg, fill=(148, 163, 184, 255))

    y = 185
    for label in [
        "Leads · Customers · Tasks",
        "Super Admin · Roles · Reports",
        "Self-host · White-label ready",
    ]:
        d.rounded_rectangle([36, y, 345, y + 30], radius=8, fill=(30, 41, 59, 230))
        d.text((48, y + 7), label, font=small, fill=(226, 232, 240, 255))
        y += 38

    dash = Image.open(SHOTS / "nexacrm-dashboard.png").convert("RGB")
    card_w, card_h = 400, 270
    src_w, src_h = dash.size
    crop = dash.crop((0, 0, int(src_w * 0.78), int(src_h * 0.72))).resize(
        (card_w, card_h), Image.Resampling.LANCZOS
    )
    mask = Image.new("L", (card_w, card_h), 0)
    ImageDraw.Draw(mask).rounded_rectangle([0, 0, card_w - 1, card_h - 1], radius=14, fill=255)

    cx, cy = 372, 65
    shadow = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    ImageDraw.Draw(shadow).rounded_rectangle(
        [cx + 4, cy + 8, cx + card_w + 4, cy + card_h + 8],
        radius=14,
        fill=(0, 0, 0, 100),
    )
    preview = Image.alpha_composite(preview, shadow.filter(ImageFilter.GaussianBlur(6)))
    preview.paste(crop, (cx, cy), mask)
    ImageDraw.Draw(preview).rounded_rectangle(
        [cx, cy, cx + card_w - 1, cy + card_h - 1],
        radius=14,
        outline=(148, 163, 184, 160),
        width=2,
    )
    return preview.convert("RGB")


def build_screenshots_zip() -> Path:
    zip_path = OUT / "nexacrm-codester-screenshots.zip"
    with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED) as zf:
        for name in SCREENSHOTS:
            src = SHOTS / name
            if not src.exists():
                raise FileNotFoundError(src)
            zf.write(src, arcname=name)
    return zip_path


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    icon = build_icon()
    preview = build_preview()

    icon_path = BRAND / "nexacrm-codester-icon-200x200.png"
    preview_path = BRAND / "nexacrm-codester-preview-800x400.png"
    icon.save(icon_path, "PNG", optimize=True)
    preview.save(preview_path, "PNG", optimize=True)
    icon.save(OUT / icon_path.name, "PNG", optimize=True)
    preview.save(OUT / preview_path.name, "PNG", optimize=True)

    zip_path = build_screenshots_zip()

    assert preview.size == (800, 400)
    assert icon.size == (200, 200)
    print(f"icon     {icon_path} {icon.size}")
    print(f"preview  {preview_path} {preview.size}")
    print(f"shots    {zip_path} ({len(SCREENSHOTS)} images)")


if __name__ == "__main__":
    main()
