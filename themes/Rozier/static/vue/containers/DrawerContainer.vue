<script>
    import { mapActions, mapState, mapGetters } from 'vuex'
    import {
        DRAWERS_UPDATE_LIST
    } from '../types/mutationTypes'

    // Components
    import RzButton from '../components/RZButton.vue'
    import DocumentPreviewListItem from '../components/DocumentPreviewListItem.vue'
    import draggable from 'vuedraggable'
    import Dropzone from '../components/Dropzone.vue'

    export default {
        data: () => {
            return {
                drawerName: null,
                dropzoneLanguage: RozierRoot.messages.dropzone
            }
        },
        mounted: function () {
            this.drawersAddInstance(this)

            setTimeout(() => {
                this.drawerName = this.$refs.drawer.getAttribute('name')
                const ids = JSON.parse(this.$refs.drawer.getAttribute('data-initial-items'))
                const entity = this.$refs.drawer.getAttribute('data-accept-entity')

                this.drawersInitData({
                    drawer: this.drawer,
                    ids,
                    entity
                })
            }, 0)
        },
        beforeDestroy: function () {
            this.drawersRemoveInstance(this)
        },
        computed: {
            ...mapState({
                isActive: function () {
                    let drawer = this.$store.getters.drawersGetById(this._uid)
                    return drawer ? drawer.isActive : false
                },
                trans: function (state) {
                    return state.drawers.trans
                }
            }),
            drawer () {
                return this.$store.getters.drawersGetById(this._uid)
            },
            items: {
                get () {
                    return this.drawer.items
                },
                set (newList) {
                    this.$store.commit(DRAWERS_UPDATE_LIST, {
                        drawer: this.drawer,
                        newList
                    })
                }
            }
        },
        methods: {
            ...mapActions([
                'drawersAddInstance',
                'drawersRemoveInstance',
                'drawersRemoveItem',
                'drawersAddItem',
                'drawersMoveItem',
                'drawersInitData',
                'drawersExplorerButtonClick',
                'drawersDropzoneButtonClick'
            ]),
            onExplorerButtonClick: function () {
                this.drawersExplorerButtonClick(this.drawer)
            },
            onDropzoneButtonClick: function () {
                this.drawersDropzoneButtonClick(this.drawer)
            },
            removeItem: function (item) {
                this.drawersRemoveItem({
                    drawer: this.drawer,
                    item
                })
            },
            addItem: function (item, newIndex = null) {
                this.drawersAddItem({
                    drawer: this.drawer,
                    item,
                    newIndex
                })
            },
            showSuccess: function (file, response) {
                this.addItem(response.document)
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
