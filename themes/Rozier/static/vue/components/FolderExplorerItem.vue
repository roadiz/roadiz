<template lang="html">
    <li class="folder-item">
        <a href="#"
           class="folder-item-link"
           :class="[isCurrentFolder ? 'active' : '']"
           @click.prevent="onFolderItemClick(folder)">
            <i class="icon" :class="[isCurrentFolder ? 'uk-icon-folder-open' : 'uk-icon-folder']"></i>
            <span class="text">{{ folder.name }}</span>
        </a>
        <ul class="sub-folders">
            <folder-explorer-item
                v-for="(children, index) in folder.children"
                :key="children.id"
                :current-folder="currentFolder"
                :on-folder-item-click="onFolderItemClick"
                :folder="children">
            </folder-explorer-item>
        </ul>
    </li>
</template>

<script>
export default {
    name: 'folder-explorer-item',
    props: ['folder', 'onFolderItemClick', 'currentFolder'],
    computed: {
        isCurrentFolder: function () {
            if (!this.currentFolder) {
                return false
            }

            return this.currentFolder.id === this.folder.id
        }
    }
}
</script>
