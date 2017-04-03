<template lang="html">
    <li class="item">
        <a href="#"
           class="filter-item-link"
           :class="[isCurrentItem ? 'active' : '']"
           @click.prevent="onItemClick(item)">
            <i class="icon" :class="icon"></i>
            <span class="text">{{ item.name }}</span>
        </a>
        <ul class="sub-filters">
            <filter-explorer-item
                v-for="(children, index) in item.children"
                :key="children.id"
                :current-item="currentItem"
                :entity="entity"
                :on-item-click="onItemClick"
                :item="children">
            </filter-explorer-item>
        </ul>
    </li>
</template>

<script>
    import {
        DOCUMENT_ENTITY
    } from '../types/entityTypes'

    export default {
        name: 'filter-explorer-item',
        props: {
            item: {
                required: true,
                type: Object
            },
            onItemClick: {
                required: true,
                type: Function,
            },
            currentItem: {
                required: true
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
        computed: {
            isCurrentItem: function () {
                if (!this.currentItem || !this.currentItem.id) {
                    return false
                }

                return this.currentItem.id === this.item.id
            }
        },
        methods: {
            setIcon: function () {
                switch (this.entity) {
                    case DOCUMENT_ENTITY:
                        this.icon = this.isCurrentItem ? 'uk-icon-folder-open' : 'uk-icon-folder'
                        break;
                    default:
                        this.icon = this.isCurrentItem ? 'uk-icon-circle' : 'uk-icon-circle-o'
                }
            }
        },
        watch: {
            currentItem: function () {
                this.setIcon()
            }
        }
    }
</script>
