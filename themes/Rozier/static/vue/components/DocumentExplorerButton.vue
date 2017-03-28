<template>
    <a class="uk-button uk-button-small"
       :class="{ 'uk-active' : isActive }"
       href="#" @click.prevent="onClick"
       data-document-widget-toggle-explorer>
        <i class="uk-icon-rz-explore"></i>
        <slot></slot>
    </a>
</template>

<script>
    export default {
        name: 'document-explorer-button',
        props: ['isActive'],
        methods: {
            onClick: function () {
                this.$parent.onDocumentExplorerButtonClick()
            }
        }
    }
</script>
