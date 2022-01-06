import axios from './utils/maxios';
import store from './store';
import Cookies from 'js-cookie';

export default async () => {
    console.info('Prepare initial data.');
    return new Promise((resolve, reject) => {

        const authCheckPromise = axios
            .get('/user')
            .then((response) => {
                store.commit('auth/SET_AUTHENTICATED', true);
                store.commit('auth/SET_USER', response.data);
                console.info('- auth check.');
            })
            .catch(() => {
                store.commit('auth/SET_AUTHENTICATED', false);
                store.commit('auth/SET_USER', null);
                Cookies.remove('taicol_tk');
                console.info('- auth fail.');
            })

        const nomenclatureDataPromise = axios
            .get('/nomenclatures')
            .then(({ data }) => {
                store.commit('nomenclature/SET_ITEMS', data);
                console.info('- nomenclatures fetch success.');
            }).catch(e => e);

        const rankDataPromise = axios
            .get('/ranks')
            .then(({ data }) => {
                store.commit('rank/SET_ITEMS', data);
                console.info('- ranks fetch success.');
            }).catch(e => e);

        Promise.all([
            authCheckPromise,
            rankDataPromise,
            nomenclatureDataPromise
        ]).then(() => {
            resolve();
        })
    })
};

