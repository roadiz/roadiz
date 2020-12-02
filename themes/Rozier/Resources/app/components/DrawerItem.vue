<template>
    <transition name="fade">
        <li
            class="uk-sortable-list-item drawer-item type-label"
            v-if="item"
            @click.prevent="onAddItemButtonClick"
            :class="{ 'has-thumbnail': item.thumbnail, 'not-published': item.isPublished === false  }">

            <div class="uk-sortable-handle"></div>
            <div class="border" :style="{ backgroundColor: getColor() }"></div>
            <figure class="thumbnail"
                    v-if="getThumbnail() && !item.thumbnail.processable"
                    :style="{ 'background-image': 'url(' + getThumbnail() + ')' }"></figure>
            <figure class="thumbnail"
                    v-else-if="getThumbnail() && item.thumbnail.processable">
                <picture>
                    <source :srcset="getThumbnail() + '.webp'" type="image/webp" />
                    <img :src="getThumbnail()" :alt="name">
                </picture>
            </figure>
            <div class="names">
                <p class="parent-name">
                    <template v-if="parentName">
                        <template v-if="subParentName">
                        <span class="sub">
                            {{ subParentName }}
                        </span>
                        </template>
                        <span class="direct">{{ parentName }}</span>
                    </template>
                </p>
                <span class="name">{{ name }}</span>
                <input type="hidden" :name="drawerName + '[' + index +']'" :value="item.id" />
                <div class="links" :class="getEditItem() ? '' : 'no-edit'">
                    <ajax-link :href="getEditItem() + getReferer()" class="uk-button link uk-button-mini" v-if="getEditItem()">
                        <i class="uk-icon-rz-pencil"></i>
                    </ajax-link><a href="#"
                                   class="uk-button uk-button-mini link uk-button-danger rz-no-ajax-link"
                                   @click.prevent="onRemoveItemButtonClick()">
                    <i class="uk-icon-rz-trash-o"></i>
                </a>
                </div>
                <a href="#" class="uk-button uk-button-mini link-button">
                    <div class="link-button-inner">
                        <i class="uk-icon-rz-plus"></i>
                    </div>
                </a>
            </div>
        </li>
    </transition>
</template>
<script>
    import AjaxLink from './AjaxLink.vue'

    export default {
        props: {
            item: {
                type: Object
            },
            editItem: {
                type: String
            },
            isItemExplorer: {
                type: Boolean
            },
            drawerName: {
                type: String
            },
            index: {
                type: Number
            },
            removeItem: {
                type: Function
            },
            addItem: {
                type: Function
            },
            parentName: {
                type: String
            },
            subParentName: {
                type: String
            },
            name: {
                type: String
            }
        },
        methods: {
            onAddItemButtonClick: function () {
                // If document is in the explorer panel
                if (this.isItemExplorer) {
                    this.addItem(this.item)
                }
            },
            onRemoveItemButtonClick: function () {
                // Call parent function to remove the document from widget
                this.removeItem(this.item)
            },
            getColor: function () {
                if (this.item.nodeType && this.item.nodeType.color) {
                    return this.item.nodeType.color
                } else if (this.item.color) {
                    return this.item.color
                }
                return null
            },
            getEditItem () {
                if (this.editItem) {
                    return this.editItem
                } else if (this.item.editItem) {
                    return this.item.editItem
                }

                return null
            },
            getReferer: function () {
                return '?referer=' + window.location.pathname
            },
            getThumbnail: function () {
                if (this.item.thumbnail && this.item.thumbnail.url) {
                    return this.item.thumbnail.url
                } else if (this.item.thumbnail) {
                    return this.item.thumbnail
                }
                return null
            }
        },
        components: {
            AjaxLink
        }
    }
</script>
