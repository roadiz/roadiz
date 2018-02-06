<template lang="html">
    <warning-modal
        :open="!connected"
        :title="translations.sessionExpireTitle"
        :content="translations.sessionExpireContent"
        :link-label="translations.login"
        :link-url="linkUrl">
    </warning-modal>
</template>

<script>
    import Vue from 'vue'
    import { mapState } from 'vuex'

    // Components
    import vmodal from 'vue-js-modal'
    import WarningModal from '../components/WarningModal.vue'

    Vue.use(vmodal)

    export default {
        data () {
            return {
                linkUrl: window.RozierRoot.routes.loginPage + '?_home=1'
            }
        },
        computed: {
            ...mapState({
                connected: state => state.connected,
                translations: state => state.translations
            })
        },
        components: {
            WarningModal
        }
    }
</script>
