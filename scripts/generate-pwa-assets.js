#!/usr/bin/env node

/**
 * PWA Assets Generator for SellerMind
 * Generates iOS splash screens and PWA screenshots
 */

import sharp from 'sharp';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Directories
const SPLASH_DIR = path.join(__dirname, '../public/images/splash');
const SCREENSHOTS_DIR = path.join(__dirname, '../public/images/screenshots');

// iOS Splash Screen sizes
const SPLASH_SIZES = [
    { width: 640, height: 1136, name: 'apple-splash-640x1136.png' },
    { width: 750, height: 1334, name: 'apple-splash-750x1334.png' },
    { width: 1242, height: 2208, name: 'apple-splash-1242x2208.png' },
    { width: 1125, height: 2436, name: 'apple-splash-1125x2436.png' },
    { width: 828, height: 1792, name: 'apple-splash-828x1792.png' },
    { width: 1242, height: 2688, name: 'apple-splash-1242x2688.png' },
    { width: 1170, height: 2532, name: 'apple-splash-1170x2532.png' },
    { width: 1284, height: 2778, name: 'apple-splash-1284x2778.png' },
    { width: 1179, height: 2556, name: 'apple-splash-1179x2556.png' },
    { width: 1290, height: 2796, name: 'apple-splash-1290x2796.png' },
    // iPad sizes
    { width: 1536, height: 2048, name: 'apple-splash-1536x2048.png' },
    { width: 1668, height: 2224, name: 'apple-splash-1668x2224.png' },
    { width: 1668, height: 2388, name: 'apple-splash-1668x2388.png' },
    { width: 2048, height: 2732, name: 'apple-splash-2048x2732.png' },
];

// Screenshot sizes for PWA install prompt
const SCREENSHOT_SIZES = [
    { width: 1280, height: 720, name: 'dashboard-wide.png' },
    { width: 750, height: 1334, name: 'dashboard-narrow.png' },
];

// SellerMind brand color
const BRAND_COLOR = '#2563eb';

/**
 * Generate SVG splash screen for given dimensions
 */
