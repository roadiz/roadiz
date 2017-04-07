<!-- Inline template in 'node-type-fields/add.html.twig' -->
<script>
    export default {
        data: function () {
            return {
                selected: ''
            }
        },
        mounted: function () {
            // Trigger first value
            this.selected = 0

            // Get elements
            this.$formColIndexed = $(this.$el.getElementsByClassName('form-col-indexed')[0])
            this.$formColUniversal = $(this.$el.getElementsByClassName('form-col-universal')[0])
            this.$formColMinLength = $(this.$el.getElementsByClassName('form-col-minLength')[0])
            this.$formColMaxLength = $(this.$el.getElementsByClassName('form-col-maxLength')[0])
            this.$formColDefaultValues = $(this.$el.getElementsByClassName('form-col-defaultValues')[0])

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
                minLength: false,
                maxLength: false,
                defaultValues: false
            }

            // Define specific config for each select value
            this.config = {
                1: {
                    defaultValues: true
                }
            }
        },
        watch: {
            selected: function (newValue) {
                this.setConfig(newValue)
            }
        },
        methods: {
            setConfig: function (value) {
                let config = this.defaultConfig

                if (this.config[value]) {
                    config = { ...config, ...this.config[value] }
                }

                for (let key in this.$formElements) {
                    if (this.$formElements.hasOwnProperty(key) && config.hasOwnProperty(key)) {
                        if (config[key]) {
                            this.$formElements[key].removeClass('hidden')
                        } else {
                            this.$formElements[key].addClass('hidden')
                        }
                    }
                }
            }
        },
        components: {

        }
    }
</script>
