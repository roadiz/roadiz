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
 * @file NodesSourceSearchStoreModule.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

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
const actions = {
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
            .catch(() => {
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
