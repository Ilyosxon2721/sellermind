#!/bin/bash

# Script to generate PWA icons from a source image
# Requires ImageMagick: sudo apt-get install imagemagick

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
SOURCE_IMAGE="${1:-logo.png}"
OUTPUT_DIR="public/images/icons"
SIZES=(72 96 128 144 152 192 384 512)

echo -e "${GREEN}=== PWA Icon Generator ===${NC}\n"

# Check if ImageMagick is installed
if ! command -v convert &> /dev/null; then
    echo -e "${RED}Error: ImageMagick is not installed${NC}"
    echo "Install it with: sudo apt-get install imagemagick"
    exit 1
fi

# Check if source image exists
if [ ! -f "$SOURCE_IMAGE" ]; then
    echo -e "${RED}Error: Source image '$SOURCE_IMAGE' not found${NC}"
    echo "Usage: $0 <source_image.png>"
    echo ""
    echo "Example:"
    echo "  $0 logo.png"
    echo ""
    echo "The source image should be:"
    echo "  - At least 512x512 pixels"
    echo "  - PNG format with transparency (optional)"
    echo "  - Square aspect ratio"
    exit 1
fi

# Create output directory
mkdir -p "$OUTPUT_DIR"

echo "Source image: $SOURCE_IMAGE"
echo "Output directory: $OUTPUT_DIR"
echo ""

# Generate icons
for size in "${SIZES[@]}"; do
    output_file="$OUTPUT_DIR/icon-${size}x${size}.png"

    echo -ne "Generating ${size}x${size}... "

    convert "$SOURCE_IMAGE" \
        -resize "${size}x${size}" \
        -background none \
        -gravity center \
        -extent "${size}x${size}" \
        "$output_file"

    echo -e "${GREEN}✓${NC}"
done

# Generate badge icon (smaller, for notifications)
echo -ne "Generating badge icon... "
convert "$SOURCE_IMAGE" \
    -resize "72x72" \
    -background none \
    -gravity center \
    -extent "72x72" \
    "$OUTPUT_DIR/badge-72x72.png"
echo -e "${GREEN}✓${NC}"

echo ""
echo -e "${GREEN}=== Success! ===${NC}"
echo ""
echo "Generated icons:"
ls -lh "$OUTPUT_DIR"/*.png
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Check the generated icons in $OUTPUT_DIR"
echo "2. Update manifest.json if needed"
echo "3. Test PWA installation on mobile device"
echo ""
echo "Tip: To create a simple logo with ImageMagick:"
echo "  convert -size 512x512 xc:#2563eb -fill white -pointsize 200 -gravity center -annotate +0+0 'SM' logo.png"
