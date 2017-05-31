<template>
    <transition name="fade">
        <div id="overlay" :class="{ 'is-active': isExplorerOpen }" v-if="isExplorerOpen"></div>
    </transition>
</template>
<script>
    import { mapState } from 'vuex'

    export default {
        computed: {
            ...mapState({
                isExplorerOpen: state => state.explorer.isOpen
            })
        }
    }
</script>
