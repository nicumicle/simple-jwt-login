import { defineConfig } from '@playwright/test';

// Every test changes the same WordPress option (the plugin settings): one worker, in order.
export default defineConfig({
  testDir: './specs',
  workers: 1,
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: 0,
  timeout: 30_000,
  globalSetup: './support/global-setup.mjs',
  // "line": progress as [n/total], failures printed as soon as they happen.
  reporter: process.env.CI
    ? [['line'], ['github'], ['html', { open: 'never' }]]
    : [['line'], ['html', { open: 'never' }]],
  use: {
    // The installed Google Chrome (nothing to download). PW_CHANNEL=chromium uses Playwright's own.
    channel: process.env.PW_CHANNEL || 'chrome',
    headless: true,
    trace: 'retain-on-failure',
  },
});
