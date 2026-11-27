import { test, expect, waitForApi, field } from '../helpers/fixtures';
import { adminState, env } from '../helpers/env';
import { userApi } from '../helpers/data';

// 只讀取，不更動使用者、不觸發名錄更新工作
test.use({ storageState: adminState });

test.describe('使用者管理', () => {
    test('列表載入，可依姓名排序', async ({ page }) => {
        const res = waitForApi(page, '/users');
        await page.goto('/zh-tw/admin/users');
        expect((await res).status()).toBe(200);
        await expect(page.locator('table tbody tr').first()).toBeVisible();

        const sorted = waitForApi(page, '/users');
        await page.locator('table thead th').nth(1).locator('.sort-button').click();
        const r = await sorted;
        expect(r.status()).toBe(200);
        expect(r.url()).toContain('sortby=last_name');
        await expect(page.locator('table tbody tr').first()).toBeVisible();
    });

    test('使用者編輯頁載入（測試帳號）', async ({ page }) => {
        // 列表每頁 20 筆、沒有搜尋，所以直接用 id 開
        const api = await userApi();
        const { id } = await (await api.get('/api/user')).json();
        await api.dispose();

        const detail = waitForApi(page, `/users/${id}`);
        await page.goto(`/zh-tw/admin/users/${id}`);
        expect((await detail).status()).toBe(200);
        await expect(field(page, 'Email').locator('input')).toHaveValue(env.user.email);
    });
});

test.describe('名錄更新服務頁', () => {
    test('三個分頁都能載入', async ({ page }) => {
        const status = waitForApi(page, '/update-service/status');
        const jobs = waitForApi(page, '/update-service/jobs');
        await page.goto('/zh-tw/admin/update-service');
        expect((await status).status()).toBe(200);
        expect((await jobs).status()).toBe(200);

        const issues = waitForApi(page, '/taxon-issues');
        await page.getByRole('button', { name: '版本名錄', exact: true }).click();
        expect((await issues).status()).toBe(200);
        await expect(page).toHaveURL(/[?&]tab=release/);

        const unchecked = waitForApi(page, '/get-unchecked-usage');
        await page.getByRole('button', { name: /學名使用檢查/ }).click();
        expect((await unchecked).status()).toBe(200);
        await expect(page).toHaveURL(/[?&]tab=usage-check/);

        await page.getByRole('button', { name: '名錄更新', exact: true }).click();
        await expect(page).not.toHaveURL(/tab=/);
    });

    test('舊網址 usage-check 轉到學名使用檢查分頁', async ({ page }) => {
        const unchecked = waitForApi(page, '/get-unchecked-usage');
        await page.goto('/zh-tw/admin/usage-check');
        expect((await unchecked).status()).toBe(200);
        await expect(page).toHaveURL(/\/zh-tw\/admin\/update-service\?tab=usage-check/);
    });

    test('分頁網址重新整理後維持在同一分頁', async ({ page }) => {
        await page.goto('/zh-tw/admin/update-service?tab=release');
        const issues = waitForApi(page, '/taxon-issues');
        await page.reload();
        expect((await issues).status()).toBe(200);
    });
});
