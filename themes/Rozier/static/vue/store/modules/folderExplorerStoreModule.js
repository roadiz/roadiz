import api from '../../api'
import {
    FOLDER_EXPLORER_OPEN,
    FOLDER_EXPLORER_CLOSE,
    FOLDER_EXPLORER_REQUEST,
    FOLDER_EXPLORER_SUCCESS,
    FOLDER_EXPLORER_FAILED,
    FOLDER_EXPLORER_REFRESH,

    KEYBOARD_EVENT_ESCAPE
} from '../../types/mutationTypes'

const state = {
    isOpen: false,
    isLoading: false,
    folders: []
}

const getters = {}

const actions = {
    folderExplorerOpen ({ commit, dispatch }) {
        commit(FOLDER_EXPLORER_OPEN)

        dispatch('folderExplorerMakeSearch')
    },
    folderExplorerClose ({ commit }) {
        commit(FOLDER_EXPLORER_CLOSE)
    },
    folderExplorerToggle ({ dispatch, state }) {
        if (state.isOpen) {
            dispatch('folderExplorerClose')
        } else {
            dispatch('folderExplorerOpen')
        }
    },
    folderExplorerMakeSearch ({ commit }) {
        commit(FOLDER_EXPLORER_REQUEST)

        return api.getFolders()
            .then((result) => {
                if (!result) {
                    commit(FOLDER_EXPLORER_FAILED)
                } else {
                    commit(FOLDER_EXPLORER_SUCCESS, { result })
                }
            })
            .catch((error) => {
                commit(FOLDER_EXPLORER_FAILED, { error })
            })
    },
}

const mutations = {
    [FOLDER_EXPLORER_REQUEST] (state) {
        state.isLoading = true
    },
    [FOLDER_EXPLORER_SUCCESS] (state, { result }) {
        state.folders = result.folders
        state.isLoading = false
    },
    [FOLDER_EXPLORER_OPEN] (state) {
        state.isOpen = true
    },
    [FOLDER_EXPLORER_CLOSE] (state) {
        state.isOpen = false
    },
    [FOLDER_EXPLORER_FAILED] (state, { error }) {
        state.isLoading = false
        state.error = 'Request failed'
    },
    [KEYBOARD_EVENT_ESCAPE] (state) {
        state.isLoading = false
        state.isOpen = false
    }
}

export default {
    state,
    getters,
    actions,
    mutations
}
