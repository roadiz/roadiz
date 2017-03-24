import Vuex from 'vuex'

// modules
import counter from './modules/counter'
import nodesSourceSearch from './modules/nodesSourceSearchStoreModule'
import documentExplorer from './modules/documentExplorerStoreModule'

export default new Vuex.Store({
    modules: {
        counter,
        nodesSourceSearch,
        documentExplorer
    }
})
