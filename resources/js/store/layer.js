import Vue from 'vue';
import { nextZ } from './overlay';

export default {
    namespaced: true,

    state: {
        items: [],
    },

    getters: {
        getItems(state) {
            return state.items;
        },
    },

    mutations: {
        ADD(state, value) {
            state.items.push({ ...value, zIndex: nextZ() });
        },
        // TAXON_NAME — 在 push 物件裡加一個欄位
        TAXON_NAME(state, { onAfterSubmit, defaultValue = null }) {
            state.items.push({
                template: () => import('../components/layers/TaxonNameLayer.vue'),
                default: defaultValue,
                title: typeof defaultValue?.id === 'undefined'
                    ? Vue.i18n.translate('taxonName.create')
                    : Vue.i18n.translate('taxonName.edit'),
                events: { onAfterSubmit },
                zIndex: nextZ(),
            });
        },
        CLOSE(state) {
            state.items.pop();
        },
    },
};
