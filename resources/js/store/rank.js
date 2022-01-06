export default {
    namespaced: true,

    state: {
        items: [],
    },

    getters: {
        getSpeciesRank(state) {
            return state.items.find(rank => rank.key === 'species');
        },

        getGenusRank(state) {
            return state.items.find(rank => rank.key === 'genus');
        },
    },

    mutations: {
        SET_ITEMS(state, value) {
            state.items = value
        },
    },
};
