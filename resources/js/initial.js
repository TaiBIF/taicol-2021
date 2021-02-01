import axios from './utils/maxios';
import store from './store';
import Cookies from 'js-cookie';

export default async () => {
    console.info('Prepare initial data.');
    return new Promise((resolve, reject) => {
        axios
            .get('/user')
            .then((response) => {
                store.commit('auth/SET_AUTHENTICATED', true);
                store.commit('auth/SET_USER', response.data);
            })
            .catch(() => {
                store.commit('auth/SET_AUTHENTICATED', false);
                store.commit('auth/SET_USER', null);
                Cookies.remove('taicol_tk');
            })
            .finally(() => {
                resolve();
            });
    })
};

