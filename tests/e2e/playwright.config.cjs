const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: __dirname,
    testMatch: '**/*.spec.cjs',
    outputDir: 'test-results',
    fullyParallel: false,
    workers: 1,
    forbidOnly: Boolean(process.env.CI),
    retries: 0,
    timeout: 15000,
    reporter: 'list',
    use: {
        browserName: 'chromium',
        baseURL: 'http://127.0.0.1:8787',
        viewport: { width: 1280, height: 800 },
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: 'node servidor.cjs',
        cwd: __dirname,
        url: 'http://127.0.0.1:8787/nosotros',
        reuseExistingServer: false,
        timeout: 15000,
    },
});
