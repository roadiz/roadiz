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
 * @file TagApi.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

import request from 'axios'

/**
 * Fetch all Tags.
 *
 * @param {String} searchTerms
 * @param {Object} filters
 * @param filterExplorerSelection
 * @param {Boolean} moreData
 * @returns {Promise<R>|Promise.<T>}
 */
export function getTags ({ searchTerms, filters, filterExplorerSelection, moreData }) {
    const postData = {
        _token: window.RozierRoot.ajaxToken,
        _action: 'getTags',
        search: searchTerms,
        page: 1
    }

    if (moreData) {
        postData.page = filters ? filters.nextPage : 1
    }

    if (filterExplorerSelection && filterExplorerSelection.id) {
        postData.tagId = filterExplorerSelection.id
    }

    return request({
        method: 'GET',
        url: window.RozierRoot.routes.tagsAjaxExplorerList,
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
        _token: window.RozierRoot.ajaxToken,
        _action: 'documentsByIds',
        ids: ids
    }

    return request({
        method: 'GET',
        url: window.RozierRoot.routes.tagsAjaxByArray,
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
export function createTag ({ tagName }) {
    const postData = {
        _token: window.RozierRoot.ajaxToken,
        _action: 'documentsByIds',
        tagName: tagName
    }

    return request({
        method: 'POST',
        url: window.RozierRoot.routes.tagsAjaxCreate,
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
