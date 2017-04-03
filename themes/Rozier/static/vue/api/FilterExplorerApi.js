import * as FolderExplorerApi from './FolderExplorerApi'
import {
    DOCUMENT_ENTITY
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
        default:
            return Promise.reject(new Error('Entity not found'))
    }
}
