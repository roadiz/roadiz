<template>
    <transition name="fade">
        <li class="widget-explorer-nextpage" v-if="nextPage && nextPage > 1" @click.prevent="loadMoreItems">
            <template v-if="!isLoadingMore">
                <i class="uk-icon-plus"></i>
                <span class="label">{{ moreItemsText }}</span>
            </template>
            <template v-else>
                <transition name="fade">
                    <div class="spinner light"></div>
                </transition>
            </template>
        </li>
    </transition>
</template>
<script>
    export default {
        props: {
            isLoadingMore: {
                required: true,
                type: Boolean
            },
            nextPage: {
                type: Number
            },
            loadMoreItems: {
                required: true,
                type: Function
            },
            moreItemsText: {
                required: true,
                type: String
            }
        }
    }
</script>
