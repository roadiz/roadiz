<script>
    import { mapActions, mapState, mapGetters } from 'vuex'

    // Components
    import DocumentExplorerButton from '../components/DocumentExplorerButton.vue'
    import DocumentPreviewListItem from '../components/DocumentPreviewListItem.vue'

    export default {
        name: 'document-widget-container',
        data: () => {
            return {
                widgetName: null,
                initialDocuments: null
            }
        },
        mounted: function () {
            this.documentWidgetsAddInstance(this)

            setTimeout(() => {
                this.widgetName = this.$refs.widget.getAttribute('name')
                this.initialDocuments = this.$refs.widget.getAttribute('data-initial-documents').split(',')
                this.documentWidgetsInitData({
                    documentWidget: this.widget,
                    ids: this.initialDocuments
                })
            }, 0)
        },
        beforeDestroy: function () {
            this.documentWidgetsRemoveInstance(this)
        },
        computed: {
            ...mapState({
                isActive: function (state) {
                    let widget = state.documentWidgets.widgets.find(widget => {
                        return widget.id === this._uid
                    })

                    return widget ? widget.isActive : false
                },
                trans: function (state) {
                    return state.documentWidgets.trans
                }
            }),
            widget () {
                return this.$store.getters.documentWidgetsGetById(this._uid)
            }
        },
        methods: {
            ...mapActions([
                'documentWidgetsAddInstance',
                'documentWidgetsRemoveInstance',
                'documentWidgetsExplorerButtonClick',
                'documentWidgetRemoveDocument',
                'documentWidgetsInitData'
            ]),
            onDocumentExplorerButtonClick: function () {
                this.documentWidgetsExplorerButtonClick(this.widget)
            },
            removeDocument: function (document) {
                this.documentWidgetRemoveDocument({
                    documentWidget: this.widget,
                    document: document
                })
            }
        },
        components: {
            DocumentExplorerButton,
            DocumentPreviewListItem
        }
    }
</script>
