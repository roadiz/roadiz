var webpack = require('webpack');

var nodeEnv = process.env.NODE_ENV;
var production = nodeEnv === 'production';

var plugins = [
    new webpack.EnvironmentPlugin({
        NODE_ENV: nodeEnv,
    }),
    new webpack.NamedModulesPlugin(),
    new webpack.NoEmitOnErrorsPlugin(),
];

if (production) {
    plugins.push(
        new webpack.optimize.OccurrenceOrderPlugin(),
        new webpack.LoaderOptionsPlugin({
            minimize: true,
            debug: false
        }),
        new webpack.optimize.UglifyJsPlugin({
            compress: {
                warnings: false
            },
            mangle:   true,
            comments: false,
            sourceMap: false,
        }),
        new webpack.DefinePlugin({
            __SERVER__:      !production,
            __DEVELOPMENT__: !production,
            __DEVTOOLS__:    !production,
            'process.env':   {
                BABEL_ENV: JSON.stringify(nodeEnv),
            },
        })
    );
}

var config = {
    entry: './vue/App.js',
    output: {
        filename: './dist/vue-bundle.js'
    },
    externals: {
        vue: 'Vue',
        vuex: 'Vuex'
    },
    cache: true,
    devtool: production ? false : 'eval-source-map',
    plugins: plugins,
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
            },
            {
                test: /\.css$/,
                use: [ 'style-loader', 'css-loader' ]
            }
        ]
    },
    watch: nodeEnv !== 'production'
}

module.exports = config
