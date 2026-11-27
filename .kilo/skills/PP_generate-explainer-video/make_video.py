#!/usr/bin/env python3
"""Build an explanatory MP4 (slideshow of screenshots + captions) from a JSON spec.

Usage:
    python3 -I make_video.py spec.json --ffmpeg /path/to/ffmpeg --workdir /path/to/empty/workdir

Spec format (UTF-8 JSON):
{
  "output": "/abs/path/video.mp4",
  "video_label": "Video 1 : short subtitle shown under every title",
  "badges": {                       # optional, merged over the defaults below
    "YES": ["#168c50", "CONFIG YES"],
    "NO":  ["#d67814", "CONFIG NO"]
  },
  "slides": [
    {"badge": "YES", "title": "Slide title", "text": "Caption. Use \\n for new lines.",
     "image": "/abs/path/shot.jpg",   # null or missing -> full-width text card
     "seconds": 8}                    # optional, default derived from the text length
  ]
}

Requires Pillow and an ffmpeg binary with libx264. Nothing else.
"""
import argparse
import json
import os
import subprocess
import sys

from PIL import Image, ImageDraw, ImageFont

BOLD = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"
REG = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
W, H = 1280, 720
NAVY = (22, 32, 78)

DEFAULT_BADGES = {
    "INFO": ["#4658a0", "INFO"],
    "BEFORE": ["#be2832", "BEFORE FIX"],
    "AFTER": ["#168c50", "AFTER FIX"],
    "SUMMARY": ["#4658a0", "SUMMARY"],
}


def hex_to_rgb(value):
    value = value.lstrip("#")
    return tuple(int(value[i:i + 2], 16) for i in (0, 2, 4))


def font(path, size):
    return ImageFont.truetype(path, size)


def wrap(draw, text, fnt, width):
    lines = []
    for para in text.split("\n"):
        cur = ""
        for word in para.split(" "):
            test = (cur + " " + word).strip()
            if draw.textlength(test, font=fnt) <= width:
                cur = test
            else:
                lines.append(cur)
                cur = word
        lines.append(cur)
    return lines


def render_slide(index, total, slide, badges, video_label, path):
    img = Image.new("RGB", (W, H), (244, 246, 252))
    d = ImageDraw.Draw(img)
    d.rectangle([0, 0, W, 84], fill=NAVY)
    d.text((30, 14), slide.get("title", ""), font=font(BOLD, 32), fill="white")
    d.text((30, 54), video_label, font=font(REG, 17), fill=(190, 200, 235))

    color = (70, 90, 160)
    badge = badges.get(slide.get("badge", "INFO"))
    if badge:
        color = hex_to_rgb(badge[0])
        label = badge[1]
        bw = d.textlength(label, font=font(BOLD, 20)) + 36
        d.rounded_rectangle([W - bw - 24, 22, W - 24, 62], radius=10, fill=color)
        d.text((W - bw - 6, 30), label, font=font(BOLD, 20), fill="white")
    d.text((W - 110, H - 34), "%d/%d" % (index, total), font=font(REG, 18), fill=(110, 120, 150))

    text = slide.get("text", "")
    image_path = slide.get("image")
    if image_path:
        src = Image.open(image_path).convert("RGB")
        bx, by, bwid, bh = 30, 104, 790, 566
        scale = min(bwid / src.width, bh / src.height, 2.0)
        resized = src.resize((int(src.width * scale), int(src.height * scale)), Image.LANCZOS)
        x = bx + (bwid - resized.width) // 2
        y = by + (bh - resized.height) // 2
        d.rectangle([x - 3, y - 3, x + resized.width + 2, y + resized.height + 2],
                    outline=(150, 160, 190), width=2)
        img.paste(resized, (x, y))
        px, pw = 846, 404
        d.rounded_rectangle([px, 104, px + pw, 670], radius=14, fill="white",
                            outline=(210, 216, 235), width=2)
        fnt = font(REG, 23)
        y = 124
        for line in wrap(d, text, fnt, pw - 40):
            d.text((px + 20, y), line, font=fnt, fill=(30, 36, 60))
            y += 31
    else:
        d.rounded_rectangle([40, 110, W - 40, 670], radius=16, fill="white",
                            outline=(210, 216, 235), width=2)
        fnt = font(REG, 29)
        y = 150
        for line in wrap(d, text, fnt, W - 140):
            d.text((80, y), line, font=fnt, fill=(30, 36, 60))
            y += 40
    img.save(path)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("spec")
    parser.add_argument("--ffmpeg", default="ffmpeg")
    parser.add_argument("--workdir", required=True)
    args = parser.parse_args()

    with open(args.spec, encoding="utf-8") as handle:
        spec = json.load(handle)

    badges = dict(DEFAULT_BADGES)
    badges.update(spec.get("badges", {}))
    slides = spec["slides"]
    os.makedirs(args.workdir, exist_ok=True)

    concat_lines = []
    last = None
    for i, slide in enumerate(slides, 1):
        path = os.path.join(args.workdir, "slide_%02d.png" % i)
        render_slide(i, len(slides), slide, badges, spec.get("video_label", ""), path)
        seconds = slide.get("seconds") or min(3.5 + len(slide.get("text", "")) / 15.0, 20)
        concat_lines.append("file '%s'\nduration %.2f\n" % (path, seconds))
        last = path
    concat_lines.append("file '%s'\n" % last)

    concat_path = os.path.join(args.workdir, "concat.txt")
    with open(concat_path, "w", encoding="utf-8") as handle:
        handle.write("".join(concat_lines))

    subprocess.run([args.ffmpeg, "-y", "-loglevel", "error", "-f", "concat", "-safe", "0",
                    "-i", concat_path, "-vf", "fps=25,format=yuv420p", "-c:v", "libx264",
                    "-crf", "20", "-movflags", "+faststart", spec["output"]], check=True)
    print("wrote", spec["output"], os.path.getsize(spec["output"]) // 1024, "KB")


if __name__ == "__main__":
    sys.exit(main())

