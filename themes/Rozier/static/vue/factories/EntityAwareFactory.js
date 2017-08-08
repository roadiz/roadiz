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
