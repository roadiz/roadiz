<template>
    <a class="uk-button uk-button-small" href="#" @click.prevent="toggleExplorer" data-document-widget-toggle-explorer>
        <i class="uk-icon-rz-explore"></i>
        <slot></slot>
    </a>
</template>

<script>
    import { mapActions } from 'vuex'

    export default {
        name: 'document-explorer-button',
        methods: {
            ...mapActions('documentExplorer', ['toggleExplorer']),
        }
    }
</script>
