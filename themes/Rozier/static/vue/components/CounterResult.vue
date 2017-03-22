<template>
    <div>
        <p>Total: {{ getTotal }}</p>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'
import { mapState } from 'vuex'

export default {
    computed: {
        ...mapGetters('counter', {
            getTotal: 'getNumber'
        })
    }
}
</script>
