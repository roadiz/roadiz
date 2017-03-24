<template>
    <div>
        <slot></slot>
    </div>
</template>

<script>
    // Components
    import DocumentExplorerButton from '../components/DocumentExplorerButton.vue'

    export default {
        name: 'document-widget-container',
        components: {
            DocumentExplorerButton
        }
    }
</script>
