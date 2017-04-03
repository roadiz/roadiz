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
            }
        },
        data: () => {
            return {
                icon: ''
            }
        },
        mounted: function () {
            this.setIcon()
        },
        methods: {
            setIcon: function () {
                switch (this.entity) {
                    case DOCUMENT_ENTITY:
                        this.icon = 'uk-icon-rz-folder-tree-mini'
                        break;
                    default:
                        this.icon = 'uk-icon-cog'
                }
            }
        },
        watch: {
            isFilterExplorerOpen: function () {
                this.setIcon()
            }
        }
    }
</script>
