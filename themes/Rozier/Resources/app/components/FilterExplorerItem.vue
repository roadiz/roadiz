<template lang="html">
    <transition name="fade-in">
        <li class="item" v-if="item">
            <a href="#"
               class="filter-item-link"
               :class="[isCurrentItem ? 'active' : '']"
               @click.prevent="onItemClick(item)">
                <i class="icon" :class="isCurrentItem ? icons.active : icons.normal"></i>
                <span class="text">{{ item.name }}</span>
            </a>
            <ul class="sub-filters">
                <filter-explorer-item
                    v-for="(children, index) in item.children"
                    :key="children.id"
                    :icons="icons"
                    :current-item="currentItem"
                    :entity="entity"
                    :on-item-click="onItemClick"
                    :item="children">
                </filter-explorer-item>
            </ul>
        </li>
    </transition>
</template>

<script>
    export default {
        name: 'filter-explorer-item',
        props: {
            item: {
                required: true,
                type: Object
            },
            onItemClick: {
                required: true,
                type: Function
            },
            currentItem: {
                required: true
            },
            entity: {
                required: true
            },
            icons: {
                required: true,
                type: Object
            }
        },
        computed: {
            isCurrentItem: function () {
                if (!this.currentItem || !this.currentItem.id) {
                    return false
                }

                return this.currentItem.id === this.item.id
            }
        }
    }
</script>
