<template>
  <div id="app">
    <img :src="logoImg">
    <HelloWorld/>
  </div>
</template>

<script>
import HelloWorld from './components/HelloWorld'
import logo from './assets/logo.png'

export default {
    name: 'app',
    data () {
        return {
            logoImg: logo
        }
    },
    components: {
        HelloWorld
    }
}
</script>
