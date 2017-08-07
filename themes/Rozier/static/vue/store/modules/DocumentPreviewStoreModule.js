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
 * @file DocumentPreviewStoreModule.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

import {
    DOCUMENT_PREVIEW_INIT,
    DOCUMENT_PREVIEW_DESTROY,
    DOCUMENT_PREVIEW_CLOSE,
    DOCUMENT_PREVIEW_OPEN,

    KEYBOARD_EVENT_SPACE,
    KEYBOARD_EVENT_ESCAPE
} from '../../types/mutationTypes'

/**
 * State
 */
const state = {
    document: null,
    isVisible: false,
    isLoading: false
}

const getters = {
    documentPreviewGetDocument: state => state.document
}

/**
 * Actions
 */
const actions = {
    documentPreviewInit ({ commit }, { document }) {
        commit(DOCUMENT_PREVIEW_INIT, { document })
    },
    documentPreviewDestroy ({ commit }) {
        commit(DOCUMENT_PREVIEW_DESTROY)
    },
    documentPreviewClose ({ commit }) {
        commit(DOCUMENT_PREVIEW_CLOSE)
    },
    documentPreviewOpen ({ commit }) {
        commit(DOCUMENT_PREVIEW_OPEN)
    }
}

/**
 * Mutations
 */
const mutations = {
    [DOCUMENT_PREVIEW_INIT] (state, { document }) {
        state.document = document
    },
    [DOCUMENT_PREVIEW_DESTROY] (state) {
        if (!state.isVisible) {
            state.document = null
        }
    },
    [KEYBOARD_EVENT_SPACE] (state) {
        if (state.document !== null && !state.isVisible) {
            state.isVisible = true
        } else if (state.isVisible) {
            state.isVisible = false
            state.document = null
        }
    },
    [KEYBOARD_EVENT_ESCAPE] (state) {
        state.isVisible = false
        state.document = null
    },
    [DOCUMENT_PREVIEW_OPEN] (state) {
        state.isVisible = true
    },
    [DOCUMENT_PREVIEW_CLOSE] (state) {
        state.isVisible = false
        state.document = null
    }
}

export default {
    state,
    getters,
    actions,
    mutations
}
