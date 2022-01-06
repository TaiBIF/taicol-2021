import Vue from 'vue';

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
            state.items.push(value);
        },
        TAXON_NAME(state, { onAfterSubmit, defaultValue = null }) {
            state.items.push({
                template: () => import('./../components/layers/TaxonNameLayer'),
                default: defaultValue,
                title: typeof defaultValue?.id === 'undefined' ? Vue.i18n.translate('forms.taxonName.create') : Vue.i18n.translate('functions.editTaxonName'),
                events: {
                    onAfterSubmit,
                },
            });
        },
        CLOSE(state) {
            state.items.pop();
        },
    },
};
