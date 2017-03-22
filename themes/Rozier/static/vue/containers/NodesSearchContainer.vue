<!-- Inline template for this component -->
<script>
    import { mapState, mapActions } from 'vuex'

    export default {
        props: ['url'],
        data: function () {
            return {
                search: null,
                isFocus: false,
            }
        },
        computed: {
            ...mapState('nodesSearch', ['search', 'items']),
        },
        methods: {
            ...mapActions('nodesSearch', ['updateSearch']),
            toggleFocus: function () {
                this.isFocus = !this.isFocus
            }
        },
        watch: {
            search: function (newValue, oldValue) {
                this.updateSearch(newValue, oldValue)
            }
        }
    }
</script>
