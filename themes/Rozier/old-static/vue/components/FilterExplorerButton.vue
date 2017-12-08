<template>
    <a href="#"
       @click.prevent="onClick"
       class="widget-explorer-logo rz-no-ajax-link">
        <i :class="icon"></i>
    </a>
</template>
<script>
    import {
        DOCUMENT_ENTITY
    } from '../types/entityTypes'

    export default {
        props: {
            onClick: {
                required: true,
                type: Function
            },
            isFilterExplorerOpen: {
                required: true,
                type: Boolean
            },
            entity: {
                required: true
            },
            icon: {
                required: true,
                type: String
            }
        }
    }
</script>
