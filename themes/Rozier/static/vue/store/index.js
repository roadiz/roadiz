import Vuex from 'vuex'
import {
    KEYBOARD_EVENT_SAVE,
    KEYBOARD_EVENT_ESCAPE
} from '../types/mutationTypes'

// Modules
import nodesSourceSearch from './modules/NodesSourceSearchStoreModule'
import explorer from './modules/ExplorerStoreModule'
import drawers from './modules/DrawersStoreModule'
import filterExplorer from './modules/FilterExplorerStoreModule'
import tags from './modules/TagsStoreModule'

export default new Vuex.Store({
    modules: {
        nodesSourceSearch,
        explorer,
        filterExplorer,
        drawers,
        tags
    },
    state: {
        translations: RozierRoot.messages
    },
    actions: {
        escape ({ commit }) {
            commit(KEYBOARD_EVENT_ESCAPE)
        }
    },
    mutations: {
        [KEYBOARD_EVENT_SAVE] () {
            // TODO
        }
    }
})
