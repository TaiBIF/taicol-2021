import Vue from 'vue';
import axios from 'axios';
import Cookies from 'js-cookie';
import snakeCaseKeys from 'snakecase-keys/index';
import camelCaseKeys from 'camelcase-keys';
import store from '../store';

import { openNotify } from './../utils/index';

const vAxios = axios.create({
    baseURL: '/api',
    withCredentials: true,
});

console.info('Prepare default Axios');

vAxios.defaults.headers.common['Authorization'] = `Bearer ${Cookies.get('taicol_tk')}`;
vAxios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

vAxios.interceptors.request.use(function (request) {
    if (request.headers['Content-Type'] !== 'multipart/form-data') {
        request.data = snakeCaseKeys(request.data || {}, { exclude: ['zh-tw', 'en-us'] });
    }

    return request;
});

vAxios.interceptors.response.use(function (response) {
    return {
        ...response,
        data: camelCaseKeys(response.data, { deep: true, exclude: ['zh-tw', 'en-us'] }),
    }
}, function (error) {
    error.response.data = camelCaseKeys(error.response.data, { deep: true });
    const { data: { message, errors }, status, config } = error.response;
    if (status === 401) {
        store.dispatch('auth/clearAuth');
    } else if (status === 422) {
        openNotify(`欄位填寫不完整`, 'is-danger');
    } else if (status === 500) {
        openNotify(`發生錯誤`, 'is-danger');
    }

    // 處理表單更新(編輯、建立)卻沒有權限的情況
    if (status === 401 && (config.method === 'post' || config.method === 'put')) {
        openNotify(`請重新登入`, 'is-danger');
    }

    return Promise.reject({
        message,
        errors: status === 422 ? errors : {},
        status,
    });
});

export default vAxios;
