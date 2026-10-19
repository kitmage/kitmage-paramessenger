// Requires Playwright and Chromium. Run: node tests/placement.cjs
const assert = require('node:assert/strict');
const { chromium } = require('playwright');

(async () => {
    const base = process.env.WORDPRESS_TEST_URL || 'http://127.0.0.1:8080/';
    const response = await fetch(`${base}?kmpm=Hello%20world&kmpc=purchase-agreement`);
    assert.equal(response.status, 200);
    const html = await response.text();
    const renderer = html.match(/<template><div class="kitmage-paramessenger[\s\S]*?<\/script>/);
    assert.ok(renderer, 'WordPress emits the message and placement script');
    const browser = await chromium.launch({
        executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium',
        headless: true,
    });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        for (const ready of [false, true]) {
            await page.setContent('<main id="content">existing text<p>Existing child</p></main>');
            if (ready) {
                await page.evaluate(markup => {
                    const container = document.createElement('div');
                    container.innerHTML = markup;
                    document.body.append(container.firstElementChild);
                    const script = document.createElement('script');
                    script.textContent = container.querySelector('script').textContent;
                    document.body.append(script);
                }, renderer[0]);
            } else {
                await page.setContent(renderer[0] + '<main id="content">existing text<p>Existing child</p></main>');
            }
            assert.equal(await page.locator('#content').evaluate(element =>
                element.firstChild.matches('.kitmage-paramessenger.purchase-agreement')), true);
            assert.equal(await page.locator('#content > .kitmage-paramessenger').textContent(), 'Hello world');
            assert.equal(await page.locator('#content > p').textContent(), 'Existing child');
            assert.equal(await page.locator('.kitmage-paramessenger').count(), 1);
        }
        await page.setContent(renderer[0] + '<main>No content target</main>');
        assert.equal(await page.locator('.kitmage-paramessenger').count(), 0);
        assert.deepEqual(errors, []);
        console.log('PASS: first-child placement before/after DOM ready, existing content, and missing target');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
