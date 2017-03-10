import Vue from 'vue'
import Counter from '../components/CounterButton.vue'
import store from '../store'

new Vue({
    el: '#app-2',
    store,
    render: h => h(Counter)
})