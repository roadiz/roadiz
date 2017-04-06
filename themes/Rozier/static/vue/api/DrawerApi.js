import {
    DOCUMENT_ENTITY,
    NODE_ENTITY
} from '../types/entityTypes'
import * as DocumentApi from './DocumentApi'
import * as NodeApi from './NodeApi'

/**
 * Fetch Items from an array of ids. Depending of its entity type (document, node...).
 *
 * @param {String} entity
 * @param {Array} ids
 * @returns {Promise<R>|Promise.<T>}
 */
export function getItemsByIds (entity, ids = []) {
    switch (entity) {
        case DOCUMENT_ENTITY:
            return DocumentApi.getDocumentsByIds({ ids })
        case NODE_ENTITY:
            return NodeApi.getNodesByIds({ ids })
    }

    return Promise.reject(new Error('No type entity found'))
}
