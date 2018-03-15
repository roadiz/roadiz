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
 * @file ExplorerApi.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

import {
    DOCUMENT_ENTITY,
    NODE_ENTITY,
    NODE_TYPE_ENTITY,
    JOIN_ENTITY,
    CUSTOM_FORM_ENTITY,
    TAG_ENTITY,
    EXPLORER_PROVIDER_ENTITY
} from '../types/entityTypes'
import * as DocumentApi from './DocumentApi'
import * as NodeApi from './NodeApi'
import * as NodeTypeApi from './NodeTypeApi'
import * as JoinApi from './JoinApi'
import * as CustomFormApi from './CustomFormApi'
import * as TagApi from './TagApi'
import * as ExplorerProviderApi from './ExplorerProviderApi'

/**
 * Get items for the Explorer panel. Depending of its entity type (document, node...).
 *
 * @param {String} entity
 * @param {String} searchTerms
 * @param {Object} preFilters
 * @param {Object} filters
 * @param {Object} filterExplorerSelection
 * @param moreData
 * @returns {*}
 */
export function getExplorerItems ({ entity, searchTerms, preFilters, filters, filterExplorerSelection, moreData = false }) {
    switch (entity) {
    case DOCUMENT_ENTITY:
        return DocumentApi.getDocuments({ searchTerms, preFilters, filters, filterExplorerSelection, moreData })
    case NODE_ENTITY:
        return NodeApi.getNodes({ searchTerms, preFilters, filters, filterExplorerSelection, moreData })
    case NODE_TYPE_ENTITY:
        return NodeTypeApi.getNodeTypes({ searchTerms, preFilters, filters, filterExplorerSelection, moreData })
    case JOIN_ENTITY:
        return JoinApi.getJoins({ searchTerms, preFilters, filters, filterExplorerSelection, moreData })
    case CUSTOM_FORM_ENTITY:
        return CustomFormApi.getCustomForms({ searchTerms, preFilters, filters, filterExplorerSelection, moreData })
    case TAG_ENTITY:
        return TagApi.getTags({ searchTerms, preFilters, filters, filterExplorerSelection, moreData })
    case EXPLORER_PROVIDER_ENTITY:
        return ExplorerProviderApi.getItems({ searchTerms, preFilters, filters, filterExplorerSelection, moreData })
    }

    return Promise.reject(new Error('No type entity found'))
}
