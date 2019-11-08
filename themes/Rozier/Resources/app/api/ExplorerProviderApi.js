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
 * @file ExplorerProviderApi.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

import request from 'axios'
import qs from 'qs'

/**
 * Fetch Joins from an array of node id.
 *
 * @param {Array} ids
 * @param filters
 * @returns {Promise<R>|Promise.<T>}
 */
export function getItemsByIds ({ ids = [], filters }) {
    const postData = {
        _token: window.RozierRoot.ajaxToken,
        ids: ids,
        providerClass: filters.providerClass
    }

    return request({
        method: 'GET',
        url: window.RozierRoot.routes.providerAjaxByArray,
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
            // Log request error or display a message
            throw new Error(error.response.data.humanMessage)
        })
}

/**
 * Fetch Joins from search terms.
 *
 * @param {String} searchTerms
 * @param {Object} preFilters
 * @param {Object} filters
 * @param {Object} filterExplorerSelection
 * @param {Boolean} moreData
 * @returns {Promise.<T>|Promise<R>}
 */
export function getItems ({ searchTerms, preFilters, filters, filterExplorerSelection, moreData }) {
    const postData = {
        _token: window.RozierRoot.ajaxToken,
        _action: 'toggleExplorer',
        providerClass: preFilters ? preFilters.providerClass : null,
        options: preFilters ? preFilters.providerOptions : null,
        search: searchTerms,
        page: 1
    }

    if (moreData) {
        postData.page = filters ? filters.nextPage : 1
    }

    return request({
        method: 'GET',
        url: window.RozierRoot.routes.providerAjaxExplorer + '?' + qs.stringify(postData) // need to use QS to compile array parameters
    })
        .then((response) => {
            if (typeof response.data !== 'undefined' && response.data.entities) {
                return {
                    items: response.data.entities,
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
