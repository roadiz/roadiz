/*
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file TagsStoreModule.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

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
        commit(TAG_CREATE_NEW_REQUEST, { tagName })

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
    [EXPLORER_UPDATE_SEARCH_TERMS] (state, { searchTerms }) {
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
