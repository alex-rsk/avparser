#!/usr/bin/env python3
import subprocess
import sys
import os
from pathlib import Path



def process_small_image(path, threshold):

    # Check if input file exists
    if not os.path.exists(path):
        print(f"Error: Small image not found: {path}")
        return False

    print(f"Processing small image...")
    try:
        cmd_small = [
            "convert",
            path,
            "-threshold",
            str(threshold),
            "assets/IM_SMALL.png"
        ]
        subprocess.run(cmd_small, check=True)
        return True
    except subprocess.CalledProcessError as e:
        print(f"Error: {e}")
        return False


def process_images(small_image, large_image):
    """
    Process images with ImageMagick threshold operations.
    
    Args:
        small_image: Path to small image (will be thresholded, filled, and negated)
        large_image: Path to large image (will be thresholded)
    """
    # Create assets directory if it doesn't exist
    assets_dir = Path("assets")
    assets_dir.mkdir(exist_ok=True)
    
    # Define output paths
    im_small = assets_dir / "IM_SMALL.png"
    im_large = assets_dir / "IM_LARGE.png"
    
    print(f"Processing images...")
    print(f"  Small image: {small_image}")
    print(f"  Large image: {large_image}")
    print(f"  Output directory: {assets_dir}")
    print()
    
    # Check if input files exist
    if not os.path.exists(small_image):
        print(f"Error: Small image not found: {small_image}")
        return False
    
    if not os.path.exists(large_image):
        print(f"Error: Large image not found: {large_image}")
        return False
    
    # Process large image: threshold 45%
    print(f"Processing large image...")
    try:
        cmd_large = [
            "convert",
            large_image,
            "-threshold", "55%",
            str(im_large)
        ]
        result = subprocess.run(cmd_large, check=True, capture_output=True, text=True)
        print(f"  ✓ Saved: {im_large}")
    except subprocess.CalledProcessError as e:
        print(f"  ✗ Error processing large image:")
        print(f"    {e.stderr}")
        return False
    except FileNotFoundError:
        print("  ✗ Error: ImageMagick 'convert' command not found")
        print("    Please install ImageMagick: sudo apt install imagemagick")
        return False
    
    # Process small image: threshold 45%, fill contours, and negate
    print(f"Processing small image...")
    try:
        # Create a temporary file for intermediate processing
        temp_file = assets_dir / "temp_small.png"
        
        # Step 1: Threshold
        cmd_threshold = [
            "convert",
            small_image,
            "-threshold", "25%",
            str(temp_file)
        ]
        subprocess.run(cmd_threshold, check=True, capture_output=True, text=True)
        
        # Step 2: Fill contours using morphology close operation and floodfill
        # This approach fills holes inside the contours
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
        
        print(f"  ✓ Saved: {im_small}")
    except subprocess.CalledProcessError as e:
        print(f"  ✗ Error processing small image:")
        print(f"    {e.stderr}")
        # Clean up temp file if it exists
        if temp_file.exists():
            temp_file.unlink()
        return False
    
    print()
    print("✓ Processing complete!")
    print(f"  Output files in: {assets_dir}/")
    print(f"    - IM_SMALL.png (thresholded, filled, negated)")
    print(f"    - IM_LARGE.png (thresholded)")
    
    return True


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python script.py <small_image> <large_image> <threshold_small> <threshold_large>")
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
    threshold_sm =  sys.argv[3]
    if len(sys.argv) < 4 or sys.argv[3] == "":
        threshold_sm = "25"
    else:
        threshold_sm = sys.argv[3]

    threshold_lg =  sys.argv[4]
    if len(sys.argv) < 5 or sys.argv[4] == "":
        threshold_lg = "55"
    else:
        threshold_lg = sys.argv[3]
        
    print(f"Processing images with threshold_small={threshold_sm}, threshold_large={threshold_lg}")    
    //success = process_images(small_image, large_image)
    
    sys.exit(0 if success else 1)
