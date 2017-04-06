import {
    DOCUMENT_ENTITY,
    NODE_ENTITY,
    JOIN_ENTITY
} from '../types/entityTypes'
import * as DocumentApi from './DocumentApi'
import * as NodeApi from './NodeApi'
import * as JoinApi from './JoinApi'

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
        case JOIN_ENTITY:
            return JoinApi.getJoinsByIds({ ids, filters })
    }

    return Promise.reject(new Error('No type entity found'))
}
