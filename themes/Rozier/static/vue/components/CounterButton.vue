<template>
    <div>
        <button class="uk-button uk-button-primary" @click.prevent="increment">+10</button>
        <button class="uk-button uk-button-primary" @click.prevent="decrement">-10</button>
    </div>
</template>

<script>
    import { mapActions } from 'vuex'

    export default {
        methods: {
            ...mapActions('counter', [
                'increment',
                'decrement'
            ])
        }
    }
</script>
