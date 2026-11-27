import { test, expect, waitForApi, field, vsPick, button } from '../helpers/fixtures';
import { createPerson, createReference, createSpecies, uid, userApi } from '../helpers/data';
import { userState } from '../helpers/env';
import type { Page } from '@playwright/test';

test.use({ storageState: userState });
test.describe.configure({ mode: 'serial' });

const key = uid();
let referenceId = 0;
let taxa: { id: number; name: string }[] = [];

const listUrl = () => `/zh-tw/references/${referenceId}/usages-edit`;
const usagesApi = () => new RegExp(`^/references/${referenceId}/usages-edit$`);
const saveApi = () => `/reference/${referenceId}/usages-edit`;
const rows = (page: Page) => page.locator('#usage-content-container .usage-row');
const epithet = (i: number) => taxa[i].name.split(' ')[1];

async function openList(page: Page) {
    const load = waitForApi(page, usagesApi());
    await page.goto(listUrl());
    expect((await load).status()).toBe(200);
}

test.beforeAll(async () => {
    const api = await userApi();
    const author = await createPerson(api, `${key}u`);
    referenceId = (await createReference(api, author.id, `${key}u`)).id;
    taxa = [
        await createSpecies(api, `a${key}`),
        await createSpecies(api, `b${key}`),
    ];
    await api.dispose();
});

test('新增學名卡片：加入兩個學名', async ({ page }) => {
    await openList(page);
    await expect(rows(page)).toHaveCount(0);

    // 「新增學名卡片」欄位預設就是展開的，按鈕是切換開關，不要按
    await expect(page.locator('.taxon-name-column')).toBeVisible();
    for (const [i, taxon] of taxa.entries()) {
        await vsPick(page.locator('.taxon-name-column'), taxon.name, { text: taxon.name, api: '/taxon-names' });
        const saved = waitForApi(page, saveApi(), 'POST');
        const reload = waitForApi(page, usagesApi());
        await button(page, '加入').click();
        expect((await saved).status()).toBe(200);
        await reload;
        await expect(rows(page)).toHaveCount(i + 1);
    }
    await expect(rows(page).nth(0)).toContainText(epithet(0));
    await expect(rows(page).nth(1)).toContainText(epithet(1));
});

test('拖曳排序後自動存檔，重新整理順序不變', async ({ page }) => {
    await openList(page);
    await expect(rows(page)).toHaveCount(2);

    const from = await rows(page).nth(1).locator('.handle').boundingBox();
    const to = await rows(page).nth(0).boundingBox();
    expect(from, '找不到拖曳把手').toBeTruthy();
    expect(to).toBeTruthy();

    const saved = waitForApi(page, saveApi(), 'POST');
    await page.mouse.move(from!.x + from!.width / 2, from!.y + from!.height / 2);
    await page.mouse.down();
    await page.mouse.move(to!.x + 20, to!.y + 2, { steps: 15 });
    await page.mouse.up();
    expect((await saved).status()).toBe(200);

    await openList(page);
    await expect(rows(page).nth(0)).toContainText(epithet(1));
    await expect(rows(page).nth(1)).toContainText(epithet(0));
});

test('Tab 縮排成異名並存檔', async ({ page }) => {
    await openList(page);
    const second = rows(page).nth(1);
    await expect(second).not.toHaveClass(/is-indent/);

    const saved = waitForApi(page, saveApi(), 'POST');
    await second.focus();
    await page.keyboard.press('Tab');
    expect((await saved).status()).toBe(200);

    await openList(page);
    await expect(rows(page).nth(1)).toHaveClass(/is-indent/);
});

test('編輯 usage 屬性（存在於臺灣、臺灣特有）', async ({ page }) => {
    await openList(page);

    const detail = waitForApi(page, new RegExp(`^/references/${referenceId}/usages-edit/\\d+$`));
    await rows(page).nth(0).locator('.usage-content').dblclick();
    const usageId = Number(new URL((await detail).url()).pathname.split('/').pop());
    await expect(page).toHaveURL(new RegExp(`${listUrl()}/${usageId}$`));

    // 按鈕可取消選取（再按一次會清掉），所以已選取就不要再按
    const choose = async (label: string) => {
        const yes = field(page, label).getByRole('button', { name: '是', exact: true });
        if (!(await yes.getAttribute('class'))?.includes('is-dark')) {
            await yes.click();
        }
        await expect(yes).toHaveClass(/is-dark/);
    };
    // 從列表「加入」的 usage 預設已是「存在於臺灣：是」
    await choose('存在於臺灣？');
    await choose('臺灣特有');

    const saved = waitForApi(page, `/references/${referenceId}/usages-edit/${usageId}`, 'PUT');
    await button(page, '儲存').click();
    expect((await saved).status()).toBe(200);
    await expect(page).toHaveURL(new RegExp(`${listUrl()}/?$`));

    const api = await userApi();
    const res = await api.get(`/api/references/${referenceId}/usages-edit/${usageId}`);
    expect(res.status()).toBe(200);
    const { properties } = await res.json();
    expect(Number(properties.is_in_taiwan)).toBe(1);
    expect(Number(properties.is_endemic)).toBe(1);
    await api.dispose();
});
