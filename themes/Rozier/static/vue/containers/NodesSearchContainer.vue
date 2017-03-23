<!-- Inline template for this component -->
<!-- Template: widgets/nodesSourcesSearch.html.twig -->
<script>
    import { mapState, mapActions } from 'vuex'

    export default {
        props: ['url'],
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
            toggleFocus: function () {
                window.setTimeout(() => {
                    this.isFocus = !this.isFocus
                }, 50)
            },
            onItemClick: (e) => {
                Rozier.lazyload.onClick(e)
            }
        },
        watch: {
            searchTerms: function (newValue, oldValue) {
                this.updateSearch(newValue, oldValue)
            }
        }
    }
</script>
