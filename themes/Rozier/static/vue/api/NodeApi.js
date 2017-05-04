import request from 'axios'

/**
 * Fetch Nodes from an array of node id.
 *
 * @param {Array} ids
 * @returns {Promise<R>|Promise.<T>}
 */
export function getNodesByIds ({ ids = [] }) {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'nodesByIds',
        ids: ids
    }

    return request({
        method: 'GET',
        url: RozierRoot.routes.nodesAjaxByArray,
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
 * Fetch Nodes from search terms.
 *
 * @param {String} searchTerms
 * @param {Object} preFilters
 * @param {Object} filters
 * @param {Object} filterExplorerSelection
 * @param {Boolean} moreData
 * @returns {Promise.<T>|Promise<R>}
 */
export function getNodes ({ searchTerms, preFilters, filters, filterExplorerSelection, moreData }) {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'toggleExplorer',
        search: searchTerms,
        page: 1
    }

    if (filterExplorerSelection) {
        if (filterExplorerSelection.id) {
            postData.tagId = filterExplorerSelection.id
        }
    }

    if (preFilters && preFilters.nodeTypes) {
        postData.nodeTypes = JSON.parse(preFilters.nodeTypes)
    }

    if (moreData) {
        postData.page = filters ? filters.nextPage : 1
    }

    return request({
        method: 'GET',
        url: RozierRoot.routes.nodesAjaxExplorer,
        params: postData
    })
        .then((response) => {
            if (typeof response.data !== 'undefined' && response.data.nodes) {
                return {
                    items: response.data.nodes,
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
