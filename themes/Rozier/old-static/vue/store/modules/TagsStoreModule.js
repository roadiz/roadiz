import {
    TAG_CREATOR_READY,
    TAG_CREATE_NEW_REQUEST,
    TAG_CREATE_NEW_REQUEST_SUCCESS,
    TAG_CREATE_NEW_REQUEST_ERROR,

    EXPLORER_SUCCESS,
    EXPLORER_UPDATE_SEARCH_TERMS
} from '../../types/mutationTypes'
import * as TagApi from '../../api/TagApi'

/**
 * State
 */
const state = {
    isReady: false,
    isTagExisting: false,
    isLoading: false,
    searchTerms: '',
    error: ''
}

const getters = {
    tagsSearchExisting: state => (searchTerms, tags) => {
        console.log(tags)

        return state.isTagExisting
    }
}

/**
 * Actions
 */
const actions = {
    tagCreatorReady ({ commit }) {
        commit(TAG_CREATOR_READY)
    },
    tagsCreate ({ commit, dispatch }, { tagName }) {
        commit(TAG_CREATE_NEW_REQUEST,{ tagName })

        TagApi.createTag({ tagName })
            .then((tag) => {
                commit(TAG_CREATE_NEW_REQUEST_SUCCESS, tag)

                // Add item to selected drawer
                dispatch('drawersAddItem', { item: tag })

                // Reset explorer
                dispatch('explorerResetSearchTerms')
            })
            .catch((error) => {
                commit(TAG_CREATE_NEW_REQUEST_ERROR, error)
            })
    }
}

/**
 * Mutations
 */
const mutations = {
    [TAG_CREATOR_READY] (state) {
        state.isReady = true
    },
    [EXPLORER_UPDATE_SEARCH_TERMS] (state, {  searchTerms }) {
        state.searchTerms = searchTerms
    },
    [EXPLORER_SUCCESS] (state, { result }) {
        state.isTagExisting = false

        // Check if tag already exist
        if (state.isReady && state.searchTerms !== '') {
            for (let item of result.items) {
                if (item.name && state.searchTerms && item.name.toLowerCase() === state.searchTerms.toLowerCase()) {
                    state.isTagExisting = true
                }
            }
        }
    },
    [TAG_CREATE_NEW_REQUEST] (state) {
        state.isLoading = true
    },
    [TAG_CREATE_NEW_REQUEST_SUCCESS] (state, tag) {
        state.isLoading = false
    },
    [TAG_CREATE_NEW_REQUEST_ERROR] (state, error) {
        state.isLoading = false
        state.error = error.message
    }
}

export default {
    state,
    getters,
    actions,
    mutations
}