function generateSplashSVG(width, height) {
    // Calculate logo scale based on screen width
    const logoScale = Math.min(width, height) / 1170;
    const hexagonSize = 150 * logoScale;
    const fontSize = 48 * logoScale;
    const taglineSize = 24 * logoScale;

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${width} ${height}" width="${width}" height="${height}">
  <rect fill="${BRAND_COLOR}" width="${width}" height="${height}"/>
  <g transform="translate(${width/2}, ${height/2})">
    <path fill="white" d="M0 -150 L130 -75 L130 75 L0 150 L-130 75 L-130 -75 Z" opacity="0.15" transform="scale(${1.8 * logoScale})"/>
    <path fill="white" d="M0 -150 L130 -75 L130 75 L0 150 L-130 75 L-130 -75 Z" transform="scale(${1.2 * logoScale})"/>
    <path fill="${BRAND_COLOR}" d="M-40 -80 Q-40 -100 -20 -100 L40 -100 Q60 -100 60 -80 L60 -60 Q60 -40 40 -40 L-20 -40 Q-40 -40 -40 -20 L-40 20 Q-40 40 -20 40 L40 40 Q60 40 60 60 L60 80 Q60 100 40 100 L-40 100 Q-60 100 -60 80 L-60 60 L-30 60 L-30 70 Q-30 80 -20 80 L30 80 Q40 80 40 70 L40 50 Q40 40 30 40 L-30 40 Q-60 40 -60 10 L-60 -10 Q-60 -40 -30 -40 L30 -40 Q40 -40 40 -50 L40 -70 Q40 -80 30 -80 L-20 -80 Q-30 -80 -30 -70 L-30 -60 L-60 -60 L-60 -80 Q-60 -100 -40 -100 Z" transform="scale(${0.8 * logoScale})"/>
    <text fill="white" font-family="Inter, -apple-system, BlinkMacSystemFont, sans-serif" font-size="${fontSize}" font-weight="700" text-anchor="middle" y="${280 * logoScale}">SellerMind</text>
    <text fill="white" font-family="Inter, -apple-system, BlinkMacSystemFont, sans-serif" font-size="${taglineSize}" font-weight="400" text-anchor="middle" opacity="0.8" y="${320 * logoScale}">AI-powered marketplace management</text>
  </g>
</svg>`;
}

/**
 * Generate screenshot SVG (dashboard mockup)
 */
function generateScreenshotSVG(width, height, isWide) {
    const padding = isWide ? 40 : 20;
    const cardHeight = isWide ? 120 : 100;
    const cardGap = isWide ? 20 : 12;

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${width} ${height}" width="${width}" height="${height}">
  <!-- Background -->
  <rect fill="#f3f4f6" width="${width}" height="${height}"/>

  <!-- Top bar -->
  <rect fill="${BRAND_COLOR}" width="${width}" height="${isWide ? 60 : 80}"/>
  <text fill="white" font-family="Inter, -apple-system, BlinkMacSystemFont, sans-serif" font-size="${isWide ? 24 : 20}" font-weight="600" x="${padding}" y="${isWide ? 38 : 50}">SellerMind</text>

  <!-- Stats cards row -->
  <g transform="translate(${padding}, ${isWide ? 80 : 100})">
    ${isWide ? `
    <!-- 4 cards in a row for wide -->
    <rect fill="white" width="${(width - padding * 2 - cardGap * 3) / 4}" height="${cardHeight}" rx="12"/>
    <text fill="#6b7280" font-family="Inter, sans-serif" font-size="14" x="16" y="30">Revenue</text>
    <text fill="#111827" font-family="Inter, sans-serif" font-size="28" font-weight="700" x="16" y="70">$24,500</text>
    <text fill="#16a34a" font-family="Inter, sans-serif" font-size="14" x="16" y="100">+12.5%</text>

    <g transform="translate(${(width - padding * 2 - cardGap * 3) / 4 + cardGap}, 0)">
      <rect fill="white" width="${(width - padding * 2 - cardGap * 3) / 4}" height="${cardHeight}" rx="12"/>
      <text fill="#6b7280" font-family="Inter, sans-serif" font-size="14" x="16" y="30">Orders</text>
      <text fill="#111827" font-family="Inter, sans-serif" font-size="28" font-weight="700" x="16" y="70">156</text>
      <text fill="#16a34a" font-family="Inter, sans-serif" font-size="14" x="16" y="100">+8.3%</text>
    </g>

    <g transform="translate(${((width - padding * 2 - cardGap * 3) / 4 + cardGap) * 2}, 0)">
      <rect fill="white" width="${(width - padding * 2 - cardGap * 3) / 4}" height="${cardHeight}" rx="12"/>
      <text fill="#6b7280" font-family="Inter, sans-serif" font-size="14" x="16" y="30">Products</text>
      <text fill="#111827" font-family="Inter, sans-serif" font-size="28" font-weight="700" x="16" y="70">1,234</text>
      <text fill="#6b7280" font-family="Inter, sans-serif" font-size="14" x="16" y="100">Active</text>
    </g>

    <g transform="translate(${((width - padding * 2 - cardGap * 3) / 4 + cardGap) * 3}, 0)">
      <rect fill="white" width="${(width - padding * 2 - cardGap * 3) / 4}" height="${cardHeight}" rx="12"/>
      <text fill="#6b7280" font-family="Inter, sans-serif" font-size="14" x="16" y="30">Reviews</text>
      <text fill="#111827" font-family="Inter, sans-serif" font-size="28" font-weight="700" x="16" y="70">4.8</text>
      <text fill="#f59e0b" font-family="Inter, sans-serif" font-size="14" x="16" y="100">★★★★★</text>
    </g>
    ` : `
    <!-- 2x2 grid for narrow -->
    <rect fill="white" width="${(width - padding * 2 - cardGap) / 2}" height="${cardHeight}" rx="12"/>
    <text fill="#6b7280" font-family="Inter, sans-serif" font-size="12" x="12" y="24">Revenue</text>
    <text fill="#111827" font-family="Inter, sans-serif" font-size="22" font-weight="700" x="12" y="55">$24,500</text>
    <text fill="#16a34a" font-family="Inter, sans-serif" font-size="12" x="12" y="80">+12.5%</text>

    <g transform="translate(${(width - padding * 2 - cardGap) / 2 + cardGap}, 0)">
      <rect fill="white" width="${(width - padding * 2 - cardGap) / 2}" height="${cardHeight}" rx="12"/>
      <text fill="#6b7280" font-family="Inter, sans-serif" font-size="12" x="12" y="24">Orders</text>
      <text fill="#111827" font-family="Inter, sans-serif" font-size="22" font-weight="700" x="12" y="55">156</text>
      <text fill="#16a34a" font-family="Inter, sans-serif" font-size="12" x="12" y="80">+8.3%</text>
    </g>

    <g transform="translate(0, ${cardHeight + cardGap})">
      <rect fill="white" width="${(width - padding * 2 - cardGap) / 2}" height="${cardHeight}" rx="12"/>
      <text fill="#6b7280" font-family="Inter, sans-serif" font-size="12" x="12" y="24">Products</text>
      <text fill="#111827" font-family="Inter, sans-serif" font-size="22" font-weight="700" x="12" y="55">1,234</text>
      <text fill="#6b7280" font-family="Inter, sans-serif" font-size="12" x="12" y="80">Active</text>
    </g>

    <g transform="translate(${(width - padding * 2 - cardGap) / 2 + cardGap}, ${cardHeight + cardGap})">
      <rect fill="white" width="${(width - padding * 2 - cardGap) / 2}" height="${cardHeight}" rx="12"/>
      <text fill="#6b7280" font-family="Inter, sans-serif" font-size="12" x="12" y="24">Reviews</text>
      <text fill="#111827" font-family="Inter, sans-serif" font-size="22" font-weight="700" x="12" y="55">4.8</text>
      <text fill="#f59e0b" font-family="Inter, sans-serif" font-size="12" x="12" y="80">★★★★★</text>
    </g>
    `}
  </g>

  <!-- Chart area -->
  <g transform="translate(${padding}, ${isWide ? 220 : 340})">
    <rect fill="white" width="${width - padding * 2}" height="${isWide ? 300 : 250}" rx="12"/>
    <text fill="#111827" font-family="Inter, sans-serif" font-size="${isWide ? 18 : 16}" font-weight="600" x="20" y="35">Sales Analytics</text>

    <!-- Simple chart bars -->
    ${Array.from({length: isWide ? 12 : 7}, (_, i) => {
      const barWidth = isWide ? 60 : 40;
      const maxHeight = isWide ? 180 : 140;
      const barHeight = Math.random() * 0.6 * maxHeight + 0.4 * maxHeight;
      const x = 30 + i * (barWidth + (isWide ? 20 : 15));
      const y = (isWide ? 250 : 200) - barHeight;
      return `<rect fill="${BRAND_COLOR}" opacity="0.8" x="${x}" y="${y}" width="${barWidth}" height="${barHeight}" rx="4"/>`;
    }).join('\n    ')}
  </g>

  ${!isWide ? `
  <!-- Bottom tab bar for mobile -->
  <rect fill="white" y="${height - 70}" width="${width}" height="70"/>
  <line stroke="#e5e7eb" x1="0" y1="${height - 70}" x2="${width}" y2="${height - 70}"/>
  ` : ''}
</svg>`;
}

