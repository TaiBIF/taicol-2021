import Vue from 'vue';

/**
 * 後端訊息依語言顯示：有 messageKey 且語言檔有對應 key 時顯示翻譯，否則顯示後端原始訊息
 * @param {{ message?: string, messageKey?: string, messageParams?: object }} res axios 回應的 data 或 maxios reject 的錯誤物件
 * @param {string} fallback 都沒有時顯示的文字
 */
export const serverMessage = (res, fallback = '') => {
    const key = res?.messageKey;
    if (key && Vue.i18n.keyExists(key)) {
        return Vue.i18n.translate(key, res.messageParams || {});
    }
    return res?.message || fallback;
};

export default serverMessage;
