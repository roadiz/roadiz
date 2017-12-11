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
 * @file DrawersStoreModule.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

import {
    remove
} from 'lodash'
import {
    DRAWERS_ADD_INSTANCE,
    DRAWERS_REMOVE_INSTANCE,
    DRAWERS_ADD_ITEM,
    DRAWERS_REMOVE_ITEM,
    DRAWERS_EDIT_INSTANCE,
    DRAWERS_UPDATE_LIST,
    DRAWERS_ENABLE_DROPZONE,
    DRAWERS_DISABLE_DROPZONE,
    DRAWERS_INIT_DATA_REQUEST,
    DRAWERS_INIT_DATA_REQUEST_SUCCESS,
    DRAWERS_INIT_DATA_REQUEST_FAILED,
    DRAWERS_INIT_DATA_REQUEST_EMPTY,

    KEYBOARD_EVENT_ESCAPE,

    EXPLORER_CLOSE
} from '../../types/mutationTypes'
import * as DrawerApi from '../../api/DrawerApi'
import EntityAwareFactory from '../../factories/EntityAwareFactory'

/**
 * State
 *
 * list: [{
 *    id: ...,
 *    isActive: false,
 *    items: []
 * }]
 */
const state = {
    list: [],
    trans: null,
    selectedDrawer: null
}

/**
 * Getters
 */
const getters = {
    drawersGetById: (state, getters) => (id) => {
        return state.list.find(drawer => drawer.id === id)
    },
    getDrawerFilters: (state) => {
        return state.selectedDrawer ? state.selectedDrawer.filters : null
    }
}

/**
 * Actions
 */
const actions = {
    drawersAddInstance ({ commit }, drawer) {
        commit(DRAWERS_ADD_INSTANCE, { drawer })
    },
    drawersRemoveInstance ({ commit }, drawerToRemove) {
        commit(DRAWERS_REMOVE_INSTANCE, { drawerToRemove })
    },
    drawersInitData ({ commit }, { drawer, entity, ids, filters, maxLength, minLength }) {
        commit(DRAWERS_INIT_DATA_REQUEST, { drawer, entity, ids, filters })

        // If no initial ids provided no need to use the api
        if (!ids || ids.length === 0 || !entity) {
            commit(DRAWERS_INIT_DATA_REQUEST_EMPTY, { drawer, maxLength, minLength })
        } else {
            // If ids provided, fetch data and fill the Drawer
            DrawerApi.getItemsByIds(entity, ids, filters)
                .then((result) => {
                    commit(DRAWERS_INIT_DATA_REQUEST_SUCCESS, { drawer, result, maxLength, minLength })
                })
                .catch((error) => {
                    commit(DRAWERS_INIT_DATA_REQUEST_FAILED, { drawer, error })
                })
        }
    },
    drawersAddItem ({ commit, state }, { drawer, item, newIndex }) {
        let drawerToChange = state.selectedDrawer

        if (drawer) {
            drawerToChange = drawer
        }

        if (!drawerToChange.acceptMore) {
            return
        }

        commit(DRAWERS_ADD_ITEM, { drawer: drawerToChange, item, newIndex })
    },
    drawersMoveItem ({ commit }, { drawer, item }) {

    },
    drawersRemoveItem ({ commit }, { drawer, item }) {
        commit(DRAWERS_REMOVE_ITEM, { drawer, item })
    },
    drawersExplorerButtonClick ({ commit, dispatch }, drawer) {
        commit(DRAWERS_EDIT_INSTANCE, { drawer })

        if (!state.selectedDrawer.isActive) {
            dispatch('explorerClose')
        } else {
            dispatch('explorerOpen', { entity: drawer.entity })
        }
    },
    drawersDropzoneButtonClick ({ state, dispatch }, drawer) {
        if (drawer.isDropzoneEnable) {
            dispatch('drawersDisableDropzone', { drawer })
        } else {
            dispatch('drawersEnableDropzone', { drawer })
        }
    },
    drawersEnableDropzone ({ commit }, { drawer }) {
        commit(DRAWERS_ENABLE_DROPZONE, { drawer })
    },
    drawersDisableDropzone ({ commit }, { drawer }) {
        commit(DRAWERS_DISABLE_DROPZONE, { drawer })
    }
}

