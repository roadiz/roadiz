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
 * @file EntityAwareFactory.js
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

// Components
import DocumentPreviewListItem from '../components/DocumentPreviewListItem.vue'
import NodePreviewItem from '../components/NodePreviewItem.vue'
import JoinPreviewItem from '../components/JoinPreviewItem.vue'
import CustomFormPreviewItem from '../components/CustomFormPreviewItem.vue'
import NodeTypePreviewItem from '../components/NodeTypePreviewItem.vue'
import TagPreviewItem from '../components/TagPreviewItem.vue'

// Containers
import TagCreatorContainer from '../containers/TagCreatorContainer.vue'

export default class EntityAwareFactory {
    static getState (entity) {
        const result = {
            trans: {
                moreItems: ''
            }
        }

        // Default
        result.isFilterEnable = false

        switch (entity) {
        case DOCUMENT_ENTITY:
            result.currentListingView = DocumentPreviewListItem
            result.filterExplorerIcon = 'uk-icon-rz-folder-tree-mini'
            result.trans.moreItems = 'moreDocuments'
            result.isFilterEnable = true
            break
        case NODE_ENTITY:
            result.currentListingView = NodePreviewItem
            result.filterExplorerIcon = 'uk-icon-tags'
            result.trans.moreItems = 'moreNodes'
            result.isFilterEnable = true
            break
        case EXPLORER_PROVIDER_ENTITY:
        case JOIN_ENTITY:
            result.currentListingView = JoinPreviewItem
            result.trans.moreItems = 'moreEntities'
            break
        case CUSTOM_FORM_ENTITY:
            result.currentListingView = CustomFormPreviewItem
            break
        case NODE_TYPE_ENTITY:
            result.currentListingView = NodeTypePreviewItem
            result.trans.moreItems = 'moreNodeTypes'
            break
        case TAG_ENTITY:
            result.currentListingView = TagPreviewItem
            result.trans.moreItems = 'moreTags'
            result.isFilterEnable = true
            result.filterExplorerIcon = 'uk-icon-tags'
            break
        }

        return result
    }

    static getListingView (entity) {
        switch (entity) {
        case DOCUMENT_ENTITY:
            return DocumentPreviewListItem
        case NODE_ENTITY:
            return NodePreviewItem
        case EXPLORER_PROVIDER_ENTITY:
        case JOIN_ENTITY:
            return JoinPreviewItem
        case CUSTOM_FORM_ENTITY:
            return CustomFormPreviewItem
        case NODE_TYPE_ENTITY:
            return NodeTypePreviewItem
        case TAG_ENTITY:
            return TagPreviewItem
        }
    }

    static getWidgetView (entity) {
        switch (entity) {
        case TAG_ENTITY:
            return TagCreatorContainer
        default:
            return null
        }
    }
}
