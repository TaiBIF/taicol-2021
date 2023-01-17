import { createToastInterface } from 'vue-toastification';
import InfoToast from '../components/toasts/info.vue';
import toastOptions from '../toastOptions';

export const renderMarkdown = (string) => string.replace(/_([a-zA-Z]*)_/g, '<i>$1</i>');
export const getBase64 = (file) => new Promise((resolve, reject) => {
    if (!file) {
        resolve(null);
    }

    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = (error) => reject(error);
});

export const openNotify = (message, type = 'is-success') => {
    const toast = createToastInterface(toastOptions);
    toast({ component: InfoToast, props: { message, type } });
};
