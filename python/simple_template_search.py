#!/usr/bin/env python3
import cv2
import numpy as np
import sys

def find_subimage(needle_path, haystack_path, threshold=0.8):
    """
    Find needle image within haystack image.
    
    Args:
        needle_path: Path to small image (template to find)
        haystack_path: Path to large image (image to search in)
        threshold: Matching threshold (0-1, higher = stricter)
    """
    # Load images
    haystack = cv2.imread(haystack_path, cv2.IMREAD_GRAYSCALE)
    needle = cv2.imread(needle_path, cv2.IMREAD_GRAYSCALE)
    
    if haystack is None:
        print(f"Error: Could not load haystack image: {haystack_path}")
        return False
    
    if needle is None:
        print(f"Error: Could not load needle image: {needle_path}")
        return False
    
    # Check if needle is larger than haystack
    if needle.shape[0] > haystack.shape[0] or needle.shape[1] > haystack.shape[1]:
        print("Error: Needle image is larger than haystack image")
        return False
    
    # Template matching
    result = cv2.matchTemplate(haystack, needle, cv2.TM_CCOEFF_NORMED)
    
    # Get best match location
    min_val, max_val, min_loc, max_loc = cv2.minMaxLoc(result)
    
    print(f"Best match confidence: {max_val:.4f}")
    
    if max_val >= threshold:
        top_left = max_loc
        h, w = needle.shape
        bottom_right = (top_left[0] + w, top_left[1] + h)
        
        # Draw rectangle on result (load color version)
        result_img = cv2.imread(haystack_path)
        cv2.rectangle(result_img, top_left, bottom_right, (0, 255, 0), 2)
        
        output_path = 'found.png'
        cv2.imwrite(output_path, result_img)
        
        print(f"✓ Match found at position {top_left}")
        print(f"  Bounding box: {top_left} to {bottom_right}")
        print(f"  Result saved to: {output_path}")
        return True
    else:
        print(f"✗ No match found (threshold: {threshold})")
        print(f"  Try lowering threshold (current best: {max_val:.4f})")
        return False

def find_all_matches(needle_path, haystack_path, threshold=0.8):
    """Find all occurrences of needle in haystack."""
    haystack = cv2.imread(haystack_path, cv2.IMREAD_GRAYSCALE)
    needle = cv2.imread(needle_path, cv2.IMREAD_GRAYSCALE)
    
    if haystack is None or needle is None:
        print("Error loading images")
        return
    
    result = cv2.matchTemplate(haystack, needle, cv2.TM_CCOEFF_NORMED)
    
    # Find all matches above threshold
    locations = np.where(result >= threshold)
    
    h, w = needle.shape
    result_img = cv2.imread(haystack_path)
    
    match_count = 0
    for pt in zip(*locations[::-1]):
        cv2.rectangle(result_img, pt, (pt[0] + w, pt[1] + h), (0, 255, 0), 2)
        match_count += 1
    
    if match_count > 0:
        output_path = 'all_matches.png'
        cv2.imwrite(output_path, result_img)
        print(f"✓ Found {match_count} matches")
        print(f"  Result saved to: {output_path}")
    else:
        print("✗ No matches found")

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python script.py <small_image> <large_image> [threshold] [--all]")
        print("\nArguments:")
        print("  small_image  - Path to template/needle image (image to find)")
        print("  large_image  - Path to haystack image (image to search in)")
        print("  threshold    - Optional: matching threshold 0-1 (default: 0.8)")
        print("  --all        - Optional: find all matches instead of best match")
        print("\nExample:")
        print("  python script.py heart.png screenshot.png 0.75")
        print("  python script.py heart.png screenshot.png 0.8 --all")
        sys.exit(1)
    
    needle_path = sys.argv[1]
    haystack_path = sys.argv[2]
    
    # Parse optional threshold
    threshold = 0.8
    find_all = False
    
    for arg in sys.argv[3:]:
        if arg == "--all":
            find_all = True
        else:
            try:
                threshold = float(arg)
                if threshold < 0 or threshold > 1:
                    print("Warning: threshold should be between 0 and 1")
                    threshold = max(0, min(1, threshold))
            except ValueError:
                print(f"Warning: ignoring invalid argument: {arg}")
    
    print(f"Searching for: {needle_path}")
    print(f"In image: {haystack_path}")
    print(f"Threshold: {threshold}")
    print()
    
    if find_all:
        find_all_matches(needle_path, haystack_path, threshold)
    else:
        find_subimage(needle_path, haystack_path, threshold)
