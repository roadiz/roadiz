import {
    TAGS_UPDATE_VALUE,
    TAGS_REQUEST,
    TAGS_REQUEST_SUCCESS,
    TAGS_REQUEST_ERROR
} from '../../types/mutationTypes'
import api from '../../api'

/**
 * State
 */
const state = {
    options: [],
    value: [],
    isLoading: false,
    error: ''
}

/**
 * Actions
 */
const actions = {
    tagsUpdateValue ({ commit }, value) {
        commit(TAGS_UPDATE_VALUE, value)
    },
    tagsInitData ({ commit }) {
        commit(TAGS_REQUEST)

        return api.getTags()
            .then((tags) => {
                commit(TAGS_REQUEST_SUCCESS, tags)
            })
            .catch((error) => {
                commit(TAGS_REQUEST_ERROR, error)
            })
    }
}

/**
 * Mutations
 */
const mutations = {
    [TAGS_UPDATE_VALUE] (state, value) {
        state.value = value
    },
    [TAGS_REQUEST] (state) {
        state.isLoading = true
    },
    [TAGS_REQUEST_SUCCESS] (state, tags) {
        state.isLoading = false
        state.options = tags
    },
    [TAGS_REQUEST_ERROR] (state, error) {
        state.isLoading = false
        state.error = error
    }
}

export default {
    state,
    actions,
    mutations
}
