import { test, expect, waitForApi, vsPick, button } from '../helpers/fixtures';
import { PREFIX, createPerson, createReference, createSpecies, uid, userApi } from '../helpers/data';
import { userState } from '../helpers/env';
import type { Page } from '@playwright/test';

test.use({ storageState: userState });
test.describe.configure({ mode: 'serial' });

const key = uid();
const folderTitle = `${PREFIX}folder ${key}`;
let folderId = 0;
let refA = 0;
let taxa: { id: number; name: string }[] = [];
const epithet = (i: number) => taxa[i].name.split(' ')[1];

/** 在文獻的 usage 編輯頁用「新增學名卡片」加入一個學名 */
async function addUsage(page: Page, referenceId: number, taxon: { name: string }) {
    const load = waitForApi(page, new RegExp(`^/references/${referenceId}/usages-edit$`));
    await page.goto(`/zh-tw/references/${referenceId}/usages-edit`);
    await load;
    await vsPick(page.locator('.taxon-name-column'), taxon.name, { text: taxon.name, api: '/taxon-names' });
    const saved = waitForApi(page, `/reference/${referenceId}/usages-edit`, 'POST');
    await button(page, '加入').click();
    expect((await saved).status()).toBe(200);
}

test.beforeAll(async () => {
    const api = await userApi();
    const author = await createPerson(api, `${key}f`);
    refA = (await createReference(api, author.id, `${key}fa`)).id;
    taxa = [await createSpecies(api, `fa${key}`)];
    await api.dispose();
});

test('建立收藏夾', async ({ page }) => {
    const list = waitForApi(page, '/favorite-folders');
    await page.goto('/zh-tw/favorite-folders');
    await list;

    await page.getByRole('button', { name: '新增收藏夾', exact: true }).click();
    await page.locator('.sticky input').fill(folderTitle);
    const res = waitForApi(page, '/favorite-folders', 'POST');
    await page.getByRole('button', { name: '送出', exact: true }).click();
    const r = await res;
    expect(r.status()).toBe(200);
    folderId = (await r.json()).data.id;
    await expect(page.getByText(folderTitle)).toBeVisible();
});

test('從文獻異名表加入收藏、移除、再加入', async ({ page }) => {
    expect(folderId, '需先建立收藏夾').toBeGreaterThan(0);
    await addUsage(page, refA, taxa[0]);

    const load = waitForApi(page, new RegExp(`^/references/${refA}/usages$`));
    await page.goto(`/zh-tw/references/${refA}/usages`);
    await load;

    const openModal = async () => {
        const status = waitForApi(page, '/favorite-folders-status');
        await page.locator('a.g-button', { has: page.locator('.fa-plus') }).first().click();
        await status;
    };
    const folderRow = () => page.locator('.modal.is-active li', { hasText: folderTitle });

    await openModal();
    let res = waitForApi(page, `/favorite-folders/${folderId}/items`, 'POST');
    await folderRow().click();
    expect((await res).status()).toBe(200);
    await expect(folderRow().locator('input[type="checkbox"]')).toBeChecked();

    res = waitForApi(page, new RegExp(`^/favorite-folders/${folderId}/items/\\d+$`), 'DELETE');
    await folderRow().click();
    expect((await res).status()).toBe(200);
    await expect(folderRow().locator('input[type="checkbox"]')).not.toBeChecked();

    res = waitForApi(page, `/favorite-folders/${folderId}/items`, 'POST');
    await folderRow().click();
    expect((await res).status()).toBe(200);
    await page.locator('.modal.is-active').getByRole('button', { name: '關閉', exact: true }).click();
});

test('收藏夾內容', async ({ page }) => {
    const load = waitForApi(page, `/favorite-folders/${folderId}`);
    await page.goto(`/zh-tw/favorite-folders/${folderId}`);
    expect((await load).status()).toBe(200);
    await expect(page.getByText(folderTitle)).toBeVisible();
    await expect(page.locator('.form')).toContainText(epithet(0));
});
