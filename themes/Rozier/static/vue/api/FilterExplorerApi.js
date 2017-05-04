import * as FolderExplorerApi from './FolderExplorerApi'
import * as TagExplorerApi from './TagExplorerApi'
import {
    DOCUMENT_ENTITY,
    NODE_ENTITY
} from '../types/entityTypes'

/**
 * Fetch filters.
 *
 * @return Promise
 */
export function getFilters ({ entity }) {
    switch (entity) {
        case DOCUMENT_ENTITY:
            return FolderExplorerApi.getFolders()
        case NODE_ENTITY:
            return TagExplorerApi.getTags()
        default:
            return Promise.reject(new Error('Entity not found'))
    }
}
