<template>
    <a class="uk-button uk-button-small"
       :class="{ 'uk-active' : isActive }"
       href="#" @click.prevent="callback">
        <slot></slot>
    </a>
</template>

<script>
    export default {
        name: 'rz-button',
        props: ['isActive', 'callback']
    }
</script>
