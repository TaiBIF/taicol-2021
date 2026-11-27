import { test, expect, waitForApi, vsPick, button } from '../helpers/fixtures';
import { PREFIX, createSpecies, uid, userApi } from '../helpers/data';
import { userState } from '../helpers/env';
import type { Page } from '@playwright/test';

test.use({ storageState: userState });
test.describe.configure({ mode: 'serial' });

const key = uid();
const title = `${PREFIX}ns ${key}`;
const folderTitle = `${PREFIX}nsfolder ${key}`;
let namespaceId = 0;
let taxa: { id: number; name: string }[] = [];
const epithet = (i: number) => taxa[i].name.split(' ')[1];
const rows = (page: Page) => page.locator('#usage-content-container .usage-row');
const listUrl = () => `/zh-tw/namespaces/${namespaceId}/usages`;
const usagesApi = () => new RegExp(`^/namespaces/${namespaceId}/usages$`);

async function openList(page: Page) {
    const load = waitForApi(page, usagesApi());
    await page.goto(listUrl());
    expect((await load).status()).toBe(200);
}

test.beforeAll(async () => {
    const api = await userApi();
    taxa = [
        await createSpecies(api, `na${key}`),
        await createSpecies(api, `nb${key}`),
    ];
    // 收藏夾放一個學名，給拖曳測試用
    const folder = await api.post('/api/favorite-folders', { data: { title: folderTitle } });
    expect(folder.ok()).toBe(true);
    const folderId = (await folder.json()).data.id;
    const item = await api.post(`/api/favorite-folders/${folderId}/items`, { data: { type: 2, id: taxa[1].id } });
    expect(item.ok()).toBe(true);
    await api.dispose();
});

test('建立名錄', async ({ page }) => {
    const list = waitForApi(page, '/namespaces');
    await page.goto('/zh-tw/namespaces');
    await list;

    await page.getByRole('button', { name: '建立名錄' }).click();
    const addRow = page.locator('table tbody tr').first();
    await addRow.getByPlaceholder('請輸入名錄名稱').fill(title);

    const res = waitForApi(page, '/namespaces', 'POST');
    await addRow.getByRole('button', { name: '儲存', exact: true }).click();
    const r = await res;
    expect(r.status()).toBe(200);
    namespaceId = (await r.json()).data.id;

    // 重新載入列表，用網址找新名錄的連結
    const reload = waitForApi(page, '/namespaces');
    await page.reload();
    await reload;
    // 子路由路徑為空，所以連結結尾會多一個 /
    const link = page.locator(`a[href$="/namespaces/${namespaceId}/usages/"]`);
    await expect(link).toBeVisible();
    await expect(link).toContainText(title);
    await link.click();
    await expect(page).toHaveURL(new RegExp(`${listUrl()}/?$`));
});

test('名錄內加入學名', async ({ page }) => {
    expect(namespaceId, '需先建立名錄').toBeGreaterThan(0);
    await openList(page);
    await expect(rows(page)).toHaveCount(0);

    await vsPick(page.locator('.taxon-name-column'), taxa[0].name, { text: taxa[0].name, api: '/taxon-names' });
    const saved = waitForApi(page, `/namespaces/${namespaceId}/usages`, 'POST');
    const reload = waitForApi(page, usagesApi());
    await button(page, '加入').click();
    expect((await saved).status()).toBe(200);
    await reload;
    await expect(rows(page)).toHaveCount(1);
    await expect(rows(page).nth(0)).toContainText(epithet(0));
});

test('從收藏面板拖曳學名進名錄', async ({ page }) => {
    await openList(page);
    await expect(rows(page)).toHaveCount(1);

    const panel = page.locator('.panel');
    await panel.getByText(folderTitle).click();
    const item = panel.locator('.draggable-container[data-type="taxon-name"] > div').filter({ hasText: epithet(1) });
    await expect(item).toBeVisible();

    const from = await item.boundingBox();
    const to = await rows(page).nth(0).boundingBox();
    expect(from && to).toBeTruthy();

    const saved = waitForApi(page, `/namespaces/${namespaceId}/usages`, 'POST');
    await page.mouse.move(from!.x + from!.width / 2, from!.y + from!.height / 2);
    await page.mouse.down();
    await page.mouse.move(to!.x + 20, to!.y + to!.height - 2, { steps: 20 });
    await page.mouse.up();
    expect((await saved).status()).toBe(200);

    await openList(page);
    await expect(rows(page)).toHaveCount(2);
    await expect(page.locator('#usage-content-container')).toContainText(epithet(1));
});

test('匯出分類群（xlsx）', async ({ page }) => {
    await openList(page);
    const res = waitForApi(page, `/export/namespaces/${namespaceId}/usages`);
    const download = page.waitForEvent('download');
    await page.getByRole('button', { name: '匯出分類群', exact: true }).click();

    const r = await res;
    expect(r.status()).toBe(200);
    expect(r.headers()['content-type']).toMatch(/spreadsheet|excel|octet-stream/);
    expect((await download).suggestedFilename()).toBe(`${title}.xlsx`);
});
