<template>
    <drawer-item
        :item="item"
        :index="index"
        :drawer-name="drawerName"
        :is-item-explorer="isItemExplorer"
        :parent-name="item.parent ? item.parent : ''"
        :name="item.name ? item.name : ''"
        :edit-item="item.editUrl"
        :add-item="addItem"
        :remove-item="removeItem">
    </drawer-item>
</template>
<script>
    import DrawerItem from './DrawerItem.vue'

    export default {
        props: [
            'item',
            'isItemExplorer',
            'drawerName',
            'index',
            'removeItem',
            'addItem'
        ],
        components: {
            DrawerItem
        }
    }
</script>
