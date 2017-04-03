import api from '../../api'
import {
    FILTER_EXPLORER_OPEN,
    FILTER_EXPLORER_CLOSE,
    FILTER_EXPLORER_REQUEST,
    FILTER_EXPLORER_SUCCESS,
    FILTER_EXPLORER_FAILED,
    FILTER_EXPLORER_UPDATE,

    KEYBOARD_EVENT_ESCAPE
} from '../../types/mutationTypes'

const state = {
    isOpen: false,
    isLoading: false,
    items: [],
    selectedItem: null
}

const getters = {
    getFilterExplorerSelectedItem: state => state.selectedItem
}

const actions = {
    filterExplorerOpen ({ commit, dispatch }) {
        commit(FILTER_EXPLORER_OPEN)

        dispatch('filterExplorerMakeSearch')
    },
    filterExplorerUpdate ({ commit, dispatch }, { item }) {
        commit(FILTER_EXPLORER_UPDATE, { item })

        dispatch('explorerMakeSearch')
    },
    filterExplorerClose ({ commit }) {
        commit(FILTER_EXPLORER_CLOSE)
    },
    filterExplorerToggle ({ dispatch, state }) {
        if (state.isOpen) {
            dispatch('filterExplorerClose')
        } else {
            dispatch('filterExplorerOpen')
        }
    },
    filterExplorerMakeSearch ({ commit, getters }) {
        commit(FILTER_EXPLORER_REQUEST)

        const entity = getters.getExplorerEntity

        return api.getFilters({ entity })
            .then((result) => {
                if (!result) {
                    commit(FILTER_EXPLORER_FAILED)
                } else {
                    commit(FILTER_EXPLORER_SUCCESS, { result })
                }
            })
            .catch((error) => {
                commit(FILTER_EXPLORER_FAILED, { error })
            })
    },
}

const mutations = {
    [FILTER_EXPLORER_UPDATE] (state, { item }) {
        state.selectedItem = item
    },
    [FILTER_EXPLORER_REQUEST] (state) {
        state.isLoading = true
    },
    [FILTER_EXPLORER_SUCCESS] (state, { result }) {
        state.items = result.items
        state.isLoading = false
    },
    [FILTER_EXPLORER_OPEN] (state) {
        state.isOpen = true
    },
    [FILTER_EXPLORER_CLOSE] (state) {
        state.isOpen = false
        state.selectedItem = null
    },
    [FILTER_EXPLORER_FAILED] (state, { error }) {
        state.isLoading = false
        state.error = 'Request failed'
    },
    [KEYBOARD_EVENT_ESCAPE] (state) {
        state.isLoading = false
        state.isOpen = false
        state.selectedItem = null
    }
}

export default {
    state,
    getters,
    actions,
    mutations
}
