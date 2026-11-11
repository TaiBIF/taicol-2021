export default {
    namespaced: true,

    state: {
        items: [],
    },

    getters: {
        getKingdomRank(state) {
            return state.items.find(rank => rank.key === 'kingdom');
        },
        getSpeciesRank(state) {
            return state.items.find(rank => rank.key === 'species');
        },
        getGenusRank(state) {
            return state.items.find(rank => rank.key === 'genus');
        },
        getSubspeciesRank(state) {
            return state.items.find(rank => rank.key === 'subspecies');
        },
    },

    mutations: {
        SET_ITEMS(state, value) {
            state.items = value
        },
    },
};
