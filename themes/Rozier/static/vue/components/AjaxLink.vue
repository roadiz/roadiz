<template>
    <a :href="href" @click.prevent="onItemClick" :style="{ borderLeftColor: typeColor }">
        <slot>{{ title }}</slot>
    </a>
</template>

<script>
    import {
        KEYBOARD_EVENT_ESCAPE
    } from '../store/mutationTypes'

    export default {
        props: ['title', 'href', 'typeColor'],
        methods: {
            onItemClick: function (e) {
                Rozier.lazyload.onClick(e)
                this.$store.commit(KEYBOARD_EVENT_ESCAPE)
            }
        }
    }
</script>
