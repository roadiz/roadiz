import Vuex from 'vuex'

// modules
import counter from './modules/counter'
import nodesSourceSearch from './modules/nodesSourceSearch'

export default new Vuex.Store({
    modules: {
        counter,
        nodesSourceSearch
    }
})
