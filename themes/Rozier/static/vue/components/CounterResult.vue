<template>
    <div>
        <h1>Counter</h1>
        <p>counter: {{ getTotal }}</p>
    </div>
</template>

<script>
import {mapGetters} from 'vuex'

export default {
    computed: {
        ...mapGetters('counter', {
            getTotal: 'getNumber'
        })
    }
}
</script>