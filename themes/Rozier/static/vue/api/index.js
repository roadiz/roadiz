import * as NodesSourceSearchApi from './NodesSourceSearchApi'
import * as ExplorerApi from './ExplorerApi'
import * as FilterExplorerApi from './FilterExplorerApi'
import * as DrawerApi from './DrawerApi'
import * as TagApi from './TagApi'
import * as SplashScreenApi from './SplashScreenApi'

const api = {
    ...NodesSourceSearchApi,
    ...ExplorerApi,
    ...FilterExplorerApi,
    ...DrawerApi,
    ...TagApi,
    ...SplashScreenApi
}

export default api
