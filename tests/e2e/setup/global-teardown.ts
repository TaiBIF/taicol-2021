import fs from 'node:fs';
import { apiLogout } from '../helpers/api';
import { adminState, userState } from '../helpers/env';

/** 測試結束：撤銷 setup 建立的 token（E2E- 資料清除在 edit / namespace spec 加入後補上） */
export default async function globalTeardown() {
    for (const file of [userState, adminState]) {
        if (!fs.existsSync(file)) continue;
        const { cookies } = JSON.parse(fs.readFileSync(file, 'utf8'));
        const tk = cookies.find((c: { name: string }) => c.name === 'taicol_tk');
        if (tk) {
            await apiLogout(decodeURIComponent(tk.value)).catch(() => undefined);
        }
        fs.rmSync(file);
    }
}
