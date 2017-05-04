import api from '../../api'
import {
    NODES_SEARCH_REQUEST,
    NODES_SEARCH_SUCCESS,
    NODES_SEARCH_RESET,
    NODES_SEARCH_FAILED,

    KEYBOARD_EVENT_ESCAPE,

    NODES_SEARCH_ENABLE_FOCUS,
    NODES_SEARCH_DISABLE_FOCUS
} from '../../types/mutationTypes'

/**
 * Module state
 */
const state = {
    searchTerms: null,
    items: [],
    isFocus: false,
    isOpen: false
}

/**
 * Getters
 */
const getters = {

}

/**
 * Actions
 */
const actions =  {
    nodeSourceSearchEnableFocus ({ commit }) {
        commit(NODES_SEARCH_ENABLE_FOCUS)
    },
    nodeSourceSearchDisableFocus ({ commit }) {
        commit(NODES_SEARCH_DISABLE_FOCUS)
    },
    nodesSourceSearchUpdate ({ commit }, searchTerms = '') {
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
    [NODES_SEARCH_ENABLE_FOCUS] (state) {
        state.isFocus = true
    },
    [NODES_SEARCH_DISABLE_FOCUS] (state) {
        state.isFocus = false
    },
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
    },
    [KEYBOARD_EVENT_ESCAPE] (state) {
        state.isFocus = false
    }
}

export default {
    state,
    getters,
    actions,
    mutations
}
