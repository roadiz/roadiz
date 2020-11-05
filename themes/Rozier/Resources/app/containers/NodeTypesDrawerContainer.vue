<!-- Inline template 'views/widgets/drawer.html.twig' -->
<template>
    <div class="uk-form-row drawer-widget entity-"
         :class="[{
            'uk-active' : isActive,
            'uk-alert-danger': drawer.errorMessage
         }, 'entity-' + entity]"
         v-if="drawer"
         ref="drawer"
         :data-accept-entity="entity"
         data-accept-limit="0"
         data-entity-types="[]">

        <nav class="drawer-widget-nav uk-navbar">
            <ul class="uk-navbar-nav">
                <li class="uk-navbar-brand"><i class=""></i></li>
            </ul>
            <div class="uk-navbar-content uk-navbar-flip">
                <div class="drawer-widget-quick-creation uk-button-group">
                    <rz-button :is-active="drawer.isActive" :callback="onExplorerButtonClick">
                        <i class="uk-icon-rz-explore"></i>
                        {{ translations.explorer }}
                    </rz-button>
                </div>
            </div>
        </nav>

        <template v-if="drawer.errorMessage">
            <p class="uk-text-danger">
                {{ drawer.errorMessage }}
            </p>
        </template>

        <div class="drawer-widget-sortable-container">

            <transition name="fade" v-if="drawer.isLoading">
                <div class="spinner"></div>
            </transition>

            <ul class="drawer-widget-sortable"
                data-input-name="">
                <draggable v-model="items" :options="{ group: '' }">
                    <transition-group>
                        <component
                            v-bind:is="drawer.currentListingView"
                            v-for="(item, index) in items"
                            :key="item.id"
                            :drawer-name="drawerName"
                            :is-item-explorer="false"
                            :add-item="addItem"
                            :remove-item="removeItem"
                            :index="index"
                            :item="item">
                        </component>
                    </transition-group>
                </draggable>
            </ul>

            <div class="hidden">
                <rz-textarea :initial-value="value" :name="name" :id="id" :cols="cols" :rows="rows"></rz-textarea>
                {{ updateList }}
            </div>
        </div>
    </div>
</template>
<script>
    import { mapState } from 'vuex'

    // Components
    import DrawerContainer from './DrawerContainer.vue'
    import RzTextarea from '../components/RzTextarea.vue'

    export default {
        props: {
            entity: {
                required: true,
                type: String
            },
            name: {
                required: true,
                type: String
            },
            id: {
                required: true,
                type: String
            },
            cols: {
                type: Number
            },
            rows: {
                type: Number
            },
            initialValue: {
                required: true,
                type: String
            }
        },
        mixins: [DrawerContainer],
        data () {
            return {
                currentView: '',
                value: ''
            }
        },
        mounted () {
            this.ids = this.initialValue.split(',')
        },
        computed: {
            ...mapState({
                translations: state => state.translations
            }),

            updateList () {
                let array = this.items.map((item) => {
                    return item.nodeName
                })

                this.value = array.join(', ')
            }
        },
        components: {
            RzTextarea
        }
    }
</script>
