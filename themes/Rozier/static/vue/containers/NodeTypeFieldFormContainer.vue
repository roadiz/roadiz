<template>
    <div>
        <counter-result></counter-result>
        <counter-button></counter-button>
    </div>
</template>

<script>
    import CounterButton from '../components/CounterButton.vue'
    import CounterResult from '../components/CounterResult.vue'

    export default {
        components: {
            CounterButton,
            CounterResult
        }
    }
</script>
