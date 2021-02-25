<!-- Inline template in 'node-type-fields/add.html.twig' and 'node-type-fields/edit.html.twig' -->
<script>
    import $ from 'jquery'
    import Vue from 'vue'
    import { mapActions } from 'vuex'

    // Containers and Components
    import RzTextarea from '../components/RzTextarea.vue'
    import CodeMirror from '../components/CodeMirror.vue'
    import NodeTypesDrawerContainer from './NodeTypesDrawerContainer.vue'

    export default {
        data: function () {
            return {
                selected: '',
                currentView: null,
                entity: '',
                mode: ''
            }
        },
        mounted: function () {
            // Get elements
            this.$formColIndexed = $(this.$el.getElementsByClassName('form-col-indexed')[0])
            this.$formColUniversal = $(this.$el.getElementsByClassName('form-col-universal')[0])
            this.$formColMinLength = $(this.$el.getElementsByClassName('form-col-minLength')[0])
            this.$formColMaxLength = $(this.$el.getElementsByClassName('form-col-maxLength')[0])
            this.$formColDefaultValues = $(this.$el.getElementsByClassName('form-col-defaultValues')[0])

            // Select element
            this.$formSelectOption = $(this.$el).find('#nodetypefield_type option')

            // Trigger selected value
            this.$formSelectOption.each((i, el) => {
                const $el = $(el)

                if ($el.attr('selected')) {
                    this.selected = $el.val()
                }
            })

            // Merge elements into a single array
            this.$formElements = {
                indexed: this.$formColIndexed,
                universal: this.$formColUniversal,
                minLength: this.$formColMinLength,
                maxLength: this.$formColMaxLength,
                defaultValues: this.$formColDefaultValues
            }

            // Hide all elements
            for (let key in this.$formElements) {
                if (this.$formElements.hasOwnProperty(key)) {
                    this.$formElements[key].addClass('hidden')
                }
            }

            // Define the default configuration
            this.defaultConfig = {
                indexed: true,
                universal: true,
                minLength: true,
                maxLength: true,
                defaultValues: true
            }

            // Set default view
            this.currentView = RzTextarea

            // Define specific config for each select value
            this.config = {
                1: {
                    minLength: true,
                    maxLength: true,
                    defaultValues: {
                        view: CodeMirror,
                        mode: 'yaml'
                    }
                },
                13: { // Référence de noeuds
                    defaultValues: {
                        view: NodeTypesDrawerContainer,
                        entity: 'node-type'
                    }
                },
                16: { // Noeuds enfants
                    defaultValues: {
                        view: NodeTypesDrawerContainer,
                        entity: 'node-type'
                    }
                },
                27: { // many-to-many.type
                    defaultValues: {
                        view: CodeMirror,
                        mode: 'yaml'
                    }
                },
                28: { // many-to-one.type
                    defaultValues: {
                        view: CodeMirror,
                        mode: 'yaml'
                    }
                },
                29: { // multiple-provider.type
                    defaultValues: {
                        view: CodeMirror,
                        mode: 'yaml'
                    }
                },
                30: { // single-provider.type
                    defaultValues: {
                        view: CodeMirror,
                        mode: 'yaml'
                    }
                },
                31: { // collection.type
                    defaultValues: {
                        view: CodeMirror,
                        mode: 'yaml'
                    }
                },
                4: { // markdown.type
                    defaultValues: {
                        view: CodeMirror,
                        mode: 'yaml'
                    }
                }
            }
        },
        watch: {
            selected: function (newValue) {
                this.escape()

                // Reset data
                this.currentView = null
                this.entity = null

                Vue.nextTick(() => {
                    window.setTimeout(() => {
                        this.setConfig(newValue)
                    }, 100)
                })
            }
        },
        methods: {
            ...mapActions([
                'escape',
                'explorerClose',
                'filterExplorerClose'
            ]),
            setConfig: function (value) {
                let config = this.defaultConfig

                if (this.config[value]) {
                    config = { ...config, ...this.config[value] }
                }

                // For each elements
                for (let key in this.$formElements) {
                    // Check the config
                    if (this.$formElements.hasOwnProperty(key) && config.hasOwnProperty(key)) {
                        if (config[key] === true) {
                            this.$formElements[key].removeClass('hidden')
                            this.currentView = RzTextarea
                        } else if (typeof (config[key]) === 'object') {
                            this.$formElements[key].removeClass('hidden')

                            this.entity = config[key].entity
                            this.mode = config[key].mode
                            this.currentView = config[key].view
                        } else {
                            this.$formElements[key].addClass('hidden')
                            this.currentView = RzTextarea
                        }
                    }
                }
            }
        }
    }
</script>
