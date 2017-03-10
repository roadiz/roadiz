<template>
    <button @click="increment">+50</button>
</template>

<script>
    import { mapActions } from 'vuex'

    export default {
        methods: {
            ...mapActions('counter', [
                'increment'
            ])
        }
    }
</script>