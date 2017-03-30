import * as NodesSourceSearchApi from './NodesSourceSearchApi'
import * as DocumentExplorerApi from './DocumentExplorerApi'
import * as FolderExplorerApi from './FolderExplorerApi'

const api = {
    ...NodesSourceSearchApi,
    ...DocumentExplorerApi,
    ...FolderExplorerApi
}

export default api
