<template>
    <transition name="slide-left">
        <div class="widget-explorer"
             :class="{ 'filter-explorer-open': isFilterExplorerOpen, 'explorer-open': isOpen }"
             v-if="isOpen">
            <div class="widget-explorer-wrapper">
                <div class="widget-explorer-header">

                    <filter-explorer-button
                        v-if="isFilterEnable"
                        :entity="entity"
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
                        <component
                            v-bind:is="currentListingView"
                            v-for="(item, index) in items"
                            :key="item.id"
                            :trans="trans"
                            :is-item-explorer="true"
                            :add-item="addItem"
                            :index="index"
                            :item="item">
                        </component>

                        <load-more-button
                            :next-page="filters.nextPage"
                            :load-more-items="explorerLoadMore"
                            :is-loading-more="isLoadingMore"
                            :more-items-text="trans.moreItems">
                        </load-more-button>

                        <explorer-items-infos
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
    import { mapState, mapActions } from 'vuex'
    import _ from 'lodash'
    import {
        DOCUMENT_ENTITY
    } from '../types/entityTypes'

    // Components
    import LoadMoreButton from '../components/LoadMoreButton.vue'
    import ExplorerItemsInfos from '../components/ExplorerItemsInfos.vue'
    import FilterExplorerButton from '../components/FilterExplorerButton.vue'
    import DocumentPreviewListItem from '../components/DocumentPreviewListItem.vue'

    export default {
        data: () => {
            return {
                searchTerms: '',
                searchPlaceHolder: '',
                moreItems: 'More items',
                currentListingView: null,
                isFilterEnable: false
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
                trans: state => state.explorer.trans,
                entity: state => state.explorer.entity,
                isFilterExplorerOpen: state => state.filterExplorer.isOpen
            })
        },
        methods: {
            ...mapActions([
                'filterExplorerToggle',
                'explorerClose',
                'explorerUpdateSearch',
                'explorerLoadMore',
                'drawersAddItem'
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
            }, 350),
            entity: function () {
                switch (this.entity) {
                    case DOCUMENT_ENTITY:
                        this.currentListingView = DocumentPreviewListItem
                        this.isFilterEnable = true
                        break;
                    default:
                        this.currentListingView = null
                }
            }
        },
        components: {
            DocumentPreviewListItem,
            LoadMoreButton,
            ExplorerItemsInfos,
            FilterExplorerButton
        }
    }
</script>
