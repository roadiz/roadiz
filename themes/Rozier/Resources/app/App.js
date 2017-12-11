/*
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file App.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

import Vue from 'vue'
import store from './store'
import $ from 'jquery'

// Services
import KeyboardEventService from './services/KeyboardEventService'
import LoginCheckService from './services/LoginCheckService'

// Containers
import NodeTypeFieldFormContainer from './containers/NodeTypeFieldFormContainer.vue'
import NodesSearchContainer from './containers/NodesSearchContainer.vue'
import DrawerContainer from './containers/DrawerContainer.vue'
import ExplorerContainer from './containers/ExplorerContainer.vue'
import FilterExplorerContainer from './containers/FilterExplorerContainer.vue'
import TagsEditorContainer from './containers/TagsEditorContainer.vue'
import DocumentPreviewContainer from './containers/DocumentPreviewContainer.vue'
import BlanchetteEditorContainer from './containers/BlanchetteEditorContainer.vue'
import ModalContainer from './containers/ModalContainer.vue'

// Components
import Overlay from './components/Overlay.vue'

import {
    KEYBOARD_EVENT_ESCAPE
} from './types/mutationTypes'

/**
 * Root entry for VueJS App.
 */
export default class AppVue {
    constructor () {
        this.services = []
        this.navTrees = null
        this.containers = null
        this.documentExplorer = null
        this.mainContentComponents = []
        this.registeredContainers = {
            NodeTypeFieldFormContainer,
            NodesSearchContainer,
            DrawerContainer,
            ExplorerContainer,
            FilterExplorerContainer,
            TagsEditorContainer,
            DocumentPreviewContainer,
            BlanchetteEditorContainer,
            ModalContainer
        }

        this.registeredComponents = {
            Overlay
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
        this.buildOtherContainers()
        this.buildMainContentComponents()
        this.initServices()
    }

    initListeners () {
        window.addEventListener('pagechange', this.onPageChange.bind(this))
        window.addEventListener('pageload', this.onPageLoaded.bind(this))
    }

    initServices () {
        this.services.push(new KeyboardEventService(store))
        this.services.push(new LoginCheckService(store))
    }

    onPageChange () {
        store.commit(KEYBOARD_EVENT_ESCAPE)
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

    buildOtherContainers () {
        if (document.getElementById('vue-containers')) {
            this.containers = this.buildComponent('#vue-containers')
        }
    }

    buildNavTrees () {
        if (document.getElementById('main-trees')) {
            this.navTrees = this.buildComponent('#main-trees')
        }
    }

    buildMainContentComponents () {
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
