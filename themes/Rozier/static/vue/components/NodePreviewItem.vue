<template>
    <transition name="fade">
        <li
            class="uk-sortable-list-item nodes-widget-sortable-list-item"
            v-if="node"
            @click.prevent="onAddItemButtonClick"
            :class="node.isPusblished ? '' : 'not-published'">

            <div class="uk-sortable-handle"></div>
            <div class="node-border" :style="{ backgroundColor: node.nodeType.color }"></div>
            <template v-if="node.parent">
                <p class="parent-node-name">
                    <template v-if="node.subparent">
                        <span class="sub">
                            {{ node.subparent.title }}
                        </span>
                    </template>
                    <span class="direct">{{ node.parent.title }}</span>
                </p>
            </template>
            <span class="node-name">{{ node.title ? node.title : node.nodeName }}</span>
            <input type="hidden" :name="drawerName + '[' + index +']'" :value="node.id" />
            <div class="node-links">
                <ajax-link :href="node.nodesEditPage" class="uk-button node-link uk-button-mini">
                    <i class="uk-icon-rz-pencil"></i>
                </ajax-link><a href="#"
                   class="uk-button uk-button-mini node-link uk-button-danger rz-no-ajax-link"
                   @click.prevent="onRemoveItemButtonClick()">
                    <i class="uk-icon-rz-minus"></i>
                </a>
            </div>
            <a href="#" class="uk-button uk-button-mini link-button">
                <div class="link-button-inner">
                    <i class="uk-icon-rz-plus"></i>
                </div>
            </a>
        </li>
    </transition>
</template>
<script>
    import AjaxLink from '../components/AjaxLink.vue'

    export default {
        props: ['item', 'trans', 'isItemExplorer', 'drawerName', 'index', 'removeItem'],
        data: function () {
            return {
                node: this.item,
                document: this.item
            }
        },
        methods: {
            onAddItemButtonClick: function () {
                // If document is in the explorer panel
                if (this.isItemExplorer) {
                    this.$parent.addItem(this.item)
                }
            },
            onRemoveItemButtonClick: function () {
                // Call parent function to remove the document from widget
                this.removeItem(this.item)
            }
        },
        components: {
            AjaxLink
        }
    }
</script>
