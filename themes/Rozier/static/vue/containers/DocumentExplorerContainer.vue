<template>
    <transition name="slide-left">
        <div class="document-widget-explorer" v-if="isOpen">
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
            <ul class="uk-sortable">
                <document-preview-list-item
                    v-for="document in documents"
                    :key="document.id"
                    :trans="trans"
                    :document="document">
                </document-preview-list-item>
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
            ...mapState('documentExplorer', ['searchTerms', 'isOpen', 'documents', 'trans']),
        },
        methods: {
            ...mapActions('documentExplorer', ['updateSearch', 'closeExplorer'])
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
