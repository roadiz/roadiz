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
 * @file DrawerApi.js
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
 * Fetch Items from an array of ids. Depending of its entity type (document, node...).
 *
 * @param {String} entity
 * @param {Array} ids
 * @param filters
 * @returns {Promise<R>|Promise.<T>}
 */
export function getItemsByIds (entity, ids = [], filters) {
    switch (entity) {
    case DOCUMENT_ENTITY:
        return DocumentApi.getDocumentsByIds({ ids, filters })
    case NODE_ENTITY:
        return NodeApi.getNodesByIds({ ids, filters })
    case NODE_TYPE_ENTITY:
        return NodeTypeApi.getNodeTypesByIds({ ids, filters })
    case JOIN_ENTITY:
        return JoinApi.getJoinsByIds({ ids, filters })
    case CUSTOM_FORM_ENTITY:
        return CustomFormApi.getCustomFormsByIds({ ids, filters })
    case TAG_ENTITY:
        return TagApi.getTagsByIds({ ids, filters })
    case EXPLORER_PROVIDER_ENTITY:
        return ExplorerProviderApi.getItemsByIds({ ids, filters })
    }

    return Promise.reject(new Error('No type entity found'))
}
