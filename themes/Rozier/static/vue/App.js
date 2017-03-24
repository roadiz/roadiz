import Vue from 'vue'
import store from './store'

// Containers
import NodeTypeFieldFormContainer from './containers/NodeTypeFieldFormContainer.vue'
import NodeTypeFieldFormContainerBis from './containers/NodeTypeFieldFormContainerBis.vue'
import PageContainer from './containers/PageContainer.vue'
import NodesSearchContainer from './containers/NodesSearchContainer.vue'
import DocumentExplorerContainer from './containers/DocumentExplorerContainer.vue'
import DocumentWidgetContainer from './containers/DocumentWidgetContainer.vue'

// Components
import DocumentExplorerButton from './components/DocumentExplorerButton.vue'

class AppVue {
    constructor () {
        this.navTrees = null
        this.documentExplorer = null
        this.mainContentComponents = []
        this.registeredContainers = {
            PageContainer,
            NodeTypeFieldFormContainer,
            NodeTypeFieldFormContainerBis,
            NodesSearchContainer,
            DocumentExplorerContainer,
            DocumentWidgetContainer
        }

        this.registeredComponents = {
            DocumentExplorerButton
        }

        this.vuejsElements = {
            ...this.registeredComponents,
            ...this.registeredContainers
        }

        this.init()
        this.initListeners()
    }

    init () {
        this.buildNavTrees()
        this.buildDocumentExplorer()
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

    buildDocumentExplorer () {
        if (document.getElementById('document-explorer')) {
            this.documentExplorer = this.buildComponent('#document-explorer')
        }
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
            components: this.vuejsElements
        })
    }
}

const appVue = new AppVue()
