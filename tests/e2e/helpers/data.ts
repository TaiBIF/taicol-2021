import fs from 'node:fs';
import type { APIRequestContext } from '@playwright/test';
import { newApi } from './api';
import { userState } from './env';

/**
 * 測試資料命名規則（清除腳本 tests/e2e/cleanup.php 依此找資料）：
 * - 人名：last_name 以 E2E- 開頭
 * - 文獻：作者為 E2E- 人名；書名以 E2E- 開頭
 * - 學名：屬名固定 E2etest（學名不能有連字號）
 * - 收藏夾：標題以 E2E- 開頭（名錄之後加入）
 */
export const PREFIX = 'E2E-';
export const GENUS = 'E2etest';

/** 每次執行唯一的小寫英數字串（也用作種小名，需 ≥ 3 字才查得到全文索引） */
export const uid = () => `${Date.now().toString(36)}${Math.random().toString(36).slice(2, 5)}`;

export function tokenOf(stateFile: string): string {
    const { cookies } = JSON.parse(fs.readFileSync(stateFile, 'utf8'));
    return decodeURIComponent(cookies.find((c: { name: string }) => c.name === 'taicol_tk').value);
}

export const userApi = () => newApi(tokenOf(userState));

async function json(res: Awaited<ReturnType<APIRequestContext['post']>>, what: string): Promise<any> {
    if (!res.ok()) {
        throw new Error(`${what} 失敗：${res.status()} ${await res.text()}`);
    }
    return res.json();
}

export async function createPerson(api: APIRequestContext, key: string) {
    const lastName = `${PREFIX}${key}`;
    const data = await json(await api.post('/api/persons', {
        data: {
            last_name: lastName,
            first_name: 'Tester',
            original_full_name: `${lastName} Tester`,
            biology_departments: ['animalia'],
            has_checked_duplicates: true,
        },
    }), '建立人名');
    return { id: data.id as number, lastName };
}

export async function createReference(api: APIRequestContext, authorId: number, key: string) {
    const bookTitle = `${PREFIX}Book ${key}`;
    const data = await json(await api.post('/api/references', {
        data: {
            type: 3,
            title: bookTitle,
            subtitle: '',
            authors: [authorId],
            publish_year: '2026',
            properties: { book_title: bookTitle, book_title_abbreviation: '' },
            is_publish: true,
            has_checked_duplicates: true,
        },
    }), '建立文獻');
    return { id: data.id as number, bookTitle };
}

let speciesIds: { nomenclatureId: number; rankId: number } | null = null;

/** ICZN 的「種」：rank id 從 /api/nomenclatures 的 ranks 取得（/api/ranks 不含 id） */
async function iczSpecies(api: APIRequestContext) {
    if (!speciesIds) {
        const list = await json(await api.get('/api/nomenclatures'), '讀取命名規約');
        const iczn = list.find((n: { name: string }) => n.name === 'ICZN');
        const species = iczn.ranks.find((r: { key: string }) => r.key === 'species');
        speciesIds = { nomenclatureId: iczn.id, rankId: species.id };
    }
    return speciesIds;
}

export async function createSpecies(api: APIRequestContext, s1: string, isPublish = true) {
    const { nomenclatureId, rankId } = await iczSpecies(api);
    const name = `${GENUS} ${s1}`;
    const data = await json(await api.post('/api/taxon-names', {
        data: {
            name,
            nomenclature_id: nomenclatureId,
            rank_id: rankId,
            latin_genus: GENUS,
            latin_s1: s1,
            authors: [],
            ex_authors: [],
            species_layers: [],
            type_specimens: [],
            hybrid_parents_id: [null, null],
            usage: {},
            is_publish: isPublish,
            has_checked_duplicates: true,
        },
    }), '建立學名');
    return { id: data.id as number, name };
}
