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
                                       value=""
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
                        <draggable v-model="items" :options="{ group: entity }">
                            <transition-group style="display:block; min-height: 80px;">
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

                        <load-more-button
                            v-if="filters"
                            :next-page="filters.nextPage"
                            :load-more-items="explorerLoadMore"
                            :is-loading-more="isLoadingMore"
                            :more-items-text="moreItems ? translations[moreItems] : ''">
                        </load-more-button>

                        <explorer-items-infos
                            v-if="filters && filters.itemCount"
                            :length="items.length"
                            :item-count="filters.itemCount">
                        </explorer-items-infos>
                    </ul>
                </transition>
            </div>
        </div>
    </transition>
</template>

<script>
    import { mapState, mapActions, mapGetters } from 'vuex'
    import _ from 'lodash'
    import {
        DOCUMENT_ENTITY
    } from '../types/entityTypes'

    // Components
    import LoadMoreButton from '../components/LoadMoreButton.vue'
    import ExplorerItemsInfos from '../components/ExplorerItemsInfos.vue'
    import FilterExplorerButton from '../components/FilterExplorerButton.vue'
    import draggable from 'vuedraggable'

    export default {
        data: () => {
            return {
                searchTerms: '',
                searchPlaceHolder: ''
            }
        },
        computed: {
            ...mapState({
                isLoadingMore: state => state.explorer.isLoadingMore,
                isLoading: state => state.explorer.isLoading,
                isOpen: state => state.explorer.isOpen,
                searchTerms: state => state.explorer.searchTerms,
                items: state => state.explorer.items,
                filters: state => state.explorer.filters,
                moreItems: state => state.explorer.trans.moreItems,
                translations: state => state.translations,
                entity: state => state.explorer.entity,
                isFilterExplorerOpen: state => state.filterExplorer.isOpen,
                currentListingView: state => state.explorer.currentListingView,
                isFilterEnable: state => state.explorer.isFilterEnable,
                filterExplorerIcon: state => state.explorer.filterExplorerIcon,
                entityClass: state => 'entity-' + state.explorer.entity
            })
        },
        methods: {
            ...mapActions([
                'filterExplorerToggle',
                'explorerClose',
                'explorerUpdateSearch',
                'explorerLoadMore',
                'drawersAddItem',
            ]),
            manualUpdate: function () {
                this.explorerUpdateSearch({ searchTerms: this.searchTerms })
            },
            addItem: function (item) {
                this.drawersAddItem({ item })
            }
        },
        watch: {
            searchTerms:_.debounce(function (newValue) {
                 this.explorerUpdateSearch({ searchTerms: newValue })
            }, 350)
        },
        components: {
            LoadMoreButton,
            ExplorerItemsInfos,
            FilterExplorerButton,
            draggable,
        }
    }
</script>