async function generateSplashScreens() {
    console.log('Generating iOS splash screens...');

    // Ensure directory exists
    if (!fs.existsSync(SPLASH_DIR)) {
        fs.mkdirSync(SPLASH_DIR, { recursive: true });
    }

    for (const size of SPLASH_SIZES) {
        const svg = generateSplashSVG(size.width, size.height);
        const outputPath = path.join(SPLASH_DIR, size.name);

        await sharp(Buffer.from(svg))
            .png()
            .toFile(outputPath);

        console.log(`  ✓ ${size.name} (${size.width}x${size.height})`);
    }
}

async function generateScreenshots() {
    console.log('\nGenerating PWA screenshots...');

    // Ensure directory exists
    if (!fs.existsSync(SCREENSHOTS_DIR)) {
        fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
    }

    for (const size of SCREENSHOT_SIZES) {
        const isWide = size.width > size.height;
        const svg = generateScreenshotSVG(size.width, size.height, isWide);
        const outputPath = path.join(SCREENSHOTS_DIR, size.name);

        await sharp(Buffer.from(svg))
            .png()
            .toFile(outputPath);

        console.log(`  ✓ ${size.name} (${size.width}x${size.height})`);
    }
}

async function main() {
    console.log('SellerMind PWA Assets Generator\n');

    try {
        await generateSplashScreens();
        await generateScreenshots();

        console.log('\n✅ All PWA assets generated successfully!');
        console.log(`\nSplash screens: ${SPLASH_DIR}`);
        console.log(`Screenshots: ${SCREENSHOTS_DIR}`);
    } catch (error) {
        console.error('\n❌ Error generating assets:', error);
        process.exit(1);
    }
}

main();
