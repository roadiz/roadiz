import api from '../../api'
import {
    NODES_SEARCH_REQUEST,
    NODES_SEARCH_SUCCESS,
    NODES_SEARCH_RESET,
    NODES_SEARCH_FAILED,
    DOCUMENT_EXPLORER_REQUEST
} from '../mutationTypes'

/**
 * Module state
 */
const state = {
    searchTerms: null,
    items: []
}

/**
 * Getters
 */
const getters = {
    getSearchTerms: state => state.searchTerms,
    getItems: state => state.items
}

/**
 * Actions
 */
const actions =  {
    updateSearch ({ commit }, searchTerms = '') {
        // If search terms is not correct
        if (!searchTerms || searchTerms.length <= 1) {
            // Reset items list
            commit(NODES_SEARCH_RESET)
            return
        }

        commit(NODES_SEARCH_REQUEST, { searchTerms })

        api.getNodesSourceFromSearch(searchTerms)
            .then((items) => {
                commit(NODES_SEARCH_SUCCESS, { items })
            })
            .catch((error) => {
                commit(NODES_SEARCH_FAILED)
            })
    }
}

/**
 * Mutations
 */
const mutations = {
    [NODES_SEARCH_REQUEST] (state, { searchTerms }) {
        state.searchTerms = searchTerms
    },
    [NODES_SEARCH_SUCCESS] (state, { items }) {
        state.items = items
    },
    [NODES_SEARCH_FAILED] (state) {
        state.items = []
    },
    [NODES_SEARCH_RESET] (state) {
        state.items = []
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
