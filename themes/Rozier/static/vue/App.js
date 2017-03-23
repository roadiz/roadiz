import Vue from 'vue'
import store from './store'

// Containers
import NodeTypeFieldFormContainer from './containers/NodeTypeFieldFormContainer.vue'
import NodeTypeFieldFormContainerBis from './containers/NodeTypeFieldFormContainerBis.vue'
import PageContainer from './containers/PageContainer.vue'
import NodesSearchContainer from './containers/NodesSearchContainer.vue'

class AppVue {
    constructor () {
        this.navTrees = null
        this.mainContentComponents = []
        this.registeredComponents = {
            PageContainer,
            NodeTypeFieldFormContainer,
            NodeTypeFieldFormContainerBis,
            NodesSearchContainer
        }

        this.init()
        this.initListeners()
    }

    init () {
        this.buildNavTrees()
        this.buildMainContentComponents()
    }

    initListeners () {
        window.addEventListener('pageload', this.onPageLoaded.bind(this))
    }

    onPageLoaded (e) {
        this.buildMainContentComponents(e.detail)
    }

    destroyMainContentComponents () {
        this.mainContentComponents.forEach((component) => {
            component.$destroy()
        })
    }

    buildNavTrees () {
        if (document.getElementById('main-trees')) {
            this.navTrees = this.buildComponent('#main-trees')
        }
    }

    buildMainContentComponents (data) {
        // Destroy old components
        this.destroyMainContentComponents()

        // Looking for new vuejs component
        const $vueComponents = $('#main-content').find('[data-vuejs]')

        // Create each component
        $vueComponents.each((i, el) => {
            this.mainContentComponents.push(this.buildComponent(el))
        })
    }

    buildComponent (el) {
        return new Vue({
            delimiters: ['${', '}'],
            el: el,
            store,
            components: this.registeredComponents
        })
    }
}

const appVue = new AppVue()
