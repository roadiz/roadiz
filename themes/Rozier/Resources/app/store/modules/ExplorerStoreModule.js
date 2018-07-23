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
 * @file ExplorerStoreModule.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

import Vue from 'vue'
import api from '../../api'
import {
    EXPLORER_REQUEST,
    EXPLORER_SUCCESS,
    EXPLORER_RESET,
    EXPLORER_FAILED,
    EXPLORER_OPEN,
    EXPLORER_CLOSE,
    EXPLORER_LOAD_MORE,
    EXPLORER_LOAD_MORE_SUCCESS,
    EXPLORER_IS_LOADED,
    EXPLORER_UPDATE_SEARCH_TERMS,

    FILTER_EXPLORER_UPDATE,

    KEYBOARD_EVENT_ESCAPE
} from '../../types/mutationTypes'
import EntityAwareFactory from '../../factories/EntityAwareFactory'

/**
 * Module state
 */
const initialState = {
    searchTerms: '',
    isOpen: false,
    isLoading: false,
    isLoadingMore: false,
    items: [],
    trans: {
        moreItems: ''
    },
    filters: {},
    entity: null,
    error: '',
    currentListingView: null,
    isFilterEnable: null,
    filterExplorerIcon: 'uk-icon-cog',
    widgetView: null
}

const state = { ...initialState }

/**
 * Getters
 */
const getters = {
    getExplorerEntity: state => state.entity,
    getExplorerSearchTerms: state => state.searchTerms
}

/**
 * Actions
 */
const actions = {
    async explorerOpen ({ commit, dispatch }, { entity }) {
        dispatch('filterExplorerClose')

        // Reset explorer
        commit(EXPLORER_RESET)

        // Open panel explorer
        commit(EXPLORER_OPEN, { entity })

        // Make the search
        await dispatch('explorerMakeSearch')

        commit(EXPLORER_IS_LOADED)
    },
    explorerClose ({ commit, dispatch }) {
        dispatch('filterExplorerClose')
        dispatch('filterExplorerReset')
        commit(EXPLORER_RESET)
        commit(EXPLORER_CLOSE)
    },
    explorerToggle ({ dispatch, state }) {
        if (state.isOpen) {
            dispatch('explorerClose')
        } else {
            dispatch('explorerOpen')
        }
    },
    explorerResetSearchTerms ({ commit, state, dispatch }) {
        const searchTerms = ''

        if (state.searchTerms === searchTerms) {
            commit(EXPLORER_UPDATE_SEARCH_TERMS, { searchTerms })
        } else {
            dispatch('explorerUpdateSearch', { searchTerms })
        }
    },
    explorerUpdateSearch ({ commit, dispatch }, { searchTerms }) {
        commit(EXPLORER_UPDATE_SEARCH_TERMS, { searchTerms })
        dispatch('explorerMakeSearch')
    },
    explorerMakeSearch ({ commit, state, getters }) {
        const entity = state.entity
        const searchTerms = state.searchTerms
        const preFilters = getters.getDrawerFilters
        const filters = state.filters
        const filterExplorerSelection = getters.getFilterExplorerSelectedItem
        const moreData = state.isLoadingMore

        commit(EXPLORER_REQUEST)

        return api.getExplorerItems({
            entity,
            searchTerms,
            preFilters,
            filters,
            filterExplorerSelection,
            moreData
        })
            .then((result) => {
                commit(EXPLORER_SUCCESS, { result })
            })
            .catch((error) => {
                console.error(error)
                commit(EXPLORER_FAILED, { error })
            })
    },
    async explorerLoadMore ({ commit, dispatch }) {
        commit(EXPLORER_LOAD_MORE)

        await dispatch('explorerMakeSearch')

        commit(EXPLORER_LOAD_MORE_SUCCESS)
    }
}

/**
 * Mutations
 */
const mutations = {
    [EXPLORER_REQUEST] (state) {
        if (!state.isLoadingMore) {
            state.isLoading = true
        }
    },
    [EXPLORER_UPDATE_SEARCH_TERMS] (state, { searchTerms }) {
        state.searchTerms = searchTerms
    },
    [EXPLORER_SUCCESS] (state, { result }) {
        state.isLoading = false

        if (state.isLoadingMore) {
            state.items = [...state.items, ...result.items]
        } else {
            state.items = result.items
        }

        state.filters = result.filters
    },
    [EXPLORER_LOAD_MORE] (state) {
        state.isLoadingMore = true
    },
    [EXPLORER_LOAD_MORE_SUCCESS] (state) {
        state.isLoadingMore = false
    },
    [FILTER_EXPLORER_UPDATE] (state) {
        state.filters = {}
    },
    [EXPLORER_RESET] (state) {
        // Reset state
        for (let f in state) {
            if (state.hasOwnProperty(f)) {
                Vue.set(state, f, initialState[f])
            }
        }
    },
    [EXPLORER_FAILED] (state) {
        state.isLoading = false
        state.isLoadingMore = false
        state.error = 'Request failed'
    },
    [EXPLORER_OPEN] (state, { entity }) {
        state.isOpen = true
        state.isLoading = true
        state.entity = entity
        state.trans.moreItems = ''

        // Change widget view
        state.widgetView = EntityAwareFactory.getWidgetView(entity)

        // Get the new specific entity state
        const previewState = EntityAwareFactory.getState(entity)

        // Set previewState value to the state
        for (let f in previewState) {
            if (state.hasOwnProperty(f)) {
                Vue.set(state, f, previewState[f])
            }
        }
    },
    [EXPLORER_IS_LOADED] (state) {
        state.isLoading = false
    },
    [EXPLORER_CLOSE] (state) {
        state.isOpen = false
        state.isLoading = false
    },
    [KEYBOARD_EVENT_ESCAPE] () {
        state.isOpen = false
    }
}

export default {
    state,
    getters,
    actions,
    mutations
}
