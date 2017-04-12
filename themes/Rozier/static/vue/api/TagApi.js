import request from 'axios'

/**
 * Fetch all Tags.
 *
 * @param {String} searchTerms
 * @param {Object} filters
 * @param {Boolean} moreData
 * @returns {Promise<R>|Promise.<T>}
 */
export function getTags ({ searchTerms, filters, moreData }) {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'getTags',
        search: searchTerms,
        page: 1
    }

    if (moreData) {
        postData.page = filters ? filters.nextPage : 1
    }

    return request({
        method: 'GET',
        url: RozierRoot.routes.tagsAjaxExplorerList,
        params: postData
    })
        .then((response) => {
            if (typeof response.data !== 'undefined' && response.data.tags) {
                return {
                    items: response.data.tags,
                    filters: response.data.filters
                }
            }

            throw new Error('No tags found')
        })
        .catch((error) => {
            if (error.response && error.response.data) {
                throw new Error(error.response.data.humanMessage)
            } else {
                throw new Error(error.message)
            }
        })
}

/**
 * Fetch Tags from an array of node id.
 *
 * @param {Array} ids
 * @returns {Promise<R>|Promise.<T>}
 */
export function getTagsByIds ({ ids = [] }) {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'documentsByIds',
        ids: ids
    }

    return request({
        method: 'GET',
        url: RozierRoot.routes.tagsAjaxByArray,
        params: postData
    })
        .then((response) => {
            if (typeof response.data !== 'undefined' && response.data.tags) {
                return {
                    items: response.data.tags
                }
            } else {
                return {}
            }
        })
        .catch((error) => {
            // TODO
            // Log request error or display a message
            throw new Error(error.response.data.humanMessage)
        })
}

/**
 * Create a new tag.
 *
 * @param {String} tagName
 * @returns {Promise<R>|Promise.<T>}
 */
export function createTag({ tagName }) {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'documentsByIds',
        tagName: tagName
    }

    return request({
        method: 'POST',
        url: RozierRoot.routes.tagsAjaxCreate,
        params: postData
    })
        .then((response) => {
            if (typeof response.data !== 'undefined' && response.data.tag) {
                return response.data.tag
            }

            throw new Error('Tag creation error')
        })
        .catch((error) => {
            if (error.response && error.response.data) {
                throw new Error(error.response.data.humanMessage)
            } else {
                throw new Error(error.message)
            }
        })
}
