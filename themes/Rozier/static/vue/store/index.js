import Vuex from 'vuex'

// modules
import counter from './modules/counter'
import nodesSearch from './modules/nodesSearch'

export default new Vuex.Store({
    modules: {
        counter,
        nodesSearch
    }
})
