import request from 'axios'

/**
 * Fetch Documents from an array of document id.
 *
 * @param {Array} ids
 * @returns {Promise<R>|Promise.<T>}
 */
export function getDocumentsByIds ({ ids = [] }) {
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
            if (typeof response.data !== 'undefined' && response.data.documents) {
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

/**
 * Fetch Documents from search terms.
 *
 * @param {String} searchTerms
 * @param {Object} filters
 * @param {Object} filterExplorerSelection
 * @param {Boolean} moreData
 * @return Promise
 */
export function getDocuments ({ searchTerms, filters, filterExplorerSelection, moreData }) {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'toggleExplorer',
        search: searchTerms,
        page: 1
    }

    if (filterExplorerSelection && filterExplorerSelection.id) {
        postData.folderId = filterExplorerSelection.id
    }

    if (moreData) {
        postData.page = filters ? filters.nextPage : 1
    }

    return request({
        method: 'GET',
        url: RozierRoot.routes.documentsAjaxExplorer,
        params: postData
    })
        .then((response) => {
            if (typeof response.data !== 'undefined' && response.data.documents) {
                return {
                    items: response.data.documents,
                    filters: response.data.filters,
                    trans: response.data.trans
                }
            } else {
                return {}
            }
        })
        .catch((error) => {
            // TODO
            // Log request error or display a message
            throw new Error(error)
        })
}

export function setDocument (formData) {
    return request.post(window.location.href, formData, {
        headers: {'Accept': 'application/json'}
    })
}
