import _ from 'lodash'
import {
    DOCUMENT_WIDGET_ADD_INSTANCE,
    DOCUMENT_WIDGET_REMOVE_INSTANCE,
    DOCUMENT_WIDGET_ADD_DOCUMENT,
    DOCUMENT_WIDGET_REMOVE_DOCUMENT,
    DOCUMENT_WIDGET_EDIT_INSTANCE,
    DOCUMENT_WIDGET_UPDATE_LIST,
    DOCUMENT_WIDGET_ENABLE_DROPZONE,
    DOCUMENT_WIDGET_DISABLE_DROPZONE,

    KEYBOARD_EVENT_ESCAPE,

    DOCUMENT_EXPLORER_INIT_DATA_REQUEST,
    DOCUMENT_EXPLORER_INIT_DATA_REQUEST_SUCCESS,
    DOCUMENT_EXPLORER_INIT_DATA_REQUEST_FAILED,
    DOCUMENT_EXPLORER_INIT_DATA_REQUEST_EMPTY,
    DOCUMENT_EXPLORER_CLOSE
} from '../mutationTypes'
import api from '../../api'

/**
 * State
 *
 * widgets: [{
 *    id: ...,
 *    isActive: false,
 *    documents: []
 * }]
 */
const state = {
    widgets: [],
    trans: null,
    selectedWidget: null
}

/**
 * Getters
 */
const getters = {
    documentWidgetsGetById: (state, getters) => (id) => {
        return state.widgets.find(widget => widget.id === id)
    }
}

/**
 * Actions
 */
const actions = {
    documentWidgetsAddInstance ({ commit }, documentWidget) {
        commit(DOCUMENT_WIDGET_ADD_INSTANCE, { documentWidget })
    },
    documentWidgetsRemoveInstance ({ commit }, documentWidgetToRemove) {
        commit(DOCUMENT_WIDGET_REMOVE_INSTANCE, { documentWidgetToRemove })
    },
    documentWidgetsInitData ({ commit }, { documentWidget, ids }) {
        commit(DOCUMENT_EXPLORER_INIT_DATA_REQUEST, { documentWidget })

        if (!ids || ids.length === 0) {
            commit(DOCUMENT_EXPLORER_INIT_DATA_REQUEST_EMPTY, { documentWidget })
            return
        }

        api.getDocumentsByIds(ids)
            .then((result) => {
                commit(DOCUMENT_EXPLORER_INIT_DATA_REQUEST_SUCCESS, { documentWidget, result })
            })
            .catch((error) => {
                commit(DOCUMENT_EXPLORER_INIT_DATA_REQUEST_FAILED, { documentWidget, error })
            })
    },
    documentWidgetsAddDocument ({ commit, state }, { documentWidget, document, newIndex }) {
        let widgetToChange = state.selectedWidget

        if (documentWidget) {
            widgetToChange = documentWidget
        }

        commit(DOCUMENT_WIDGET_ADD_DOCUMENT, { documentWidget: widgetToChange, document, newIndex })
    },
    documentWidgetMoveDocument ({ commit }, { documentWidget, document }) {

    },
    documentWidgetRemoveDocument ({ commit }, { documentWidget, document }) {
        commit(DOCUMENT_WIDGET_REMOVE_DOCUMENT, { documentWidget, document })
    },
    documentWidgetsExplorerButtonClick ({ commit, dispatch }, documentWidget) {
        commit(DOCUMENT_WIDGET_EDIT_INSTANCE, { documentWidget })

        if (!state.selectedWidget.isActive) {
            dispatch('documentExplorerClose')
        } else {
            dispatch('documentExplorerOpen')
        }
    },
    documentWidgetsDropzoneButtonClick ({ state, dispatch }, documentWidget) {
        if (documentWidget.isDropzoneEnable) {
            dispatch('documentWidgetDisableDropzone', { documentWidget })
        } else {
            dispatch('documentWidgetEnableDropzone', { documentWidget })
        }
    },
    documentWidgetEnableDropzone ({ commit }, { documentWidget }) {
        commit(DOCUMENT_WIDGET_ENABLE_DROPZONE, { documentWidget })
    },
    documentWidgetDisableDropzone ({ commit }, { documentWidget }) {
        commit(DOCUMENT_WIDGET_DISABLE_DROPZONE, { documentWidget })
    }
}

