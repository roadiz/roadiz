module.exports = {
    entry: './vue/App.js',
    output: {
        filename: './dist/vue-bundle.js'
    },
    externals: {
        vue: 'Vue',
        vuex: 'Vuex'
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
                options: {
                    postLoaders: {
                        html: 'babel-loader'
                    }
                }
            },
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
                loader: 'babel-loader',
                query: {
                    presets: ['env']
                }
            }
        ]
    },
    watch: true
}
