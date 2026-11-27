import { test, expect, waitForApi, field, vsPick, button } from '../helpers/fixtures';
import { GENUS, PREFIX, createPerson, createSpecies, uid, userApi } from '../helpers/data';
import { userState } from '../helpers/env';
import type { Page } from '@playwright/test';

test.use({ storageState: userState });
// 同一支 spec 內的測試共用前面建立的資料，依序執行
test.describe.configure({ mode: 'serial' });

const key = uid();

test.describe('人名', () => {
    const lastName = `${PREFIX}${key}`;
    let personId = 0;

    test('必填未填：422 並顯示錯誤', async ({ page }) => {
        await page.goto('/zh-tw/person');
        const res = waitForApi(page, '/persons', 'POST');
        await button(page, '儲存').click();
        expect((await res).status()).toBe(422);
        await expect(page.locator('.form .is-danger').first()).toBeVisible();
        await expect(page).toHaveURL(/\/zh-tw\/person$/);
    });

    test('新增 → 人名頁', async ({ page }) => {
        await page.goto('/zh-tw/person');
        await field(page, '姓').locator('input').fill(lastName);
        await field(page, '名').locator('input').fill('Tester');
        await field(page, '原母語完整名').locator('input').fill(`${lastName} Tester`);
        await page.getByLabel('動物', { exact: true }).check();

        const res = waitForApi(page, '/persons', 'POST');
        await button(page, '儲存').click();
        const r = await res;
        expect(r.status()).toBe(200);
        personId = (await r.json()).id;

        await expect(page).toHaveURL(new RegExp(`/zh-tw/persons/${personId}$`));
        await expect(page.locator('.tabs li.is-active')).toBeVisible();
    });

    test('重複偵測：同名再新增出現提示，返回不建立', async ({ page }) => {
        await page.goto('/zh-tw/person');
        await field(page, '姓').locator('input').fill(lastName);
        await field(page, '名').locator('input').fill('Tester');
        await field(page, '原母語完整名').locator('input').fill(`${lastName} Tester`);
        await page.getByLabel('動物', { exact: true }).check();

        const res = waitForApi(page, '/persons', 'POST');
        await button(page, '儲存').click();
        expect((await res).status()).toBe(409);

        const modal = page.getByText('您輸入的內容於資料庫中已有以下類似的資料');
        await expect(modal).toBeVisible();
        await page.locator('.modal.is-active').getByRole('button', { name: '返回', exact: true }).click();
        await expect(modal).toBeHidden();
        await expect(page).toHaveURL(/\/zh-tw\/person$/);
    });

    test('編輯：修改其他名後儲存', async ({ page }) => {
        expect(personId, '需先跑「新增」').toBeGreaterThan(0);
        const load = waitForApi(page, `/persons/${personId}`);
        await page.goto(`/zh-tw/persons/${personId}/edit`);
        await load;
        await expect(field(page, '姓').locator('input')).toHaveValue(lastName);

        await field(page, '其他名').locator('input').fill(`${PREFIX}alias`);
        const res = waitForApi(page, `/persons/${personId}`, 'PUT');
        await button(page, '儲存').click();
        expect((await res).status()).toBe(200);
        await expect(page).toHaveURL(new RegExp(`/zh-tw/persons/${personId}$`));
    });
});

