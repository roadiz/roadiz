<template lang="html">
    <li class="folder-item">
        <a href="#"
           class="folder-item-link"
           :class="[currentFolderId === folder.id ? 'active' : '']"
           @click.prevent="onFolderItemClick(folder)">
            <i class="uk-icon-rz-folder-tree-mini"></i>
            <span class="text">{{ folder.name }}</span>
        </a>
        <ul class="sub-folders">
            <folder-explorer-item
                v-for="(children, index) in folder.children"
                :key="children.id"
                :current-folder-id="currentFolderId"
                :on-folder-item-click="onFolderItemClick"
                :folder="children">
            </folder-explorer-item>
        </ul>
    </li>
</template>

<script>
export default {
    name: 'folder-explorer-item',
    props: ['folder', 'onFolderItemClick', 'currentFolderId'],
}
</script>
