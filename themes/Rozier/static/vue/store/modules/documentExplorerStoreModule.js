import api from '../../api'
import {
    DOCUMENT_EXPLORER_REQUEST,
    DOCUMENT_EXPLORER_SUCCESS,
    DOCUMENT_EXPLORER_RESET,
    DOCUMENT_EXPLORER_FAILED,
    DOCUMENT_EXPLORER_OPEN,
    DOCUMENT_EXPLORER_CLOSE
} from '../mutationTypes'

/**
 * Module state
 */
const state = {
    isOpen: false,
    searchTerms: null,
    documents: [],
    trans: null,
    error: null,
}

/**
 * Getters
 */
const getters = {
    getSearchTerm: state => state.searchTerms,
    getDocuments: state => state.documents
}

/**
 * Actions
 */
const actions =  {
    openExplorer ({ commit, dispatch }) {
        // Make the search
        dispatch('makeSearch')

        // Open panel explorer
        commit(DOCUMENT_EXPLORER_OPEN)
    },
    closeExplorer ({ commit }) {
        commit(DOCUMENT_EXPLORER_CLOSE)
    },
    toggleExplorer ({ commit, dispatch, state }) {
        if (state.isOpen) {
            dispatch('closeExplorer')
        } else {
            dispatch('openExplorer')
        }
    },
    updateSearch ({ commit, dispatch }, searchTerms = '') {
        commit(DOCUMENT_EXPLORER_REQUEST, { searchTerms })

        // Make the search
        dispatch('makeSearch', searchTerms)
    },
    makeSearch ({ commit }, searchTerms = '') {
        api.getDocumentsFromSearchTerms(searchTerms)
            .then((result) => {
                if (!result) {
                    commit(DOCUMENT_EXPLORER_FAILED)
                } else {
                    commit(DOCUMENT_EXPLORER_SUCCESS, { result })
                }
            })
            .catch((error) => {
                console.error(error)
                commit(DOCUMENT_EXPLORER_FAILED, { error })
            })
    }
}

/**
 * Mutations
 */
const mutations = {
    [DOCUMENT_EXPLORER_REQUEST] (state, { searchTerms }) {
        state.searchTerms = searchTerms
    },
    [DOCUMENT_EXPLORER_SUCCESS] (state, { result }) {
        console.log(result)
        state.documents = result.documents
        state.documentsCount = result.documentsCount
        state.trans = result.trans
    },
    [DOCUMENT_EXPLORER_RESET] (state) {
        state.items = []
        state.searchTerms = ''
    },
    [DOCUMENT_EXPLORER_FAILED] (state, { error }) {
        state.items = []
        state.error = 'Request failed'
    },
    [DOCUMENT_EXPLORER_OPEN] (state) {
        state.isOpen = true
    },
    [DOCUMENT_EXPLORER_CLOSE] (state) {
        state.isOpen = false
        state.searchTerms = null
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
