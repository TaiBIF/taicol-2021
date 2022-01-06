import Vue from 'vue';
import Vuex from 'vuex';
import auth from './auth';
import breadcrumb from './breadcrumb';
import rank from './rank';
import nomenclature from './nomenclauture';
import layer from './layer';

Vue.use(Vuex);

const store = new Vuex.Store({
    modules: {
        auth,
        breadcrumb,
        rank,
        nomenclature,
        layer,
    },
    state: {
        countries: [],
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
            document.documentElement.style.overflowY = 'hidden';
        },
        closeModal(state) {
            state.modal.isActive = false;
            document.documentElement.style.overflowY = 'auto';
        },

    },
});

export default store;