/**
 * Mutations
 */
const mutations = {
    [DOCUMENT_WIDGET_ADD_INSTANCE] (state, { documentWidget }) {
        state.widgets.push({
            id: documentWidget._uid,
            isActive: false,
            documents: [],
            errorMessage: null,
            isLoading: false,
            isDropzoneEnable: false
        })
    },
    [DOCUMENT_WIDGET_REMOVE_INSTANCE] (state, { documentWidgetToRemove }) {
        state.widgets = _.remove(state.widgets, (documentWidget) => {
            return documentWidget._uid === documentWidgetToRemove._uid
        })
    },
    [DOCUMENT_WIDGET_EDIT_INSTANCE] (state, { documentWidget }) {
        // Disable other document widget
        state.widgets.forEach((item) => {
            if (item !== documentWidget) {
                item.isActive = false
            }
        })

        // Toggle current document widget
        documentWidget.isActive = !documentWidget.isActive

        // Define the document widget as current selected widget
        state.selectedWidget = documentWidget
    },
    [DOCUMENT_WIDGET_ADD_DOCUMENT] (state, { documentWidget, document, newIndex = 0 }) {
        documentWidget.documents.push(document)
    },
    [DOCUMENT_WIDGET_UPDATE_LIST] (state, { documentWidget, newList }) {
        documentWidget.documents = newList
    },
    [DOCUMENT_WIDGET_REMOVE_DOCUMENT] (state, { documentWidget, document }) {
        let indexOf = documentWidget.documents.indexOf(document)
        if (indexOf >= 0) {
            documentWidget.documents.splice(indexOf, 1)
        }
    },
    [DOCUMENT_EXPLORER_CLOSE] (state) {
        state = state = disableActiveDocumentWidget(state)
    },
    [DOCUMENT_EXPLORER_INIT_DATA_REQUEST_SUCCESS] (state, { documentWidget, result }) {
        documentWidget.isLoading = false
        documentWidget.documents = result.documents
        state.trans = result.trans
    },
    [DOCUMENT_EXPLORER_INIT_DATA_REQUEST] (state, { documentWidget }) {
        documentWidget.isLoading = true
    },
    [DOCUMENT_EXPLORER_INIT_DATA_REQUEST_FAILED] (state, { documentWidget, error }) {
        documentWidget.isLoading = false
        documentWidget.errorMessage = error.message
    },
    [DOCUMENT_EXPLORER_INIT_DATA_REQUEST_EMPTY] (state, { documentWidget }) {
        documentWidget.isLoading = false
    },
    [KEYBOARD_EVENT_ESCAPE] (state) {
        state = disableActiveDocumentWidget(state)
    },
    [DOCUMENT_WIDGET_ENABLE_DROPZONE] (state, { documentWidget }) {
        // Disable other dropzone
        state.widgets.forEach((widget) => {
            widget.isDropzoneEnable = false
        })

        documentWidget.isDropzoneEnable = true
    },
    [DOCUMENT_WIDGET_DISABLE_DROPZONE] (state, { documentWidget }) {
        documentWidget.isDropzoneEnable = false
    },
}

function disableActiveDocumentWidget (state) {
    state.widgets.forEach((item) => {
        item.isActive = false
        item.isDropzoneEnable = false
    })

    state.selectedWidget = null

    return state
}

function findWidgetByInstance (state, documentWidget) {
    return state.widgets.find((item) => {
        return item === documentWidget
    })
}

export default {
    state,
    getters,
    actions,
    mutations
}
