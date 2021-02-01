import { ToastProgrammatic as Toast } from 'buefy';

export const renderMarkdown = (string) => {
    return string.replace(/_([a-zA-Z]*)_/g, '<i>$1</i>');
}
export const getBase64 = (file) => new Promise(function (resolve, reject) {
    if (!file) {
        resolve();
    }

    let reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result)
    reader.onerror = (error) => reject('Error: ', error);
})

export const openNotify = (message, type = 'is-success') => {
    Toast.open({
        duration: 3000,
        message,
        position: 'is-bottom',
        type,
        queue: false,
    });
}

