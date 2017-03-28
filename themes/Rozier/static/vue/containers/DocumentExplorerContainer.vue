<template>
    <transition name="slide-left">
        <div class="document-widget-explorer" v-if="isOpen">

            <div class="spinner light" v-if="isLoading"></div>

            <div class="document-widget-explorer-header">
                <a href="#" class="document-widget-explorer-logo rz-no-ajax-link"><i class="uk-icon-rz-folder-tree-mini"></i></a>
                <div class="document-widget-explorer-search">
                    <form action="#" method="POST" class="explorer-search uk-form">
                        <div class="uk-form-icon">
                            <i class="uk-icon-search"></i>
                            <input id="documents-search-input"
                                   type="search"
                                   name="searchTerms"
                                   value=""
                                   v-model="searchTerms"
                                   :placeholder="searchDocumentsPlaceHolder" />
                            </div>
                        </form>
                    </div>
                <div class="document-widget-explorer-close" @click.prevent="closeExplorer"><i class="uk-icon-rz-close-explorer"></i></div>
            </div>

            <ul class="uk-sortable" v-if="!isLoading">
                <document-preview-list-item
                    :isDocumentExplorer="true"
                    v-for="document in documents"
                    :key="document.id"
                    :trans="trans"
                    :document="document">
                </document-preview-list-item>

                <transition name="fade">
                    <li class="document-widget-explorer-nextpage" v-if="filters && filters.nextPage > 1" @click.prevent="loadMoreDocuments">
                        <template v-if="!isLoadingMore">
                            <i class="uk-icon-plus"></i>
                            <span class="label">{{ trans.moreDocuments }}</span>
                        </template>
                        <template v-else>
                            <transition name="fade">
                                <div class="spinner light"></div>
                            </transition>
                        </template>
                    </li>
                </transition>

                <transition name="fade">
                    <li class="document-widget-explorer-infos" v-if="filters && filters.itemCount">
                        {{ documents.length }} / {{ filters.itemCount }}
                    </li>
                </transition>
            </ul>
        </div>
    </transition>
</template>

<script>
    import { mapState, mapActions } from 'vuex'
    import _ from 'lodash'

    // Components
    import DocumentPreviewListItem from '../components/DocumentPreviewListItem.vue'

    export default {
        data: () => {
            return {
                searchTerms: null,
                searchDocumentsPlaceHolder: temp.messages.searchDocuments
            }
        },
        computed: {
            ...mapState({
                isLoadingMore: state => state.documentExplorer.isLoadingMore,
                isLoading: state => state.documentExplorer.isLoading,
                isOpen: state => state.documentExplorer.isOpen,
                searchTerms: state => state.documentExplorer.searchTerms,
                documents: state => state.documentExplorer.documents,
                trans: state => state.documentExplorer.trans,
                filters: state => state.documentExplorer.filters
            })
        },
        methods: {
            ...mapActions({
                documentExplorerDocumentSelected: 'documentExplorerDocumentSelected',
                updateSearch: 'documentExplorerUpdateSearch',
                loadMoreDocuments: 'documentExplorerLoadMore',
                closeExplorer: 'documentExplorerClose',
                documentWidgetsAddDocument: 'documentWidgetsAddDocument',
            }),
            /**
             * When a click occur to add a document to a document widget.
             *
             * @param document
             */
            addDocument: function (document) {
                this.documentWidgetsAddDocument({
                    documentWidget: null,
                    document
                })
            }
        },
        watch: {
            searchTerms:_.debounce(function (newValue, oldValue) {
                this.updateSearch(newValue, oldValue)
            }, 350)
        },
        components: {
            DocumentPreviewListItem
        }
    }
</script>
