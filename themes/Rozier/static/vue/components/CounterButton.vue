<template>
    <div>
        <counter-result></counter-result>
        <button @click.prevent="increment">+10</button>
    </div>
</template>

<script>
    import { mapActions } from 'vuex'
    import CounterResult from './CounterResult.vue'

    export default {
        methods: {
            ...mapActions('counter', [
                'increment'
            ])
        },
        components: {
            CounterResult
        }
    }
</script>
