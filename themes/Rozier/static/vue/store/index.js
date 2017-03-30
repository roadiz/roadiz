import Vuex from 'vuex'
import {
    KEYBOARD_EVENT_SAVE
} from './mutationTypes'

// Modules
import nodesSourceSearch from './modules/nodesSourceSearchStoreModule'
import documentExplorer from './modules/documentExplorerStoreModule'
import folderExplorer from './modules/folderExplorerStoreModule'
import documentWidgets from './modules/documentWidgetsStoreModule'

export default new Vuex.Store({
    modules: {
        nodesSourceSearch,
        documentExplorer,
        folderExplorer,
        documentWidgets
    },
    state: {
        translations: RozierRoot.messages
    },
    mutations: {
        [KEYBOARD_EVENT_SAVE] () {
            // TODO
        }
    }
})
