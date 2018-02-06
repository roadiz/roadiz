<template>
    <a :href="href" @click.prevent="onItemClick" :style="{ borderLeftColor: typeColor }">
        <slot>{{ title }}</slot>
    </a>
</template>

<script>
    import {
        KEYBOARD_EVENT_ESCAPE
    } from '../types/mutationTypes'

    export default {
        props: ['title', 'href', 'typeColor'],
        methods: {
            onItemClick: function (e) {
                window.Rozier.lazyload.onClick(e)
                this.$store.commit(KEYBOARD_EVENT_ESCAPE)
            }
        }
    }
</script>
