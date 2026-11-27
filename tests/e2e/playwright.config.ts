import { defineConfig, devices } from '@playwright/test';
import { env } from './helpers/env';

export default defineConfig({
    testDir: '.',
    timeout: 60_000,
    expect: { timeout: 15_000 },
    fullyParallel: false,
    workers: 2,
    retries: 0,
    reporter: [['list'], ['html', { open: 'never' }]],
    globalTeardown: './setup/global-teardown.ts',
    use: {
        baseURL: env.baseURL,
        locale: 'zh-TW',
        timezoneId: 'Asia/Taipei',
        viewport: { width: 1440, height: 900 },
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        ignoreHTTPSErrors: true,
    },
    projects: [
        // 用 API 登入一般與管理員帳號，存成 .auth/*.json
        {
            name: 'setup',
            testMatch: /setup\/auth\.setup\.ts/,
        },
        {
            name: 'chromium',
            testMatch: /specs\/.*\.spec\.ts/,
            testIgnore: /specs\/auth-logout\.spec\.ts/,
            dependencies: ['setup'],
            use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } },
        },
        // 登出會刪掉該帳號全部 token，必須等其他 spec 跑完
        {
            name: 'logout',
            testMatch: /specs\/auth-logout\.spec\.ts/,
            dependencies: ['chromium'],
            use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } },
        },
    ],
});
