export default {
    namespaced: true,

    state: {
        items: [],
    },

    mutations: {
        SET_ITEMS(state, value) {
            state.items = value
        },
    },
};
