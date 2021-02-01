import axios from '../utils/maxios';
import Cookies from 'js-cookie';

export default {
    namespaced: true,

    state: {
        authenticated: !!Cookies.get('taicol_tk'),
        user: null,
    },

    getters: {
        authenticated(state) {
            return state.authenticated
        },

        user(state) {
            return state.user
        },
    },

    mutations: {
        SET_AUTHENTICATED(state, value) {
            state.authenticated = value
        },

        SET_USER(state, value) {
            state.user = value
        },
    },

    actions: {
        async login({ dispatch, commit }, credentials) {
            const { data: { tk: token } } = await axios.post('/login', credentials);
            Cookies.set('taicol_tk', token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            return dispatch('fetchMe');
        },

        async logout({ dispatch }) {
            await axios.post('/logout')

            return dispatch('fetchMe');
        },

        clearAuth({ commit }) {
            commit('SET_AUTHENTICATED', false);
            commit('SET_USER', null);
        },

        async fetchMe({ commit }) {
            return axios.get('/user').then((response) => {
                commit('SET_AUTHENTICATED', true);
                commit('SET_USER', response.data);
            }).catch(() => {
                commit('SET_AUTHENTICATED', false);
                commit('SET_USER', null);
                Cookies.remove('taicol_tk');
            });
        },
    },
}
