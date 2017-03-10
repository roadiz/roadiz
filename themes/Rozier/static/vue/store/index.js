import Vuex from 'vuex'

// modules
import counter from './modules/counter'

const state = {
    number: 0
}

export default new Vuex.Store({
    modules: {
        counter
    }
})