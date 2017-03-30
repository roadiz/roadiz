import request from 'axios'

/**
 * Fetch Documents from search terms.
 *
 * @param {String} searchTerms
 * @param {Object} filters
 * @return Promise
 */
export function getDocuments ({ searchTerms, filters, folderId }) {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'toggleExplorer',
        search: searchTerms,
        page: filters ? filters.nextPage : null
    }

    if (folderId) {
        postData.folderId = folderId
    }

    return request({
        method: 'GET',
        url: RozierRoot.routes.documentsAjaxExplorer,
        params: postData
    })
        .then((response) => {
            if (typeof response.data != 'undefined' && response.data.documents) {
                return response.data
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
export function getDocumentsByIds (ids = []) {
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
                return response.data
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
