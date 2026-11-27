import fs from 'node:fs';
import { test as setup, expect } from '@playwright/test';
import { apiLogin, newApi } from '../helpers/api';
import { adminState, authDir, env, userState } from '../helpers/env';

const accounts = [
    { name: 'user', file: userState, cred: () => env.user, isAdmin: false },
    { name: 'admin', file: adminState, cred: () => env.admin, isAdmin: true },
];

for (const account of accounts) {
    setup(`登入 ${account.name}`, async () => {
        const { email, password } = account.cred();
        const token = await apiLogin(email, password);

        // 確認帳號角色符合預期（roleId 1 = 管理員）
        const api = await newApi(token);
        const me = await api.get('/api/user');
        expect(me.status()).toBe(200);
        const { role_id: roleId } = await me.json();
        expect(roleId === 1, `${email} 的 role_id 為 ${roleId}`).toBe(account.isAdmin);
        await api.dispose();

        const { hostname } = new URL(env.baseURL);
        fs.mkdirSync(authDir, { recursive: true });
        fs.writeFileSync(account.file, JSON.stringify({
            cookies: [{
                name: 'taicol_tk',
                value: encodeURIComponent(token),
                domain: hostname,
                path: '/',
                expires: -1,
                httpOnly: false,
                secure: env.baseURL.startsWith('https'),
                sameSite: 'Lax',
            }],
            origins: [],
        }, null, 2));
    });
}
