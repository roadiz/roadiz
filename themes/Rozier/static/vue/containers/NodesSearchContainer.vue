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
                isFocus: false,
            }
        },
        computed: {
            ...mapState('nodesSourceSearch', ['searchTerms', 'items']),
        },
        methods: {
            ...mapActions('nodesSourceSearch', ['updateSearch']),
            toggleFocus: _.debounce(function () {
                this.isFocus = !this.isFocus
            }, 50)
        },
        watch: {
            searchTerms: _.debounce(function (newValue, oldValue) {
                this.updateSearch(newValue, oldValue)
            }, 350)
        },
        components: {
            AjaxLink
        }
    }
</script>
