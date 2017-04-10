import request from 'axios'

/**
 * Fetch NodeTypes from an array of node name.
 *
 * @param {Array} ids
 * @returns {Promise<R>|Promise.<T>}
 */
export function getNodeTypesByIds ({ ids = [] }) {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'nodeTypesByIds',
        names: ids
    }

    return request({
        method: 'GET',
        url: RozierRoot.routes.nodeTypesAjaxByArray,
        params: postData
    })
        .then((response) => {
            if (typeof response.data !== 'undefined' && response.data.items) {
                return {
                    items: response.data.items
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
 * Fetch NodeTypes from search terms.
 *
 * @param {String} searchTerms
 * @param {Object} preFilters
 * @param {Object} filters
 * @param {Object} filterExplorerSelection
 * @param {Boolean} moreData
 * @returns {Promise.<T>|Promise<R>}
 */
export function getNodeTypes ({ searchTerms, preFilters, filters, filterExplorerSelection, moreData }) {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'toggleExplorer',
        search: searchTerms,
        page: 1
    }

    if (moreData) {
        postData.page = filters ? filters.nextPage : 1
    }

    return request({
        method: 'GET',
        url: RozierRoot.routes.nodeTypesAjaxExplorer,
        params: postData
    })
        .then((response) => {
            if (typeof response.data !== 'undefined' && response.data.nodeTypes) {
                return {
                    items: response.data.nodeTypes,
                    filters: response.data.filters
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
