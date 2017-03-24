import * as NodesSourceSearchApi from './NodesSourceSearchApi'
import * as DocumentExplorerApi from './DocumentExplorerApi'

const api = {
    ...NodesSourceSearchApi,
    ...DocumentExplorerApi
}

export default api
