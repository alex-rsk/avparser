import sys
import os
import configparser
from stage1 import logging 
from stage1 import process_small_image
from stage1 import process_big_image
from stage2 import find_subimage
from pathlib import Path

config = configparser.ConfigParser()

config.read(os.path.abspath('capsolver/config.ini'));

threshold_sm = config['thresholds']['small_threshold'] + '%'
threshold_lg = config['thresholds']['large_threshold'] + '%'

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
    puzzle_top_coord = 0
else:
    puzzle_top_coord = int(sys.argv[3])

if len(sys.argv) < 5 or sys.argv[4] == "":
    puzzle_height = 80
else:
    puzzle_height = int(sys.argv[4])    

assets_dir = Path("capsolver/assets")
assets_dir.mkdir(exist_ok=True)

process_small_image(small_image, threshold_sm)
process_big_image(large_image, threshold_lg, puzzle_top_coord, puzzle_height)

needle_path = os.path.join(assets_dir, "IM_SMALL.png")
haystack_path = os.path.join(assets_dir, "IM_LARGE.png")
find_threshold = 0.15
res = find_subimage(needle_path, haystack_path, find_threshold)

if res:
    logging('Left coord: ' + str(res))
    print(res)
else:
    logging("Nothing found")



sys.exit(0)