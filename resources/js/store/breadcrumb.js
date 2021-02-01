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
        SET_ITEMS(state, value) {
            state.items = value
        },
        CLEAR_ITEMS(state) {
            state.items = [];
        }
    },
};
