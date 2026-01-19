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

def process_small_image(path, threshold):

    try:
        if not os.path.exists(path):
            logging(f"Error: Small image not found: {path}")
            return False

        im_small = assets_dir / "IM_SMALL.png"        
        temp_file = assets_dir / "temp_small.png"
        
        # Step 1: Threshold
        cmd_threshold = [
            "convert",
            small_image,
            "-threshold", "25%",
            str(temp_file)
        ]
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
            str(im_small)
        ]
        subprocess.run(cmd_fill, check=True, capture_output=True, text=True)
        
        # Clean up temp file
        temp_file.unlink()
        
        # Step 3: Negate the result
        cmd_negate = [
            "convert",
            str(im_small),
            "-negate",
            str(im_small)
        ]
        subprocess.run(cmd_negate, check=True, capture_output=True, text=True)
    except subprocess.CalledProcessError as e:
        logging(f" Error processing small image:")
        logging(f"    {e.stderr}")
        # Clean up temp file if it exists
        if temp_file.exists():
            temp_file.unlink()
        return False


def process_big_image(path, threshold, puzzle_top_coord, puzzle_height):

    if not os.path.exists(path):
        logging(f"Error: Big image not found: {path}")
        return False

    temp_file = assets_dir / "temp_big.png"    

    im_large = assets_dir / "IM_LARGE.png"

    if not os.path.exists(path):
        logging(f"Error: Large image not found: {path}")
        return False

    try:
        cmd = [
            "convert",
            path,
            "-crop", "0x" + str(puzzle_height) + "+0+" + str(puzzle_top_coord),
            str(temp_file)
        ]
        
        subprocess.run(cmd, check=True)
    except subprocess.CalledProcessError as e:
        print(f"    {e.stderr}")
        return False

    try:
        cmd = [
            "convert",
            temp_file,
            "-threshold",
            str(threshold),
            im_large
        ]

        subprocess.run(cmd, check=True)
        return True
    except subprocess.CalledProcessError as e:
        print(f"    {e.stderr}")
        return False


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python script.py <small_image> <large_image> <threshold_small> <threshold_large> <puzzle_top_coord> <puzzle_height>")
        print()
        print("Process images with ImageMagick:")
        print("  - Large image: threshold  → assets/IM_LARGE.png")
        print("  - Small image: threshold + fill contours + negate → assets/IM_SMALL.png")
        print()
        print("Arguments:")
        print("  small_image - Path to small image (template/needle)")
        print("  large_image - Path to large image (haystack)")
        print("  threshold 1 - threshold for small image")
        print("  threshold 2 - threshold for large image")        
        print()
        print("Example:")
        print("  python script.py heart.png screenshot.png")
        print("  python script.py heart.png screenshot.png --alt")
        sys.exit(1)
    
    small_image = sys.argv[1]
    large_image = sys.argv[2]
    
    if len(sys.argv) < 4 or sys.argv[3] == "":
        threshold_sm = "25%"
    else:
        threshold_sm =  sys.argv[3] + "%"        
    
    if len(sys.argv) < 5 or sys.argv[4] == "":
        threshold_lg = "47%"
    else:
        threshold_lg =  sys.argv[4] + "%"        

    if len(sys.argv) < 6 or sys.argv[5] == "":
        puzzle_top_coord = 0
    else:
        puzzle_top_coord = int(sys.argv[5])

    if len(sys.argv) < 6 or sys.argv[5] == "":
        puzzle_top_coord = 0
    else:
        puzzle_top_coord = int(sys.argv[5])

    if len(sys.argv) < 7 or sys.argv[6] == "":
        puzzle_height = 80
    else:
        puzzle_height = int(sys.argv[6])    
    
    assets_dir = Path("assets")
    assets_dir.mkdir(exist_ok=True)
    process_small_image(small_image, threshold_sm)
    process_big_image(large_image, threshold_lg, puzzle_top_coord, puzzle_height)
        
    sys.exit(0)
