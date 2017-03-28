<template>
    <transition name="fade">
        <div id="overlay" :class="{ 'is-active': isDocumentExplorerOpen }" v-if="isDocumentExplorerOpen">{{ isDocumentExplorerOpen }}</div>
    </transition>
</template>
<script>
    import { mapState } from 'vuex'

    export default {
        computed: {
            ...mapState({
                isDocumentExplorerOpen: state => state.documentExplorer.isOpen
            })
        }
    }
</script>
