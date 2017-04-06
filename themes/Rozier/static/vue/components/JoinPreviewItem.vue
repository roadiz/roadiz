<template>
    <transition name="fade">
        <li
            class="uk-sortable-list-item joins-widget-sortable-list-item"
            v-if="join"
            @click.prevent="onAddItemButtonClick">
            <div class="uk-sortable-handle"></div>
            <div class="join-border"></div>
            <template v-if="join.classname">
                <p class="parent-join-name">
                    <span class="direct">{{ join.classname }}</span>
                </p>
            </template>
            <span class="join-name">{{ join.displayable }}</span>
            <input type="hidden" :name="drawerName + '[' + index +']'" :value="join.id" />
            <div class="join-links">
                <a href="#"
                   class="uk-button uk-button-mini join-link uk-button-danger rz-no-ajax-link"
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
                join: this.item
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
