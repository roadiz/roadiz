<template lang="html">
    <transition name="slide-left-no-opacity">
        <div class="folder-widget-explorer" v-if="isOpen">
            <ul class="folders">
                <li class="folder-infos">
                    <div class="infos-content">
                        <span class="number">{{ itemCount }}</span>
                        {{ itemCount > 1 ? translations.documents : translations.document }}
                    </div>
                </li>
                <li class="folder-item">
                    <a href="#"
                       class="folder-item-link"
                       :class="[ currentFolder ? '' : 'active' ]"
                       @click.prevent="onResetClick">
                        <i class="uk-icon-rz-unordered-list"></i> {{ translations.see_all }}
                    </a>
                </li>
                <folder-explorer-item
                    v-for="(folder, index) in folders"
                    :current-folder="currentFolder"
                    :key="index"
                    :on-folder-item-click="onFolderItemClick"
                    :folder="folder">
                </folder-explorer-item>
            </ul>
        </div>
    </transition>
</template>

<script>
    import { mapState, mapActions } from 'vuex'
    import FolderExplorerItem from '../components/FolderExplorerItem.vue'

    export default {
        computed: {
            ...mapState({
                isLoading: state => state.folderExplorer.isLoading,
                isOpen: state => state.folderExplorer.isOpen,
                folders: state => state.folderExplorer.folders,
                itemCount: state => state.documentExplorer.filters.itemCount,
                currentFolder: state => state.documentExplorer.currentFolder,
                translations: state => state.translations
            })
        },
        methods: {
            ...mapActions([
                'documentExplorerUpdateSearch'
            ]),
            onFolderItemClick: function (folder) {
                this.documentExplorerUpdateSearch({
                    folder: folder
                })
            },
            onResetClick: function () {
                this.documentExplorerUpdateSearch({
                    folder: null
                })
            }
        },
        components: {
            FolderExplorerItem
        }
    }
</script>
