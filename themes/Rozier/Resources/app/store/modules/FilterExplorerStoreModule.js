import api from '../../api'
import {
    FILTER_EXPLORER_OPEN,
    FILTER_EXPLORER_CLOSE,
    FILTER_EXPLORER_REQUEST,
    FILTER_EXPLORER_SUCCESS,
    FILTER_EXPLORER_FAILED,
    FILTER_EXPLORER_UPDATE,
    FILTER_EXPLORER_RESET,
    KEYBOARD_EVENT_ESCAPE
} from '../../types/mutationTypes'

import {
    DOCUMENT_ENTITY,
    NODE_ENTITY,
    TAG_ENTITY
} from '../../types/entityTypes'

const initialState = {
    isOpen: false,
    isLoading: false,
    items: [],
    entity: null,
    icons: {
        normal: 'uk-icon-circle-o',
        active: 'uk-icon-circle'
    },
    selectedItem: null
}

const state = { ...initialState }

const getters = {
    getFilterExplorerSelectedItem: state => state.selectedItem
}

const actions = {
    filterExplorerOpen ({ commit, dispatch, getters }) {
        const entity = getters.getExplorerEntity

        commit(FILTER_EXPLORER_OPEN, { entity })

        dispatch('explorerResetSearchTerms')
        dispatch('filterExplorerMakeSearch')
    },
    filterExplorerUpdate ({ commit, dispatch }, { item }) {
        commit(FILTER_EXPLORER_UPDATE, { item })

        dispatch('explorerMakeSearch')
    },
    filterExplorerClose ({ commit }) {
        commit(FILTER_EXPLORER_CLOSE)
    },
    filterExplorerReset ({ commit }) {
        commit(FILTER_EXPLORER_RESET)
    },
    filterExplorerToggle ({ dispatch, state }) {
        if (state.isOpen) {
            dispatch('filterExplorerClose')
        } else {
            dispatch('filterExplorerOpen')
        }
    },
    filterExplorerMakeSearch ({ commit, state }) {
        commit(FILTER_EXPLORER_REQUEST)

        const entity = state.entity

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
    }
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
    [FILTER_EXPLORER_OPEN] (state, { entity }) {
        state.isOpen = true
        state.entity = entity

        switch (entity) {
        case DOCUMENT_ENTITY:
            state.icons.normal = 'uk-icon-folder'
            state.icons.active = 'uk-icon-folder-open'
            break
        case NODE_ENTITY:
            state.icons.normal = 'uk-icon-tag'
            state.icons.active = 'uk-icon-tag'
            break
        case TAG_ENTITY:
            state.icons.normal = 'uk-icon-tag'
            state.icons.active = 'uk-icon-tag'
            break
        }
    },
    [FILTER_EXPLORER_CLOSE] (state) {
        state.isOpen = false
    },
    [FILTER_EXPLORER_RESET] (state) {
        state.selectedItem = null
        state.items = []
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
