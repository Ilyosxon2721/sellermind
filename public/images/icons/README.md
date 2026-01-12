# PWA Icons

This directory should contain PWA icons in the following sizes:

## Required Icons

- `icon-72x72.png` - Android Chrome
- `icon-96x96.png` - Android Chrome
- `icon-128x128.png` - Android Chrome
- `icon-144x144.png` - Android Chrome, Windows
- `icon-152x152.png` - iOS Safari
- `icon-192x192.png` - Android Chrome (main icon)
- `icon-384x384.png` - Android Chrome
- `icon-512x512.png` - Android Chrome (splash screen)
- `badge-72x72.png` - Notification badge

## How to Generate

### Option 1: Using ImageMagick (recommended)

```bash
cd /path/to/sellermind
chmod +x scripts/generate-pwa-icons.sh
./scripts/generate-pwa-icons.sh your-logo.png
```

### Option 2: Using online tool

1. Go to https://realfavicongenerator.net
2. Upload your logo (512x512 recommended)
3. Download the generated icons
4. Place them in this directory

### Option 3: Create placeholder with ImageMagick

```bash
# Install ImageMagick
sudo apt-get install imagemagick

# Create a simple placeholder
convert -size 512x512 xc:#2563eb \
    -fill white \
    -pointsize 200 \
    -gravity center \
    -annotate +0+0 'SM' \
    logo.png

# Then run the generator script
./scripts/generate-pwa-icons.sh logo.png
```

## Design Guidelines

- **Colors**: Blue background (#2563eb), white icon/text
- **Shape**: Square with 20% rounded corners
- **Style**: Simple, recognizable, works at small sizes
- **Format**: PNG with transparency (optional)
- **Safe zone**: Keep important elements within 80% of icon area
- **Testing**: Test on actual device at different sizes

## Current Status

⚠️ **Icons are missing!** PWA will work but installation prompt may not appear without proper icons.

Please generate icons using one of the methods above.