/**
 * Mutations
 */
const mutations = {
    [DRAWERS_ADD_INSTANCE] (state, { drawer }) {
        state.list.push({
            entity: null,
            id: drawer._uid,
            isActive: false,
            items: [],
            errorMessage: null,
            isLoading: false,
            filters: {
                nodeTypes: null,
                nodeTypeField: null,
                providerClass: null
            },
            isDropzoneEnable: false,
            minLength: 0,
            maxLength: 999999,
            acceptMore: true
        })
    },
    [DRAWERS_REMOVE_INSTANCE] (state, { drawerToRemove }) {
        state.list = remove(state.list, (drawer) => {
            return drawer._uid === drawerToRemove._uid
        })
    },
    [DRAWERS_EDIT_INSTANCE] (state, { drawer }) {
        // Disable other drawers
        state.list.forEach((item) => {
            if (item !== drawer) {
                item.isActive = false
            }
        })

        // Set accept more
        drawer.acceptMore = drawer.items.length < drawer.maxLength

        // Toggle current drawer
        drawer.isActive = !drawer.isActive

        // Define the drawer as current selected drawer
        state.selectedDrawer = drawer
    },
    [DRAWERS_ADD_ITEM] (state, { drawer, item, newIndex = 0 }) {
        drawer.items.push(item)
        drawer.acceptMore = drawer.items.length < drawer.maxLength
    },
    [DRAWERS_UPDATE_LIST] (state, { drawer, newList }) {
        drawer.items = newList
        drawer.acceptMore = drawer.items.length < drawer.maxLength
    },
    [DRAWERS_REMOVE_ITEM] (state, { drawer, item }) {
        let indexOf = drawer.items.indexOf(item)
        if (indexOf >= 0) {
            drawer.items.splice(indexOf, 1)
        }

        drawer.acceptMore = drawer.items.length < drawer.maxLength
    },
    [EXPLORER_CLOSE] (state) {
        state = disableActiveDrawer(state)
    },
    [DRAWERS_INIT_DATA_REQUEST_SUCCESS] (state, { drawer, result, maxLength, minLength }) {
        drawer.isLoading = false
        drawer.items = result.items
        drawer.trans = result.trans
        drawer.maxLength = maxLength
        drawer.minLength = minLength
        drawer.acceptMore = result.items.length < maxLength
    },
    [DRAWERS_INIT_DATA_REQUEST] (state, { drawer, entity, filters }) {
        drawer.isLoading = true
        drawer.entity = entity
        drawer.filters = filters
        drawer.currentListingView = EntityAwareFactory.getListingView(entity)
    },
    [DRAWERS_INIT_DATA_REQUEST_FAILED] (state, { drawer, error }) {
        drawer.isLoading = false
        drawer.errorMessage = error.message
    },
    [DRAWERS_INIT_DATA_REQUEST_EMPTY] (state, { drawer, maxLength, minLength }) {
        drawer.isLoading = false
        drawer.acceptMore = true
        drawer.maxLength = maxLength
        drawer.minLength = minLength
    },
    [KEYBOARD_EVENT_ESCAPE] (state) {
        state = disableActiveDrawer(state)
    },
    [DRAWERS_ENABLE_DROPZONE] (state, { drawer }) {
        // Disable other dropzone
        state.list.forEach((drawer) => {
            drawer.isDropzoneEnable = false
        })

        drawer.isDropzoneEnable = true
    },
    [DRAWERS_DISABLE_DROPZONE] (state, { drawer }) {
        drawer.isDropzoneEnable = false
    }
}

function disableActiveDrawer (state) {
    state.list.forEach((drawer) => {
        drawer.isActive = false
        drawer.isDropzoneEnable = false
    })

    state.selectedDrawer = null

    return state
}

export default {
    state,
    getters,
    actions,
    mutations
}
