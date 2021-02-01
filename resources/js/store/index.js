import Vue from 'vue';
import Vuex from 'vuex';
import auth from './auth';
import breadcrumb from './breadcrumb';

Vue.use(Vuex);

const store = new Vuex.Store({
    modules: {
        auth,
        breadcrumb,
    },
    state: {
        countries: [],
        layers: [],
        modal: {
            component: null,
            isActive: false,
            title: '',
            props: {},
        },
    },
    actions: {
    },
    mutations: {
        openModal(state, { component, props }) {
            state.modal.isActive = true;
            state.modal.component = component;
            state.modal.props = props;
        },
        closeModal(state) {
            state.modal.isActive = false;
        },

    },
});

export default store;
