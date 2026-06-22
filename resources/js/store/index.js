import Vue from 'vue';
import Vuex from 'vuex';
import auth from './auth';
import breadcrumb from './breadcrumb';
import rank from './rank';
import nomenclature from './nomenclauture';
import layer from './layer';
import { nextZ } from './overlay';

Vue.use(Vuex);

const DEFAULT_LANGUAGE = 'zh-tw';

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
        lang: DEFAULT_LANGUAGE,
        modals: [],
    },
    actions: {},
    mutations: {
        // mutations:openModal/closeModal 改寫
        openModal(state, { component, props, title }) {
            state.modals.push({
                component,
                props: props || {},
                title: title || '',
                zIndex: nextZ(),
            });
            document.documentElement.style.overflowY = 'hidden';
        },
        closeModal(state) {
            state.modals.pop();
            if (state.modals.length === 0) {
                document.documentElement.style.overflowY = 'auto';
            }
        },
        SET_LANG(state, lang) {
            Vue.i18n.set(lang);  // 移除原本誤寫的 state.modal.lang = lang
        },
        setReferencePresetData(state, data) {
            state.referencePresetData = data;
        },
        setBindReferenceData(state, data) {
            state.bindReferenceData = data;
        },
    },
});

export default store;
