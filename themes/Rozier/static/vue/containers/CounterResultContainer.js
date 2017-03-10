import Vue from 'vue'
import CounterResult from '../components/CounterResult.vue'
import store from '../store'

new Vue({
    el: '#app-1',
    store,
    render: h => h(CounterResult)
})