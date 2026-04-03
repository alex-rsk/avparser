#!/usr/bin/env python3
import cv2
import numpy as np
import sys
import datetime
import os
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
        logging(f"Error: Could not load haystack image: {haystack_path}")
        return False
    
    if needle is None:
        logging(f"Error: Could not load needle image: {needle_path}")
        return False
    
    # Check if needle is larger than haystack
    if needle.shape[0] > haystack.shape[0] or needle.shape[1] > haystack.shape[1]:
        logging("Error: Needle image is larger than haystack image")
        return False
    
    # Template matching
    result = cv2.matchTemplate(haystack, needle, cv2.TM_CCOEFF_NORMED)
    
    # Get best match location
    min_val, max_val, min_loc, max_loc = cv2.minMaxLoc(result)
    
    logging(f"Best match confidence: {max_val:.4f}")
    
    if max_val >= threshold:
        top_left = max_loc
        h, w = needle.shape
        bottom_right = (top_left[0] + w, top_left[1] + h)
        
        # Draw rectangle on result (load color version)
        result_img = cv2.imread(haystack_path)
        cv2.rectangle(result_img, top_left, bottom_right, (0, 255, 0), 2)
        
        output_path = 'found.png'
        cv2.imwrite(output_path, result_img)
        
        logging(f"✓ Match found at position {top_left}")
        logging(f"  Bounding box: {top_left} to {bottom_right}")
        #logging(f"  Result saved to: {output_path}")
        return top_left[0]
    else:
        logging(f" No match found (threshold: {threshold})")
        logging(f"  Try lowering threshold (current best: {max_val:.4f})")
        return False
