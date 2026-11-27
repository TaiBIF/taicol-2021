import { test as base, expect, type Page } from '@playwright/test';

/**
 * 每個測試自動檢查：
 * - 頁面沒有未捕捉的 JS 例外（pageerror）
 * - 沒有任何 /api/* 回 5xx
 * 已知、暫時無法修的錯誤加到 ignoredErrors（並在交接文件記錄）。
 */
const ignoredErrors: RegExp[] = [];

type Guard = { allow5xx: (pattern: RegExp) => void };

export const test = base.extend<{ guard: Guard }>({
    guard: [async ({ page }, use) => {
        const pageErrors: string[] = [];
        const serverErrors: string[] = [];
        const allowed: RegExp[] = [];

        page.on('pageerror', (err) => {
            if (!ignoredErrors.some((re) => re.test(err.message))) {
                pageErrors.push(err.message);
            }
        });
        page.on('response', (res) => {
            const url = res.url();
            if (url.includes('/api/') && res.status() >= 500 && !allowed.some((re) => re.test(url))) {
                serverErrors.push(`${res.status()} ${res.request().method()} ${url}`);
            }
        });

        await use({ allow5xx: (pattern) => allowed.push(pattern) });

        expect(pageErrors, '頁面 JS 例外').toEqual([]);
        expect(serverErrors, 'API 5xx').toEqual([]);
    }, { auto: true }],
});

export { expect };

/** 等某支 API 回應（path 為 /api 之後的路徑或正規式） */
export function waitForApi(page: Page, path: string | RegExp, method = 'GET') {
    return page.waitForResponse((res) => {
        const { pathname } = new URL(res.url());
        const p = pathname.replace(/^\/api/, '');
        const hit = typeof path === 'string' ? p === path : path.test(p);
        return hit && pathname.startsWith('/api/') && res.request().method() === method;
    });
}

/** 綜合搜尋：在 vue-select 輸入關鍵字 → Tab 產生 tag → 按放大鏡 */
export async function search(page: Page, keyword: string) {
    const input = page.locator('.vs__search').first();
    await input.fill(keyword);
    await input.press('Tab');
    await expect(page.locator('.selected-tag-item', { hasText: keyword }).first()).toBeVisible();
    await page.locator('.fa-search').first().click();
}

/** 頁首下拉選單（hover 才會顯示） */
export async function openHeaderMenu(page: Page, label: string | RegExp) {
    await page.locator('.header .navbar-item', { hasText: label }).first().hover();
}
