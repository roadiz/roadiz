<template>
    <div>
        <slot></slot>
        <label class="typo__label">Tagging</label>
        <multiselect
            v-model="value"
            tag-placeholder="Add this as new tag"
            placeholder="Search or add a tag"
            label="name"
            track-by="id"
            :options="options"
            :multiple="true"
            :taggable="true"
            group-values="children"
            group-label="name"
            @tag="addTag"
            @input="tagsUpdateValue">

            <!--<template slot="option" scope="props">-->
                <!--<div class="option__desc">-->
                    <!--<span class="option__name">{{ props.option.name }}</span>-->
                    <!--<span class="option__children">-->
                        <!--{{ props.option.children }}-->

                    <!--</span>-->
                <!--</div>-->
            <!--</template>-->

        </multiselect>
        <pre class="language-json"><code>{{ value  }}</code></pre>
    </div>
</template>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<script>
    import { mapState, mapActions } from 'vuex'

    // Components
    import Multiselect from 'vue-multiselect'

    export default {
        mounted () {
            this.tagsInitData()
        },
        computed: {
            ...mapState({
                value: state => state.tags.value,
                options: state => state.tags.options
            })
        },
        methods: {
            ...mapActions([
                'tagsUpdateValue',
                'tagsInitData'
            ]),
            addTag (newTag) {
                const tag = {
                    name: newTag
                }

                this.options.push(tag)
                this.value.push(tag)

                this.tagsUpdateValue(this.value)
            }
        },
        components: {
            Multiselect
        }
    }
</script>
