<template>
    <drawer-item
        :item="item"
        :index="index"
        :drawer-name="drawerName"
        :is-item-explorer="isItemExplorer"
        :parent-name="item.nodeName ? item.nodeName : ''"
        :name="item.name ? item.name : ''"
        :edit-item="item.nodesEditPage"
        :add-item="addItem"
        :remove-item="removeItem">
    </drawer-item>
</template>
<script>
    import DrawerItem from './DrawerItem.vue'

    export default {
        props: [
            'item',
            'trans',
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
