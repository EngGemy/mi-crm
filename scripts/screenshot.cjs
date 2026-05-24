const { chromium } = require('playwright');

(async () => {
    const htmlPath = process.argv[2];
    const outputPath = process.argv[3];
    const width = parseInt(process.argv[4], 10) || 1240;
    const height = parseInt(process.argv[5], 10) || 1754;

    if (!htmlPath || !outputPath) {
        console.error('Usage: node screenshot.cjs <html-path> <output-path> [width] [height]');
        process.exit(1);
    }

    const browser = await chromium.launch();
    const page = await browser.newPage({
        viewport: { width, height },
    });

    const fileUrl = 'file:///' + htmlPath.replace(/\\/g, '/');
    await page.goto(fileUrl, { waitUntil: 'networkidle' });

    // Wait for web fonts to load before screenshotting
    await page.waitForFunction(() => document.fonts.ready.then(() => true));
    // Small extra delay to ensure layout settles
    await page.waitForTimeout(300);

    await page.screenshot({
        path: outputPath,
        fullPage: false,
        type: 'png',
    });

    await browser.close();
    console.log('Screenshot saved to:', outputPath);
})();
