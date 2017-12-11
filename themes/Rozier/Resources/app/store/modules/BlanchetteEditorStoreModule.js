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
 * @file BlanchetteEditorStoreModule.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

import {
    BLANCHETTE_EDITOR_INIT,
    BLANCHETTE_EDITOR_IS_LOADING,
    BLANCHETTE_EDITOR_LOADED,
    BLANCHETTE_EDITOR_ERROR,
    BLANCHETTE_EDITOR_SAVE_SUCCESS
} from '../../types/mutationTypes'
import * as DocumentApi from '../../api/DocumentApi'
import * as Utils from '../../utils'

/**
 *  State
 */
const state = {
    isLoading: true,
    originalUrl: '',
    editor: null
}

/**
 *  Actions
 */
const actions = {
    blanchetteEditorInit ({ commit, dispatch }, { url, editor }) {
        dispatch('blanchetteEditorIsLoading')

        commit(BLANCHETTE_EDITOR_INIT, { url, editor })
    },
    blanchetteEditorIsLoading ({ commit }) {
        commit(BLANCHETTE_EDITOR_IS_LOADING)
    },
    blanchetteEditorLoaded ({ commit }) {
        commit(BLANCHETTE_EDITOR_LOADED)
    },
    blanchetteEditorSave ({ commit, state }, { url, filename }) {
        const blob = Utils.dataURItoBlob(url)
        const form = state.editor.getElementsByTagName('form')[0]

        let formData = new FormData(form)
        formData.append('form[editDocument]', blob, filename)

        commit(BLANCHETTE_EDITOR_IS_LOADING)

        return DocumentApi.setDocument(formData)
            .then((res) => {
                commit(BLANCHETTE_EDITOR_LOADED)
                if (res.data && res.data.path) {
                    window.UIkit.notify({
                        message: res.data.message,
                        status: 'success',
                        timeout: 2000,
                        pos: 'top-center'
                    })

                    commit(BLANCHETTE_EDITOR_SAVE_SUCCESS, { path: res.data.path })
                } else {
                    throw new Error('No path found')
                }
            })
            .catch((error) => {
                console.error(error)
                commit(BLANCHETTE_EDITOR_ERROR, { error })
                commit(BLANCHETTE_EDITOR_LOADED)
            })
    }
}

/**
 *  Mutations
 */
const mutations = {
    [BLANCHETTE_EDITOR_INIT] (state, { url, editor }) {
        state.originalUrl = url
        state.editor = editor
    },
    [BLANCHETTE_EDITOR_IS_LOADING] (state) {
        state.isLoading = true
    },
    [BLANCHETTE_EDITOR_ERROR] (state, { error }) {
        state.error = error.message
    },
    [BLANCHETTE_EDITOR_LOADED] (state) {
        state.isLoading = false
    },
    [BLANCHETTE_EDITOR_SAVE_SUCCESS] (state, { path }) {
        state.originalUrl = path
    }
}

export default {
    state,
    actions,
    mutations
}
