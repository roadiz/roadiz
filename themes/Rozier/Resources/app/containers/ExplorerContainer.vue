<template>
    <transition name="slide-left">
        <div class="widget-explorer"
             :class="[{
                'filter-explorer-open': isFilterExplorerOpen,
                'explorer-open': isOpen
             }, entityClass]"
             v-if="isOpen">
            <div class="widget-explorer-wrapper">
                <div class="widget-explorer-header">

                    <filter-explorer-button
                        v-if="isFilterEnable"
                        :entity="entity"
                        :icon="filterExplorerIcon"
                        :filter-explorer-selected-items="filterExplorerSelectedItems"
                        :is-filter-explorer-open="isFilterExplorerOpen"
                        :on-click="filterExplorerToggle">
                    </filter-explorer-button>

                    <div class="widget-explorer-search">
                        <form action="#" method="POST" class="explorer-search uk-form" v-on:submit.prevent>
                            <div class="uk-form-icon">
                                <i class="uk-icon-search"></i>
                                <input id="search-input"
                                       type="search"
                                       name="searchTerms"
                                       v-model="searchTerms"
                                       autocomplete="off"
                                       @keyup.enter.stop.prevent="manualUpdate"
                                       :placeholder="searchPlaceHolder" />
                            </div>
                        </form>
                    </div>
                    <div class="widget-explorer-close" @click.prevent="explorerClose">
                        <i class="uk-icon-rz-close-explorer"></i>
                    </div>
                </div>

                <div class="spinner light" v-if="isLoading"></div>

                <transition name="fade">
                    <ul class="uk-sortable" v-if="!isLoading">
                        <draggable v-model="items" :options="{ group: { name: entity, put: false } }">
                            <transition-group class="sortable-inner">
                                <component
                                    v-bind:is="currentListingView"
                                    v-for="(item, index) in items"
                                    :key="item.id"
                                    :is-item-explorer="true"
                                    :add-item="addItem"
                                    :index="index"
                                    :item="item">
                                </component>
                            </transition-group>
                        </draggable>
                    </ul>
                </transition>

                <load-more-button
                    v-if="filters"
                    :next-page="filters.nextPage"
                    :load-more-items="explorerLoadMore"
                    :is-loading-more="isLoadingMore"
                    :more-items-text="moreItems ? translations[moreItems] : ''">
                </load-more-button>

                <explorer-items-infos
                    v-if="filters"
                    :length="items.length"
                    :item-count="filters.itemCount">
                </explorer-items-infos>

                <component :is="widgetView"></component>
            </div>
        </div>
    </transition>
</template>

<script>
    import { mapState, mapActions } from 'vuex'
    import { debounce } from 'lodash'

    // Components
    import LoadMoreButton from '../components/LoadMoreButton.vue'
    import ExplorerItemsInfos from '../components/ExplorerItemsInfos.vue'
    import FilterExplorerButton from '../components/FilterExplorerButton.vue'
    import draggable from 'vuedraggable'

    export default {
        data: () => {
            return {
                searchPlaceHolder: ''
            }
        },
        computed: {
            ...mapState({
                isLoadingMore: state => state.explorer.isLoadingMore,
                isLoading: state => state.explorer.isLoading,
                isOpen: state => state.explorer.isOpen,
                items: state => state.explorer.items,
                filters: state => state.explorer.filters,
                moreItems: state => state.explorer.trans.moreItems,
                translations: state => state.translations,
                entity: state => state.explorer.entity,
                isFilterExplorerOpen: state => state.filterExplorer.isOpen,
                filterExplorerSelectedItems: state => state.filterExplorer.selectedItem,
                currentListingView: state => state.explorer.currentListingView,
                widgetView: state => state.explorer.widgetView,
                isFilterEnable: state => state.explorer.isFilterEnable,
                filterExplorerIcon: state => state.explorer.filterExplorerIcon,
                entityClass: state => 'entity-' + state.explorer.entity
            }),
            searchTerms: {
                get () {
                    return this.$store.getters.getExplorerSearchTerms
                },
                set: debounce(function (searchTerms) {
                    if (this.isOpen) {
                        this.$store.dispatch('explorerUpdateSearch', { searchTerms })
                    }
                }, 450)
            }
        },
        methods: {
            ...mapActions([
                'filterExplorerToggle',
                'explorerClose',
                'explorerUpdateSearch',
                'explorerLoadMore',
                'drawersAddItem'
            ]),
            manualUpdate () {
                this.explorerUpdateSearch({ searchTerms: this.searchTerms })
            },
            addItem (item) {
                this.drawersAddItem({ item })
            }
        },
        components: {
            LoadMoreButton,
            ExplorerItemsInfos,
            FilterExplorerButton,
            draggable
        }
    }
</script>
