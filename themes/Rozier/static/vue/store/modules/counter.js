import { COUNTER_INCREMENT } from '../mutationTypes'

const state = {
    number: 0
}


// GETTERS

const getters = {
    getNumber: state => state.number
}


// ACTIONS

const actions =  {
    increment ({ commit }) {
        commit(COUNTER_INCREMENT, {
            plus: 10
        })
    }
}


// MUTATIONS

const mutations = {
    [COUNTER_INCREMENT] (state, { plus }) {
        state.number += plus
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
