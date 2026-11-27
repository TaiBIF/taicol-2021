import { test, expect, waitForApi, openHeaderMenu } from '../helpers/fixtures';
import { adminState, env, userState } from '../helpers/env';
import type { Page } from '@playwright/test';

const field = (page: Page, label: string) => page.locator('.field', { hasText: label }).locator('input');

async function fillLogin(page: Page, email: string, password: string) {
    await field(page, '電子信箱').fill(email);
    await field(page, '密碼').fill(password);
    await page.getByRole('button', { name: '登入' }).click();
}

test.describe('登入（未登入狀態）', () => {
    test('需登入頁面導向登入頁並帶 redirect', async ({ page }) => {
        await page.goto('/zh-tw/namespaces');
        await expect(page).toHaveURL(/\/zh-tw\/login\?redirect=/);
        await expect(page.getByRole('button', { name: '登入' })).toBeVisible();
    });

    test('密碼錯誤顯示錯誤訊息', async ({ page }) => {
        await page.goto('/zh-tw/login');
        const res = waitForApi(page, '/login', 'POST');
        await fillLogin(page, env.user.email, `${env.user.password}-wrong`);
        expect((await res).status()).toBe(422);
        await expect(page.getByText('帳號密碼不符或該組帳號尚未開通。')).toBeVisible();
        await expect(page).toHaveURL(/\/zh-tw\/login/);
    });

    test('登入後回到 redirect 頁面', async ({ page }) => {
        await page.goto('/zh-tw/namespaces');
        await expect(page).toHaveURL(/\/zh-tw\/login\?redirect=/);

        const res = waitForApi(page, '/login', 'POST');
        await fillLogin(page, env.user.email, env.user.password);
        expect((await res).status()).toBe(200);

        await expect(page).toHaveURL(/\/zh-tw\/namespaces$/);
        await expect(page.locator('.header')).toContainText('收藏管理');
        const cookies = await page.context().cookies();
        expect(cookies.some((c) => c.name === 'taicol_tk')).toBe(true);
    });

    test('Enter 鍵送出登入', async ({ page }) => {
        await page.goto('/zh-tw/login');
        await field(page, '電子信箱').fill(env.user.email);
        await field(page, '密碼').fill(env.user.password);
        const res = waitForApi(page, '/login', 'POST');
        await field(page, '密碼').press('Enter');
        expect((await res).status()).toBe(200);
        await expect(page).toHaveURL(/\/zh-tw\/?$/);
    });
});

test.describe('一般帳號', () => {
    test.use({ storageState: userState });

    test('頁首沒有管理員功能', async ({ page }) => {
        await page.goto('/zh-tw/taxon-names');
        await expect(page.locator('.header')).toContainText('收藏管理');
        await expect(page.locator('.header')).not.toContainText('管理員功能');
    });

    test('進 admin 頁面被 API 擋下（403）', async ({ page }) => {
        const res = waitForApi(page, '/users');
        await page.goto('/zh-tw/admin/users');
        expect((await res).status()).toBe(403);
        await expect(page.getByText('沒有權限').first()).toBeVisible();
    });

    test('名錄更新頁 API 被擋下（403）', async ({ page }) => {
        const res = waitForApi(page, /^\/update-service\//);
        await page.goto('/zh-tw/admin/update-service');
        expect((await res).status()).toBe(403);
    });
});

test.describe('管理員帳號', () => {
    test.use({ storageState: adminState });

    test('頁首有管理員功能，可進使用者管理', async ({ page }) => {
        await page.goto('/zh-tw/taxon-names');
        await expect(page.locator('.header')).toContainText('管理員功能');

        await openHeaderMenu(page, '管理員功能');
        const res = waitForApi(page, '/users');
        await page.locator('.header .submenu .item', { hasText: '使用者管理' }).click();
        expect((await res).status()).toBe(200);
        await expect(page).toHaveURL(/\/zh-tw\/admin\/users$/);
        await expect(page.getByText('沒有權限')).toHaveCount(0);
    });
});
