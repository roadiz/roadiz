<template>
    <transition name="fade">
        <div class="tag-creator" v-if="!isTagExisting && searchTerms">
            <div class="infos">{{ translations.createTag }}</div>

            <tag-creator-item
                :create-tag="tagsCreate"
                :tag-name="searchTerms">
            </tag-creator-item>
        </div>
    </transition>
</template>
<script>
    import { mapState, mapActions } from 'vuex'

    // Components
    import TagCreatorItem from '../components/TagCreatorItem.vue'

    export default {
        mounted () {
            this.tagCreatorReady()
        },
        computed: {
            ...mapState({
                translations: state => state.translations,
                searchTerms: state => state.explorer.searchTerms,
                isTagExisting: state => state.tags.isTagExisting
            })
        },
        methods: {
            ...mapActions([
                'tagsCreate',
                'tagCreatorReady'
            ])
        },
        components: {
            TagCreatorItem
        }
    }
</script>
<style lang="scss" scoped>
    $bgColor: darken(#3d3d3d, 10);
    $textColor: lighten(#3d3d3d, 10);
    $btnSize: 40px;

    .tag-creator {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        padding: 20px 40px;
        background-color: $bgColor;
        color: $textColor;
        box-sizing: border-box;
        display: flex;

        .new-tag-container,
        .infos {
            flex: 1;
        }

        .infos {
            font-size: 12px;
            margin-right: 20px;
        }
    }
</style>
