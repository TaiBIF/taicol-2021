import { test, expect, waitForApi, search, openHeaderMenu } from '../helpers/fixtures';
import { env } from '../helpers/env';
import type { Page } from '@playwright/test';

const kw = env.keyword;
const kwQuery = encodeURIComponent(`text: ${kw}`);

/** 結果列表：等搜尋 API 200，回傳表格內第一個詳細頁連結 */
async function firstRowLink(page: Page, detailPath: string) {
    await expect(page.locator('.help.text-center')).toContainText(/共計 \d+ 筆資料/);
    const link = page.locator(`table tbody a.my-link[href*="/${detailPath}/"]`).first();
    await expect(link).toBeVisible();
    return link;
}

/** 詳細頁：API 200、沒有錯誤頁；重新整理（深層網址）後仍正常 */
async function expectDetailWorks(page: Page, apiPattern: RegExp, ready: () => Promise<void>) {
    const assertPage = async () => {
        await ready();
        await expect(page.getByText('發生錯誤')).toHaveCount(0);
        await expect(page.getByText('找不到頁面')).toHaveCount(0);
    };
    await assertPage();

    const res = waitForApi(page, apiPattern);
    await page.reload();
    expect((await res).status()).toBe(200);
    await assertPage();
}

test.describe('公開頁面', () => {
    test('根目錄導向 /zh-tw 首頁', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveURL(/\/zh-tw\/?$/);
        await expect(page.locator('.middle-center h1')).toHaveText('物種學名管理工具');
        await expect(page.getByRole('link', { name: '登入' })).toBeVisible();
    });

    test('不存在的路徑導回首頁', async ({ page }) => {
        await page.goto('/zh-tw/no-such-page');
        await expect(page).toHaveURL(/\/zh-tw\/?$/);
    });

    test('首頁綜合搜尋 → 學名列表 → 學名頁', async ({ page }) => {
        await page.goto('/zh-tw');
        const res = waitForApi(page, '/search-taxon-names');
        await search(page, kw);
        expect((await res).status()).toBe(200);
        await expect(page).toHaveURL(/\/zh-tw\/taxon-names\?keywords=/);

        const link = await firstRowLink(page, 'taxon-names');
        const detail = waitForApi(page, /^\/taxon-names\/\d+$/);
        await link.click();
        expect((await detail).status()).toBe(200);
        await expect(page).toHaveURL(/\/zh-tw\/taxon-names\/\d+$/);

        await expectDetailWorks(page, /^\/taxon-names\/\d+$/, async () => {
            await expect(page.locator('.tabs li.is-active')).toBeVisible();
        });
    });

    test('首頁切換搜尋類型為文獻', async ({ page }) => {
        await page.goto('/zh-tw');
        await page.getByLabel('文獻', { exact: true }).check();
        const res = waitForApi(page, '/search-references');
        await search(page, kw);
        expect((await res).status()).toBe(200);
        await expect(page).toHaveURL(/\/zh-tw\/references\?keywords=/);
    });

    test('文獻列表 → 文獻頁 → 異名表', async ({ page }) => {
        const res = waitForApi(page, '/search-references');
        await page.goto('/zh-tw/references');
        expect((await res).status()).toBe(200);

        const link = await firstRowLink(page, 'references');
        await link.click();
        await expect(page).toHaveURL(/\/zh-tw\/references\/\d+$/);

        await expectDetailWorks(page, /^\/references\/\d+$/, async () => {
            await expect(page.getByText('本文獻分類群及其異名表')).toBeVisible();
        });

        const usages = waitForApi(page, /^\/references\/\d+\/usages$/);
        await page.getByRole('link', { name: '檢視異名表' }).click();
        expect((await usages).status()).toBe(200);
        await expect(page).toHaveURL(/\/zh-tw\/references\/\d+\/usages$/);
        const reloaded = waitForApi(page, /^\/references\/\d+\/usages$/);
        await page.reload();
        expect((await reloaded).status()).toBe(200);
        await expect(page.getByText('發生錯誤')).toHaveCount(0);
    });

    test('人名列表 → 人名頁', async ({ page }) => {
        const res = waitForApi(page, '/search-persons');
        await page.goto('/zh-tw/persons');
        expect((await res).status()).toBe(200);

        const link = await firstRowLink(page, 'persons');
        await link.click();
        await expect(page).toHaveURL(/\/zh-tw\/persons\/\d+$/);

        await expectDetailWorks(page, /^\/persons\/\d+$/, async () => {
            await expect(page.locator('.tabs li.is-active')).toBeVisible();
        });
    });

    test('列表頁深層網址（含 query）重新整理', async ({ page }) => {
        await page.goto(`/zh-tw/taxon-names?keywords=${kwQuery}&page=1`);
        await expect(page.getByText(/共計 \d+ 筆資料 · 第 1 頁/)).toBeVisible();
        await page.reload();
        await expect(page.getByText(/共計 \d+ 筆資料 · 第 1 頁/)).toBeVisible();
        await expect(page.locator('.selected-tag-item', { hasText: kw })).toBeVisible();
    });

    test('中英切換', async ({ page }) => {
        await page.goto(`/zh-tw/taxon-names?keywords=${kwQuery}`);
        await expect(page.getByText(/共計 \d+ 筆資料/)).toBeVisible();

        await openHeaderMenu(page, '語言');
        await page.locator('.header .submenu .item', { hasText: 'English' }).click();
        await expect(page).toHaveURL(/\/en-us\/taxon-names/);
        await expect(page.getByText(/\d+ Results, Page 1/)).toBeVisible();
        await expect(page.locator('.header h1')).toHaveText(/^Scientific Names Management Tool/);

        await openHeaderMenu(page, 'Language');
        await page.locator('.header .submenu .item', { hasText: '繁體中文' }).click();
        await expect(page).toHaveURL(/\/zh-tw\/taxon-names/);
        await expect(page.getByText(/共計 \d+ 筆資料/)).toBeVisible();
    });

    test('首頁英文版', async ({ page }) => {
        await page.goto('/en-us');
        await expect(page.locator('.middle-center h1')).toHaveText('Scientific Names Management Tool');
        await expect(page.getByRole('link', { name: 'Login' })).toBeVisible();
    });
});
