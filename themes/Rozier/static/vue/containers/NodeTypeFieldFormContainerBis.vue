<template>
    <div>
        <p>coucou</p>
        <p>counter: {{ getTotal }}</p>
    </div>
</template>

<script>
    import {mapGetters} from 'vuex'

    export default {
        name: "node-type-field-form-container-bis",
        computed: {
            ...mapGetters('counter', {
                getTotal: 'getNumber'
            })
        }
    }
</script>
