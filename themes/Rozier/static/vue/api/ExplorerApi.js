import request from 'axios'
import {
    DOCUMENT_ENTITY
} from '../types/entityTypes'

export function getExplorerItems ({ entity, searchTerms, filters, filterExplorerSelection }) {
    switch (entity) {
        case DOCUMENT_ENTITY:
            return getDocuments({ searchTerms, filters, filterExplorerSelection })
    }
}

/**
 * Fetch Documents from search terms.
 *
 * @param {String} searchTerms
 * @param {Object} filters
 * @param {Object} filterExplorerSelection
 * @return Promise
 */
export function getDocuments ({ searchTerms, filters, filterExplorerSelection }) {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'toggleExplorer',
        search: searchTerms,
        page: filters ? filters.nextPage : null
    }

    if (filterExplorerSelection && filterExplorerSelection.id) {
        postData.folderId = filterExplorerSelection.id

        if (!filters.folderId || filters.folderId != postData.folderId) {
            postData.page = null
        }
    }

    return request({
        method: 'GET',
        url: RozierRoot.routes.documentsAjaxExplorer,
        params: postData
    })
        .then((response) => {
            if (typeof response.data != 'undefined' && response.data.documents) {
                return {
                    items: response.data.documents,
                    filters: response.data.filters,
                    trans: response.data.trans
                }
            } else {
                return null
            }
        })
        .catch((error) => {
            // TODO
            // Log request error or display a message
            throw new Error(error)
        })
}

/**
 * Fetch Documents from an array of document id.
 *
 * @param ids
 * @returns {Promise<R>|Promise.<T>}
 */
export function getItemsByIds (entity, ids = []) {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'documentsByIds',
        ids: ids
    }

    return request({
        method: 'GET',
        url: RozierRoot.routes.documentsAjaxByArray,
        params: postData
    })
        .then((response) => {
            if (typeof response.data != 'undefined' && response.data.documents) {
                return {
                    items: response.data.documents,
                    trans: response.data.trans
                }
            } else {
                return null
            }
        })
        .catch((error) => {
            // TODO
            // Log request error or display a message
            throw new Error(error.response.data.humanMessage)
        })
}
