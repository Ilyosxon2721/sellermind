/**
 * Генерация PWA иконок для SellerMind
 * Использует Sharp для создания PNG иконок
 */

import sharp from 'sharp';
import { mkdir, writeFile } from 'fs/promises';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const outputDir = join(__dirname, '../public/images/icons');

// Размеры иконок для PWA
const sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// Цвета бренда
const brandColor = '#2563eb'; // Blue-600
const textColor = '#ffffff';

/**
 * Создаёт SVG с логотипом SellerMind
 */
function createLogoSVG(size) {
    const padding = Math.round(size * 0.15);
    const innerSize = size - padding * 2;
    const fontSize = Math.round(size * 0.35);
    const cornerRadius = Math.round(size * 0.18);

    return `
        <svg width="${size}" height="${size}" xmlns="http://www.w3.org/2000/svg">
            <rect x="0" y="0" width="${size}" height="${size}" rx="${cornerRadius}" ry="${cornerRadius}" fill="${brandColor}"/>
            <text
                x="${size / 2}"
                y="${size / 2 + fontSize * 0.35}"
                font-family="Arial, sans-serif"
                font-size="${fontSize}"
                font-weight="bold"
                fill="${textColor}"
                text-anchor="middle"
            >SM</text>
            <path
                d="M${size * 0.3} ${size * 0.7} L${size * 0.5} ${size * 0.55} L${size * 0.7} ${size * 0.7}"
                stroke="${textColor}"
                stroke-width="${Math.max(2, size * 0.03)}"
                fill="none"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    `.trim();
}

/**
 * Создаёт SVG для badge (notification)
 */
function createBadgeSVG(size) {
    const cornerRadius = Math.round(size * 0.2);

    return `
        <svg width="${size}" height="${size}" xmlns="http://www.w3.org/2000/svg">
            <rect x="0" y="0" width="${size}" height="${size}" rx="${cornerRadius}" ry="${cornerRadius}" fill="${brandColor}"/>
            <circle cx="${size / 2}" cy="${size / 2}" r="${size * 0.25}" fill="${textColor}"/>
        </svg>
    `.trim();
}

async function generateIcons() {
    console.log('🎨 Генерация PWA иконок для SellerMind...\n');

    // Создаём директорию если не существует
    await mkdir(outputDir, { recursive: true });

    // Генерируем основные иконки
    for (const size of sizes) {
        const svg = createLogoSVG(size);
        const filename = `icon-${size}x${size}.png`;
        const filepath = join(outputDir, filename);

        await sharp(Buffer.from(svg))
            .png()
            .toFile(filepath);

        console.log(`✅ ${filename}`);
    }

    // Генерируем badge для уведомлений
    const badgeSVG = createBadgeSVG(72);
    await sharp(Buffer.from(badgeSVG))
        .png()
        .toFile(join(outputDir, 'badge-72x72.png'));
    console.log('✅ badge-72x72.png');

    // Генерируем maskable иконку (с отступами)
    const maskableSize = 512;
    const maskableSVG = `
        <svg width="${maskableSize}" height="${maskableSize}" xmlns="http://www.w3.org/2000/svg">
            <rect width="${maskableSize}" height="${maskableSize}" fill="${brandColor}"/>
            <text
                x="${maskableSize / 2}"
                y="${maskableSize / 2 + 60}"
                font-family="Arial, sans-serif"
                font-size="180"
                font-weight="bold"
                fill="${textColor}"
                text-anchor="middle"
            >SM</text>
            <path
                d="M${maskableSize * 0.35} ${maskableSize * 0.68} L${maskableSize * 0.5} ${maskableSize * 0.58} L${maskableSize * 0.65} ${maskableSize * 0.68}"
                stroke="${textColor}"
                stroke-width="12"
                fill="none"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    `.trim();

    await sharp(Buffer.from(maskableSVG))
        .png()
        .toFile(join(outputDir, 'maskable-512x512.png'));
    console.log('✅ maskable-512x512.png');

    console.log('\n✨ Все иконки сгенерированы в:', outputDir);
}

generateIcons().catch(console.error);
