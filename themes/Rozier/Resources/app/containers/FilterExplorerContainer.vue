<template lang="html">
    <transition name="slide-left-no-opacity">
        <div class="filter-widget-explorer" v-if="isOpen">
            <ul class="filters-items">
                <li class="filters-infos">
                    <div class="infos-content">
                        <transition name="fade">
                            <div v-if="itemCount || itemCount === 0">
                                <span class="number">{{ itemCount }}</span>
                                {{ itemCount > 1 ? translations.items : translations.item }}
                            </div>
                        </transition>
                    </div>
                </li>
                <li class="item">
                    <a href="#"
                       class="filter-item-link"
                       :class="[ selectedItem ? '' : 'active' ]"
                       @click.prevent="onResetClick">
                        <i class="uk-icon-rz-unordered-list"></i> {{ translations.see_all }}
                    </a>
                </li>

                <filter-explorer-item
                    v-for="(item, index) in items"
                    :current-item="selectedItem"
                    :icons="icons"
                    :key="index"
                    :entity="entity"
                    :on-item-click="onItemClick"
                    :item="item">
                </filter-explorer-item>
            </ul>
        </div>
    </transition>
</template>

<script>
    import { mapState, mapActions } from 'vuex'

    // Components
    import FilterExplorerItem from '../components/FilterExplorerItem.vue'

    export default {
        data: () => {
            return {
                currentListingView: null
            }
        },
        computed: {
            ...mapState({
                isLoading: state => state.filterExplorer.isLoading,
                isOpen: state => state.filterExplorer.isOpen,
                items: state => state.filterExplorer.items,
                entity: state => state.explorer.entity,
                itemCount: state => state.explorer.filters.itemCount,
                selectedItem: state => state.filterExplorer.selectedItem,
                translations: state => state.translations,
                icons: state => state.filterExplorer.icons
            })
        },
        methods: {
            ...mapActions([
                'filterExplorerUpdate'
            ]),
            onItemClick: function (item) {
                this.filterExplorerUpdate({ item: item })
            },
            onResetClick: function () {
                this.filterExplorerUpdate({ item: null })
            }
        },
        components: {
            FilterExplorerItem
        }
    }
</script>
