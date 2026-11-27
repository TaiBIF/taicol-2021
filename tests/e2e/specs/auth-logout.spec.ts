import fs from 'node:fs';
import { test, expect, waitForApi } from '../helpers/fixtures';
import { newApi } from '../helpers/api';
import { userState } from '../helpers/env';

// 登出會刪除該帳號全部 token，所以這支在 logout project 最後跑
test.use({ storageState: userState });

test('登出後回首頁，token 失效', async ({ page }) => {
    const { cookies } = JSON.parse(fs.readFileSync(userState, 'utf8'));
    const token = decodeURIComponent(cookies.find((c: { name: string }) => c.name === 'taicol_tk').value);

    await page.goto('/zh-tw/taxon-names');
    const userMenu = page.locator('.header .navbar-item').filter({
        has: page.locator('.submenu .item', { hasText: '登出' }),
    });
    await userMenu.hover();

    const res = waitForApi(page, '/logout', 'POST');
    await userMenu.locator('.submenu .item', { hasText: '登出' }).click();
    expect((await res).status()).toBe(200);

    await expect(page).toHaveURL(/\/zh-tw\/?$/);
    await expect(page.getByRole('link', { name: '登入' })).toBeVisible();
    await expect.poll(async () => (await page.context().cookies()).some((c) => c.name === 'taicol_tk')).toBe(false);

    const api = await newApi(token);
    expect((await api.get('/api/user')).status()).toBe(401);
    await api.dispose();
});
