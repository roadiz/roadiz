<template>
    <transition name="fade">
        <li class="image-document uk-sortable-list-item documents-widget-sortable-list-item"
            v-if="document"
            data-uk-tooltip="{animation:true, pos:'bottom'}"
            :title="document.filename"
            @mouseover="onMouseover"
            @mouseleave="onMouseleave">

            <div class="preview-zoom" @click.prevent="onPreviewClick">
                <i class="uk-icon-search-plus"></i>
            </div>

            <div @click.prevent="onAddItemButtonClick">

                <div class="uk-sortable-handle"></div>
                <div class="document-border"></div>

                <div class="document-overflow">
                    <template v-if="document.isSvg">
                        <div v-html="document.preview_html" class="svg"></div>
                    </template>
                    <template v-else-if="document.isImage && !document.isWebp && !document.isPrivate">
                        <picture>
                            <source :srcset="document.thumbnail_80 + '.webp'" type="image/webp">
                            <img class="document-image"
                                 width="80"
                                 height="80"
                                 loading="lazy"
                                 :src="document.thumbnail_80" />
                        </picture>
                    </template>
                    <template v-else-if="document.isImage && !document.isPrivate">
                        <img class="document-image" width="80" height="80"
                             src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="
                             v-dynamic-img="document.thumbnail_80" />
                    </template>
                    <template v-else-if="document.isPrivate">
                        <div class="document-platform-icon"><i class="uk-icon-lock"></i></div>
                    </template>
                    <template v-else>
                        <img v-if="document.hasThumbnail"
                             class="document-image"
                             width="80" height="80"
                             src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="
                             v-dynamic-img="document.thumbnail_80" />
                        <div class="document-platform-icon"><i :class="'uk-icon-file-' + document.shortType +'-o'"></i></div>
                    </template>
                    <template v-if="drawerName">
                        <input type="hidden" :name="drawerName + '[' + index +']'" :value="document.id" />
                    </template>

                    <div class="document-links">
                        <ajax-link
                            :href="document.editUrl + getReferer()"
                            class="uk-button document-link uk-button-mini">
                            <i class="uk-icon-rz-pencil"></i>
                        </ajax-link><a
                            href="#"
                            @click.prevent="onRemoveItemButtonClick()"
                            class="uk-button uk-button-mini document-link uk-button-danger rz-no-ajax-link">
                            <i class="uk-icon-rz-trash-o"></i>
                        </a>
                    </div>
                    <template v-if="document.isEmbed">
                        <div class="document-mime-type">{{ document.embedPlatform }}</div>
                        <div class="document-platform-icon"><i :class="'uk-icon-rz-' + document.embedPlatform"></i></div>
                    </template>
                    <template v-else>
                        <div class="document-mime-type">{{ document.shortMimeType | truncate(13, false, '…') }}</div>
                    </template>

                    <a data-document-widget-link-document href="#" class="uk-button uk-button-mini link-button">
                        <div class="link-button-inner">
                            <i class="uk-icon-rz-plus"></i>
                        </div>
                    </a>
                </div>
                <div class="document-name">{{ document.filename | centralTruncate(12, -4) }}</div>
            </div>
        </li>
    </transition>
</template>

<script>
    import { mapActions } from 'vuex'

    // Filters
    import filters from '../filters'

    // Components
    import AjaxLink from '../components/AjaxLink.vue'

    // Directives
    import DynamicImg from '../directives/DynamicImg'

    export default {
        props: ['item', 'isItemExplorer', 'drawerName', 'index', 'removeItem', 'addItem'],
        data: function () {
            return {
                document: this.item
            }
        },
        directives: {
            DynamicImg
        },
        filters: filters,
        methods: {
            ...mapActions([
                'documentPreviewInit',
                'documentPreviewOpen',
                'documentPreviewDestroy'
            ]),
            onAddItemButtonClick () {
                // If document is in the explorer panel
                if (this.isItemExplorer) {
                    this.addItem(this.item)
                }
            },
            onRemoveItemButtonClick () {
                // Call parent function to remove the document from widget
                this.removeItem(this.item)
            },
            getReferer () {
                return '?referer=' + window.location.pathname
            },
            onPreviewClick () {
                this.documentPreviewOpen()
            },
            onMouseover () {
                this.documentPreviewInit({ document: this.item })
            },
            onMouseleave () {
                this.documentPreviewDestroy({ document: this.item })
            }
        },
        components: {
            AjaxLink
        }
    }
</script>
