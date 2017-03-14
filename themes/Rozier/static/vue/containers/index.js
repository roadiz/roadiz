import Vue from 'vue'
import store from '../store'

// Containers
import NodeTypeFieldFormContainer from '../containers/NodeTypeFieldFormContainer.vue'
import NodeTypeFieldFormContainerBis from '../containers/NodeTypeFieldFormContainerBis.vue'
import PageContainer from '../containers/PageContainer.vue'

class AppVue {
    constructor () {
        this.appVue = null
        this.init()
        this.initListeners()
    }

    init () {
        this.appVue = new Vue({
            el: '#app',
            store,
            components: {
                PageContainer,
                NodeTypeFieldFormContainer,
                NodeTypeFieldFormContainerBis
            }
        })
    }

    initListeners () {
        window.addEventListener('pageload', (e) => {
            this.buildVue(e.detail)
        })
    }

    buildVue (data) {
        window.setTimeout(() => {
            var MyComponent = Vue.extend({
                // extension options
                store,
                template: data,
                components: {
                    PageContainer,
                    NodeTypeFieldFormContainer,
                    NodeTypeFieldFormContainerBis
                }
            })

            // all instances of `MyComponent` are created with
            // the pre-defined extension options
            var myComponentInstance = new MyComponent()

            myComponentInstance.$mount()
        }, 500)
    }
}

const appVue = new AppVue()
