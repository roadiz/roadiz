<script>
    import { mapActions, mapState, mapGetters } from 'vuex'
    import {
        DOCUMENT_WIDGET_UPDATE_LIST
    } from '../store/mutationTypes'

    // Components
    import RzButton from '../components/RZButton.vue'
    import DocumentPreviewListItem from '../components/DocumentPreviewListItem.vue'
    import draggable from 'vuedraggable'
    import Dropzone from '../components/Dropzone.vue'

    export default {
        name: 'document-widget-container',
        data: () => {
            return {
                widgetName: null,
                initialDocuments: null,
                dropzoneLanguage: temp.messages.dropzone
            }
        },
        mounted: function () {
            this.documentWidgetsAddInstance(this)

            setTimeout(() => {
                this.widgetName = this.$refs.widget.getAttribute('name')
                this.initialDocuments = JSON.parse(this.$refs.widget.getAttribute('data-initial-documents'))
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
            },
            documents: {
                get () {
                    return this.widget.documents
                },
                set (newList) {
                    this.$store.commit(DOCUMENT_WIDGET_UPDATE_LIST, {
                        documentWidget: this.widget,
                        newList
                    })
                }
            }
        },
        methods: {
            ...mapActions([
                'documentWidgetsAddInstance',
                'documentWidgetsRemoveInstance',
                'documentWidgetsExplorerButtonClick',
                'documentWidgetRemoveDocument',
                'documentWidgetsAddDocument',
                'documentWidgetMoveDocument',
                'documentWidgetsInitData',
                'documentWidgetsDropzoneButtonClick'
            ]),
            onDocumentExplorerButtonClick: function () {
                this.documentWidgetsExplorerButtonClick(this.widget)
            },
            onDropzoneButtonClick: function () {
                this.documentWidgetsDropzoneButtonClick(this.widget)
            },
            removeDocument: function (document) {
                this.documentWidgetRemoveDocument({
                    documentWidget: this.widget,
                    document
                })
            },
            addDocument: function (document, newIndex = null) {
                this.documentWidgetsAddDocument({
                    documentWidget: this.widget,
                    document,
                    newIndex
                })
            },
            showSuccess: function (file, response) {
                this.addDocument(response.document)
            },
            showError: function (file, error, xhr) {
                console.log(file)
                console.log(error)
                console.log(xhr)
            }
        },
        components: {
            RzButton,
            DocumentPreviewListItem,
            draggable,
            Dropzone
        }
    }
</script>
