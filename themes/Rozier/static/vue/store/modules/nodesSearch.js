import {
    NODES_SEARCH_REQUEST,
    NODES_SEARCH_SUCCESS,
    NODES_SEARCH_FAILURE
} from '../mutationTypes'

/**
 * Module state
 */
const state = {
    search: null,
    items: []
}

/**
 * Getters
 */
const getters = {
    getSearch: state => state.search,
    getItems: state => state.items
}

/**
 * Actions
 */
const actions =  {
    updateSearch ({ commit }, search = '') {
        commit(NODES_SEARCH_REQUEST, { search })

        window.setTimeout(() => {
            let items = [{
                name: 'test1'
            }, {
                name: 'test2'
            }]

            console.log(items)
            commit(NODES_SEARCH_SUCCESS, { items })
        }, 3000)
    }
}

/**
 * Mutations
 */
const mutations = {
    [NODES_SEARCH_REQUEST] (state, { search }) {
        state.search = search
    },
    [NODES_SEARCH_SUCCESS] (state, { items }) {
        state.items = items
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
