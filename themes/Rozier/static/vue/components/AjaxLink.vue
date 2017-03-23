<template>
    <a :href="href" @click.prevent="onItemClick" :style="{ borderLeftColor: typeColor }">
        <slot>{{ title }}</slot>
    </a>
</template>

<script>
    export default {
        props: ['title', 'href', 'typeColor'],
        methods: {
            onItemClick: (e) => {
                Rozier.lazyload.onClick(e)
            }
        }
    }
</script>
