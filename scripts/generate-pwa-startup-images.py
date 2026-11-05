"""Render the static iOS launch images from the existing single-layer app icon.

Run with: python scripts/generate-pwa-startup-images.py
Requires Pillow. Keep DEVICE_SIZES in sync with shared/pwa-startup-images.blade.php.
"""

from pathlib import Path
import math

from PIL import Image, ImageDraw, ImageFilter, ImageFont


ROOT = Path(__file__).resolve().parents[1]
ICON = ROOT / "public/img/app-icon-source.png"
OUTPUT = ROOT / "public/img/pwa-startup"

# CSS viewport width, height, and pixel ratio. Tablet landscape variants are
# generated as well; the responsive HTML launch screen covers other sizes.
DEVICE_SIZES = (
    (320, 568, 2),
    (375, 667, 2),
    (375, 812, 3),
    (390, 844, 3),
    (393, 852, 3),
    (402, 874, 3),
    (414, 736, 3),
    (414, 896, 2),
    (414, 896, 3),
    (428, 926, 3),
    (430, 932, 3),
    (440, 956, 3),
    (744, 1133, 2),
    (768, 1024, 2),
    (810, 1080, 2),
    (820, 1180, 2),
    (834, 1112, 2),
    (834, 1194, 2),
    (834, 1210, 2),
    (1024, 1366, 2),
    (1032, 1376, 2),
)


def font(bold: bool, size: int) -> ImageFont.FreeTypeFont:
    candidates = (
        [Path("C:/Windows/Fonts/segoeuib.ttf"), Path("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf")]
        if bold
        else [Path("C:/Windows/Fonts/segoeui.ttf"), Path("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf")]
    )
    for candidate in candidates:
        if candidate.exists():
            return ImageFont.truetype(str(candidate), size)
    raise FileNotFoundError("A Segoe UI or DejaVu Sans TrueType font is required")


def draw_tracking(draw: ImageDraw.ImageDraw, text: str, center_x: float, y: float, face: ImageFont.FreeTypeFont, fill, tracking: int) -> None:
    widths = [draw.textlength(char, font=face) for char in text]
    total = sum(widths) + tracking * (len(text) - 1)
    x = center_x - total / 2
    for char, width in zip(text, widths):
        draw.text((x, y), char, font=face, fill=fill)
        x += width + tracking


def render(css_width: int, css_height: int, dpr: int, landscape: bool = False) -> Path:
    if landscape:
        css_width, css_height = css_height, css_width
    width, height = css_width * dpr, css_height * dpr
    scale = max(1, round(dpr / 2))
    small = (math.ceil(width / scale), math.ceil(height / scale))

    background = Image.new("RGBA", small, "#071a33")
    glows = Image.new("RGBA", small, (0, 0, 0, 0))
    glow_draw = ImageDraw.Draw(glows)
    glow_draw.ellipse(
        (small[0] * .27, -small[1] * .28, small[0] * 1.40, small[1] * .52),
        fill=(29, 91, 159, 92),
    )
    glow_draw.ellipse(
        (-small[0] * .43, small[1] * .52, small[0] * .56, small[1] * 1.28),
        fill=(0, 105, 178, 42),
    )
    glows = glows.filter(ImageFilter.GaussianBlur(max(24, int(min(small) * .14))))
    background.alpha_composite(glows)

    accents = Image.new("RGBA", small, (0, 0, 0, 0))
    accent_draw = ImageDraw.Draw(accents)
    arc_width = max(1, round(dpr / scale))
    accent_draw.arc(
        (small[0] * .05, small[1] * .14, small[0] * 1.33, small[1] * .91),
        204, 323, fill=(123, 192, 255, 19), width=arc_width,
    )
    accent_draw.arc(
        (-small[0] * .34, small[1] * .20, small[0] * .91, small[1] * 1.04),
        25, 144, fill=(123, 192, 255, 12), width=arc_width,
    )
    background.alpha_composite(accents)
    background = background.resize((width, height), Image.Resampling.LANCZOS)

    logo_size_css = min(194 if css_width > 600 else 168, max(108, css_width * .32))
    logo_size = round(logo_size_css * dpr)
    title_size = round((32 if css_width > 600 else 29) * dpr)
    tagline_size = round((17 if css_width > 600 else 15) * dpr)
    footer_size = round(10 * dpr)
    gap = round(25 * dpr)
    title_gap = round(9 * dpr)
    title_height = round(39 * dpr)
    tagline_height = round(24 * dpr)
    group_height = logo_size + gap + title_height + title_gap + tagline_height
    top = round(height * (.47 if css_height > css_width else .46) - group_height / 2)

    with Image.open(ICON) as original:
        logo = original.convert("RGBA").resize((logo_size, logo_size), Image.Resampling.LANCZOS)
    background.alpha_composite(logo, ((width - logo_size) // 2, top))

    draw = ImageDraw.Draw(background)
    title_y = top + logo_size + gap
    draw.text(
        (width / 2, title_y), "SpeakReady AI", font=font(True, title_size),
        fill="#f7fbff", anchor="mt",
    )
    draw.text(
        (width / 2, title_y + title_height + title_gap), "Practice with confidence.",
        font=font(False, tagline_size), fill="#bed1e8", anchor="mt",
    )

    footer_y = height - round((63 if css_height > css_width else 38) * dpr)
    line_width = round(36 * dpr)
    line_y = footer_y - round(22 * dpr)
    draw.rounded_rectangle(
        (width / 2 - line_width / 2, line_y, width / 2 + line_width / 2, line_y + max(2, dpr)),
        radius=dpr, fill="#45aaff",
    )
    draw_tracking(
        draw, "PREPARE  ·  PRACTICE  ·  PERFORM", width / 2, footer_y,
        font(False, footer_size), "#98b4d2", max(1, round(dpr * .7)),
    )

    OUTPUT.mkdir(parents=True, exist_ok=True)
    target = OUTPUT / f"splash-{width}x{height}.png"
    background.convert("RGB").save(target, "PNG", optimize=True)
    return target


if __name__ == "__main__":
    for device_width, device_height, pixel_ratio in DEVICE_SIZES:
        print(render(device_width, device_height, pixel_ratio))
        if device_width >= 600:
            print(render(device_width, device_height, pixel_ratio, landscape=True))
