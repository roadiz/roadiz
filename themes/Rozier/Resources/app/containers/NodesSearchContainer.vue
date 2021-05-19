<!-- Inline template for this component -->
<!-- Template: widgets/nodesSourcesSearch.html.twig -->
<script>
    import { mapState, mapActions } from 'vuex'
    import { debounce } from 'lodash'

    // Components
    import AjaxLink from '../components/AjaxLink.vue'

    export default {
        data: function () {
            return {
                searchTerms: null
            }
        },
        computed: {
            ...mapState({
                items: state => state.nodesSourceSearch.items,
                isFocus: state => state.nodesSourceSearch.isFocus,
                isOpen: state => state.nodesSourceSearch.isOpen
            })
        },
        methods: {
            ...mapActions([
                'nodesSourceSearchUpdate',
                'nodeSourceSearchEnableFocus',
                'nodeSourceSearchDisableFocus'
            ]),
            enableFocus: debounce(function () {
                this.nodeSourceSearchEnableFocus()
            }, 50),
            disableFocus: debounce(function () {
                this.nodeSourceSearchDisableFocus()
            }, 500) // wait to enable click on links
        },
        watch: {
            searchTerms: debounce(function (newValue, oldValue) {
                this.nodesSourceSearchUpdate(newValue, oldValue)
            }, 200),
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
