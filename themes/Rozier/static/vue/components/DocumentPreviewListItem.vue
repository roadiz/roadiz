<template>
    <li class="image-document uk-sortable-list-item documents-widget-sortable-list-item">

        <div class="uk-sortable-handle"></div>
        <div class="document-border"></div>

        <template v-if="document.isSvg">
            <object class="document-image" type="image/svg+xml" width="80" height="80" :data="document.url"></object>
        </template>
        <template v-else-if="document.isImage && !document.isPrivate">
            <img class="document-image" width="80" height="80" :src="document.thumbnail_80" />
        </template>
        <template v-else-if="document.isPrivate">
            <div class="document-platform-icon"><i class="uk-icon-lock"></i></div>
        </template>
        <template v-else>
            <div class="document-platform-icon"><i :class="'uk-icon-file-' + document.shortType +'-o'"></i></div>
        </template>
        <input type="hidden" :value="document.id" />
        <div class="document-overflow">
            <div class="document-links">
                <a :href="document.editUrl" class="uk-button document-link uk-button-mini">
                    <i class="uk-icon-rz-pencil"></i>
                    <span class="label">{{ trans.editDocument }}</span>
                </a>
                <a data-document-widget-unlink-document href="#" class="uk-button uk-button-mini document-link uk-button-danger rz-no-ajax-link">
                    <i class="uk-icon-rz-minus"></i>
                    <span class="label">{{ trans.unlinkDocument }}</span>
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
                    <span class="label">{{ trans.linkDocument }}</span>
                </div>
            </a>
        </div>

        <div class="document-name">{{ document.filename | centralTruncate(12, -4) }}</div>
    </li>
</template>

<script>
    import { mapActions } from 'vuex'

    // Filters
    import filters from '../filters'

    export default {
        name: 'document-explorer-button',
        props: ['document', 'trans'],
        filters: filters
    }
</script>
