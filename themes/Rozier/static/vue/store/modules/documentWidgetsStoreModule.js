import _ from 'lodash'
import {
    DOCUMENT_WIDGET_ADD_INSTANCE,
    DOCUMENT_WIDGET_REMOVE_INSTANCE,
    DOCUMENT_WIDGET_ADD_DOCUMENT,
    DOCUMENT_WIDGET_REMOVE_DOCUMENT,
    DOCUMENT_WIDGET_EDIT_INSTANCE,

    KEYBOARD_EVENT_ESCAPE,

    DOCUMENT_EXPLORER_INIT_DATA_REQUEST,
    DOCUMENT_EXPLORER_INIT_DATA_REQUEST_SUCCESS,
    DOCUMENT_EXPLORER_INIT_DATA_REQUEST_FAILED,
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

        api.getDocumentsByIds(ids)
            .then((result) => {
                commit(DOCUMENT_EXPLORER_INIT_DATA_REQUEST_SUCCESS, { documentWidget, result })
            })
            .catch((error) => {
                commit(DOCUMENT_EXPLORER_INIT_DATA_REQUEST_FAILED, { documentWidget, error })
            })
    },
    documentWidgetsAddDocument ({ commit }, document) {
        commit(DOCUMENT_WIDGET_ADD_DOCUMENT, { document })
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
            isLoading: false
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
    [DOCUMENT_WIDGET_ADD_DOCUMENT] (state, { document }) {
        if (state.selectedWidget) {
            state.selectedWidget.documents.push(document)
        }
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
    [KEYBOARD_EVENT_ESCAPE] (state) {
        state = disableActiveDocumentWidget(state)
    }
}

function disableActiveDocumentWidget (state) {
    state.widgets.forEach((item) => {
        item.isActive = false
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
