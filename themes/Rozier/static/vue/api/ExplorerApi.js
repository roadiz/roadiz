import {
    DOCUMENT_ENTITY,
    NODE_ENTITY
} from '../types/entityTypes'
import * as DocumentApi from './DocumentApi'
import * as NodeApi from './NodeApi'

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
    }

    return Promise.reject(new Error('No type entity found'))
}
