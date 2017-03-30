<template lang="html">
    <transition name="slide-left-no-opacity">
        <div class="folder-widget-explorer" v-if="isOpen">
            <ul class="folders">
                <li class="folder-close">
                    <a href="#" class="folder-item-link" data-folder-id="0" @click.prevent="onResetClick">
                        <i class="uk-icon-rz-reset"></i>
                    </a>
                </li>
                <folder-explorer-item
                    v-for="(folder, index) in folders"
                    :current-folder-id="currentFolderId"
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
                currentFolderId: state => state.documentExplorer.currentFolderId
            })
        },
        methods: {
            ...mapActions([
                'documentExplorerUpdateSearch'
            ]),
            onFolderItemClick: function (folder) {
                this.documentExplorerUpdateSearch({
                    folderId: folder.id
                })
            },
            onResetClick: function () {
                this.documentExplorerUpdateSearch({
                    folderId: null
                })
            }
        },
        components: {
            FolderExplorerItem
        }
    }
</script>
