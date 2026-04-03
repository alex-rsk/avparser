#!/usr/bin/env python3
import subprocess
import sys
import os
import datetime
from pathlib import Path

def logging(message):    
    logs_dir = Path("logs")
    logs_dir.mkdir(exist_ok=True)
    log_file = os.path.join(logs_dir, "log.txt")
    try:
        dt = datetime.datetime.now()        
        with open(log_file, 'a', encoding='utf-8') as f:
            f.write(dt.strftime('%Y-%m-%d_%H-%M-%S') + message + '\n')
    except IOError as e:
        print(f"An error occurred while writing to the file: {e}")

def process_small_image(task_uuid, path, threshold):

    try:
        if not os.path.exists(path):
            logging(f"Error: Small image not found: {path}")
            return False

        im_small = path
        assets_dir = os.path.join(os.path.dirname(os.path.dirname(im_small)), "assets")
        temp_file = Path(os.path.join(assets_dir,  "temp_small_" + task_uuid + ".png"))
        outfile = Path(os.path.join(assets_dir,  "IM_SMALL_"+task_uuid+".png"))
        # Step 1: Threshold
        cmd_threshold = [
            "convert",
            im_small,
            "-threshold", "25%",
            str(temp_file)
        ]

        logging(" ".join(cmd_threshold))
        subprocess.run(cmd_threshold, check=True, capture_output=True, text=True)

        cmd_fill = [
            "convert",
            str(temp_file),
            "-morphology", "Close", "Disk:2",  # Close small gaps
            "-fill", "white",
            "-draw", "color 0,0 floodfill",     # Fill outside (assuming background is at 0,0)
            "-negate",                           # Invert so inside is white
            "-fill", "black",
            "-draw", "color 0,0 floodfill",     # Fill outside with black
            "-negate",                           # Invert back
            str(outfile)
        ]

        logging(" ".join(cmd_fill))
        subprocess.run(cmd_fill, check=True, capture_output=True, text=True)
        
        # Step 3: Negate the result
        cmd_negate = [
            "convert",
            str(temp_file),
            "-negate",
            str(outfile)
        ]

        # print(cmd_negate)
        subprocess.run(cmd_negate, check=True, capture_output=True, text=True)
        if temp_file.exists():
            temp_file.unlink()
    except subprocess.CalledProcessError as e:
        logging(f" Error processing small image:")
        logging(f"    {e.stderr}")
        # Clean up temp file if it exists
        if temp_file.exists():
            temp_file.unlink()
        return False


def process_big_image(task_uuid, path, threshold, puzzle_top_coord, puzzle_height):

    if not os.path.exists(path):
        logging(f"Error: Big image not found: {path}")
        return False

    assets_dir = os.path.join(os.path.dirname(os.path.dirname(path)), "assets")

    temp_file = Path(os.path.join(assets_dir,  "temp_big_" + task_uuid + ".png"))

    im_large = path

    out_file = Path(os.path.join(assets_dir,  "IM_LARGE_" + task_uuid + ".png"))

    if not os.path.exists(path):
        logging(f"Error: Large image not found: {path}")
        return False

    try:
        cmd = [
            "convert",
            im_large,
            "-crop", "0x" + str(puzzle_height) + "+0+" + str(puzzle_top_coord),
            str(temp_file)
        ]
        
        subprocess.run(cmd, check=True)
        
    except subprocess.CalledProcessError as e:
        print(f"{e.stderr}")
        return False

    try:
        cmd = [
            "convert",
            temp_file,
            "-threshold",
            str(threshold),
            out_file
        ]
        # print(cmd)
        subprocess.run(cmd, check=True)
        if temp_file.exists():
            temp_file.unlink()
        return True
    except subprocess.CalledProcessError as e:
        print(f"    {e.stderr}")
        return False
