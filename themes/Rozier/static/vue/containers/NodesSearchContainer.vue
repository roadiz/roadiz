<!-- Inline template for this component -->
<!-- Template: widgets/nodesSourcesSearch.html.twig -->
<script>
    import { mapState, mapActions } from 'vuex'
    import _ from 'lodash'

    // Components
    import AjaxLink from '../components/AjaxLink.vue'

    export default {
        data: function () {
            return {
                searchTerms: null,
            }
        },
        computed: {
            ...mapState({
                items: state => state.nodesSourceSearch.items,
                isFocus: state => state.nodesSourceSearch.isFocus,
                isOpen: state => state.nodesSourceSearch.isOpen,
            })
        },
        methods: {
            ...mapActions([
                'nodesSourceSearchUpdate',
                'nodeSourceSearchEnableFocus',
                'nodeSourceSearchDisableFocus'
            ]),
            enableFocus: _.debounce(function () {
                this.nodeSourceSearchEnableFocus()
            }, 50),
            disableFocus: _.debounce(function () {
                this.nodeSourceSearchDisableFocus()
            }, 50)
        },
        watch: {
            searchTerms: _.debounce(function (newValue, oldValue) {
                this.nodesSourceSearchUpdate(newValue, oldValue)
            }, 350),
            isFocus: function () {
                if (!this.isFocus) {
                    this.$refs.searchTermsInput.blur()
                }
            }
        },
        components: {
            AjaxLink
        }
    }
</script>
