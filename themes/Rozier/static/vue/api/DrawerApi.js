import {
    DOCUMENT_ENTITY,
    NODE_ENTITY,
    NODE_TYPE_ENTITY,
    JOIN_ENTITY,
    CUSTOM_FORM_ENTITY,
    TAG_ENTITY
} from '../types/entityTypes'
import * as DocumentApi from './DocumentApi'
import * as NodeApi from './NodeApi'
import * as NodeTypeApi from './NodeTypeApi'
import * as JoinApi from './JoinApi'
import * as CustomFormApi from './CustomFormApi'
import * as TagApi from './TagApi'

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
    }

    return Promise.reject(new Error('No type entity found'))
}
