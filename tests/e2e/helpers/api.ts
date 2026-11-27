import { request, type APIRequestContext } from '@playwright/test';
import { env } from './env';

export const DEVICE = 'playwright-e2e';

/** 與前端相同的 headers：Bearer token + XHR，讓 Laravel 回 JSON 而不是 redirect */
export const apiHeaders = (token?: string) => ({
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
});

export async function newApi(token?: string): Promise<APIRequestContext> {
    return request.newContext({
        baseURL: env.baseURL,
        ignoreHTTPSErrors: true,
        extraHTTPHeaders: apiHeaders(token),
    });
}

/** POST /api/login，回傳 Sanctum token（前端存在 cookie taicol_tk） */
export async function apiLogin(email: string, password: string): Promise<string> {
    const api = await newApi();
    const res = await api.post('/api/login', { data: { email, password, device: DEVICE } });
    if (!res.ok()) {
        throw new Error(`登入失敗 ${email}：${res.status()} ${await res.text()}`);
    }
    const { tk } = await res.json();
    await api.dispose();
    return tk;
}

/** POST /api/logout（後端會刪掉該帳號全部 token） */
export async function apiLogout(token: string): Promise<void> {
    const api = await newApi(token);
    await api.post('/api/logout');
    await api.dispose();
}
