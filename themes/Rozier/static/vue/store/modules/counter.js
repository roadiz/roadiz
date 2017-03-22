import { COUNTER_INCREMENT, COUNTER_DECREMENT } from '../mutationTypes'

/**
 * Module state
 */
const state = {
    number: 0
}

/**
 * Getters
 */
const getters = {
    getNumber: state => state.number
}

/**
 * Actions
 */
const actions =  {
    increment ({ commit }, e, value = 10) {
        commit(COUNTER_INCREMENT, { value })
    },
    decrement ({ commit }, e, value = 10) {
        commit(COUNTER_DECREMENT, { value })
    },
}

/**
 * Mutations
 */
const mutations = {
    [COUNTER_INCREMENT] (state, { value }) {
        state.number += value
    },
    [COUNTER_DECREMENT] (state, { value }) {
        state.number -= value
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