test.describe('文獻', () => {
    let author = { id: 0, lastName: '' };
    let referenceId = 0;
    const bookTitle = `${PREFIX}Book ${key}`;

    test.beforeAll(async () => {
        const api = await userApi();
        author = await createPerson(api, `${key}r`);
        await api.dispose();
    });

    test('新增書籍 → 文獻頁', async ({ page }) => {
        await page.goto('/zh-tw/reference');
        await vsPick(field(page, '文獻類型'), /^\s*書籍\s*$/);
        await vsPick(field(page, '作者'), author.lastName, { text: author.lastName, api: '/persons' });
        await field(page, '發表年份').locator('input').fill('2026');

        // 「書名縮寫」也以「書名」開頭，所以用完全比對
        const book = field(page, /^\s*書名\s*$/);
        await book.locator('.vs__search').fill(bookTitle);
        await book.locator('.vs__search').press('Enter');
        await expect(book.locator('.vs__selected', { hasText: bookTitle })).toBeVisible();

        const res = waitForApi(page, '/references', 'POST');
        await button(page, '確定發布').click();
        const r = await res;
        expect(r.status()).toBe(200);
        referenceId = (await r.json()).id;

        await expect(page).toHaveURL(new RegExp(`/zh-tw/references/${referenceId}$`));
        await expect(page.getByText('本文獻分類群及其異名表')).toBeVisible();
    });

    test('編輯：修改備註後儲存', async ({ page }) => {
        expect(referenceId, '需先跑「新增」').toBeGreaterThan(0);
        await page.goto(`/zh-tw/references/${referenceId}/edit`);
        const note = field(page, '備註').locator('textarea');
        await expect(note).toBeVisible();
        await note.fill(`${PREFIX}note ${key}`);

        const res = waitForApi(page, `/references/${referenceId}`, 'PUT');
        await button(page, '儲存').click();
        expect((await res).status()).toBe(200);
        await expect(page).toHaveURL(new RegExp(`/zh-tw/references/${referenceId}$`));
    });
});

test.describe('學名', () => {
    const s1 = `t${key}`;
    let taxonNameId = 0;

    async function fillSpecies(page: Page) {
        await page.goto('/zh-tw/taxon-name');
        await vsPick(field(page, '適用命名規約'), '國際動物命名法規');
        await vsPick(field(page, '階層'), /^\s*種\s*$/);
        await field(page, '屬名').locator('input').fill(GENUS);
        await field(page, '種小名').locator('input').fill(s1);
    }

    test('新增種（存為草稿）→ 留在編輯頁', async ({ page }) => {
        await fillSpecies(page);
        const res = waitForApi(page, '/taxon-names', 'POST');
        await button(page, '存為草稿').click();
        const r = await res;
        expect(r.status()).toBe(200);
        taxonNameId = (await r.json()).id;
        await expect(page).toHaveURL(new RegExp(`/zh-tw/taxon-names/${taxonNameId}/edit$`));
    });

    test('重複偵測：同名草稿已存在', async ({ page, guard }) => {
        // 舊版 TaxonNameForm.submit 在 409 後會 throw，頁面沒有接住
        guard.allowPageError(/^Object$/);
        await fillSpecies(page);
        const res = waitForApi(page, '/taxon-names', 'POST');
        await button(page, '存為草稿').click();
        expect((await res).status()).toBe(409);
        await expect(page.getByText('該筆資料已被建立為草稿')).toBeVisible();
        await button(page, '繼續編輯').click();
    });

    test('草稿發布 → 學名頁', async ({ page }) => {
        expect(taxonNameId, '需先跑「新增」').toBeGreaterThan(0);
        const load = waitForApi(page, `/taxon-names/${taxonNameId}/info`);
        await page.goto(`/zh-tw/taxon-names/${taxonNameId}/edit`);
        await load;
        await expect(field(page, '種小名').locator('input')).toHaveValue(s1);

        const res = waitForApi(page, `/taxon-names/${taxonNameId}`, 'PUT');
        await button(page, '確定發布').click();
        expect((await res).status()).toBe(200);
        await expect(page).toHaveURL(new RegExp(`/zh-tw/taxon-names/${taxonNameId}$`));
        await expect(page.locator('.tabs li.is-active')).toBeVisible();
    });
});

test.describe('俗名', () => {
    let taxon = { id: 0, name: '' };

    test.beforeAll(async () => {
        const api = await userApi();
        taxon = await createSpecies(api, `c${key}`);
        await api.dispose();
    });

    test('新增俗名', async ({ page }) => {
        await page.goto('/zh-tw/common-name');
        // 表單第一個 .field 是學名選擇
        await vsPick(page.locator('.form .field').first(), taxon.name, { text: taxon.name, api: '/taxon-names' });

        const box = page.locator('.form .box').first();
        await field(page, '俗名', '.column').locator('input').fill(`${PREFIX}俗名${key}`);
        await vsPick(box.locator('.column').nth(1), /./);

        const res = waitForApi(page, '/common-name', 'POST');
        await button(page, '儲存').click();
        expect((await res).status()).toBe(200);
    });
});
