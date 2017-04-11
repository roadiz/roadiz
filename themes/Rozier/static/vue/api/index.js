import * as NodesSourceSearchApi from './NodesSourceSearchApi'
import * as ExplorerApi from './ExplorerApi'
import * as FilterExplorerApi from './FilterExplorerApi'
import * as DrawerApi from './DrawerApi'
import * as TagApi from './TagApi'

const api = {
    ...NodesSourceSearchApi,
    ...ExplorerApi,
    ...FilterExplorerApi,
    ...DrawerApi,
    ...TagApi
}

export default api
