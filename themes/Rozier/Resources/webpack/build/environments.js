import webpack from 'webpack'
import cssnano from 'cssnano'
import ExtractTextPlugin from 'extract-text-webpack-plugin'
import postcssFixes from 'postcss-fixes'
import postcssReduceTransform from 'postcss-reduce-transforms'
import cssMqpacker from 'css-mqpacker'
import Harddisk from 'html-webpack-harddisk-plugin'
import HtmlWebpackPlugin from 'html-webpack-plugin'
import debug from 'debug'
import OptimizeCSSPlugin from 'optimize-css-assets-webpack-plugin'
import UglifyJsPlugin from 'uglifyjs-webpack-plugin'

const dbg = debug('Roadiz-front:webpack-config:environments  ')
dbg.color = debug.colors[5]

export default {
    development: (base, config) => ({
        watch: true,
        devServer: {
            stats: config.stats,
            port: config.port,
            publicPath: config.public_path,
            host: config.address,
            watchOptions: {
                // poll: config.watchInterval,
                aggregateTimeout: 50,
                ignored: [/node_modules/, '/bower_components/', '/Resources/app/vendor/']
            }
        },
        resolve: {
            alias: {
                vue: 'vue/dist/vue.js'
            }
        },
        plugins: [
            new webpack.NamedModulesPlugin(),
            new HtmlWebpackPlugin({
                filename: config.utils_paths.views('partials/css-inject.html.twig'),
                template: config.utils_paths.views('partials/css-inject-src.html.twig'),
                cache: true,
                inject: false,
                alwaysWriteToDisk: true,
                refreshOnChange: config.refreshOnChange
            }),
            new HtmlWebpackPlugin({
                filename: config.utils_paths.views('partials/js-inject.html.twig'),
                template: config.utils_paths.views('partials/js-inject-src.html.twig'),
                chunks: ['app', 'vendor'],
                cache: true,
                inject: false,
                alwaysWriteToDisk: true,
                refreshOnChange: config.refreshOnChange
            }),
            new HtmlWebpackPlugin({
                filename: config.utils_paths.views('partials/simple-js-inject.html.twig'),
                template: config.utils_paths.views('partials/simple-js-inject-src.html.twig'),
                chunks: ['simple'],
                cache: true,
                inject: false,
                alwaysWriteToDisk: true,
                refreshOnChange: config.refreshOnChange
            }),
            new Harddisk()
        ]
    }),

    production: (base, config) => {
        dbg('🗑  Cleaning assets folder')
        dbg('👽  Using UglifyJs')
        dbg('🎨  Using PostCss')

        return {
            resolve: {
                alias: {
                    vue: 'vue/dist/vue.common.js'
                }
            },
            module: {
                loaders: [{
                    test: /\.scss?$/,
                    loader: ExtractTextPlugin.extract({
                        fallback: 'style-loader',
                        use: [{
                            loader: 'css-loader',
                            options: {
                                importLoaders: 2,
                                sourceMap: false
                            }
                        }, {
                            loader: 'postcss-loader',
                            options: {
                                plugins: [
                                    postcssFixes(),
                                    postcssReduceTransform(),
                                    cssMqpacker(),
                                    cssnano({
                                        autoprefixer: {
                                            add: true,
                                            remove: true,
                                            browsers: ['last 2 version']
                                        },
                                        discardComments: {
                                            removeAll: true
                                        },
                                        discardUnused: false,
                                        mergeIdents: false,
                                        reduceIdents: false,
                                        safe: true,
                                        sourcemap: false
                                    })
                                ]
                            }
                        }, {
                            loader: 'resolve-url-loader'
                        }, {
                            loader: 'sass-loader',
                            options: {
                                sourceMap: true
                            }
                        }]
                    })
                }]
            },
            plugins: [
                new webpack.DefinePlugin({
                    'process.env': {
                        NODE_ENV: '"production"'
                    }
                }),
                new UglifyJsPlugin({
                    exclude: [
                        /\/Resources\/app\/vendor/,
                        /\/node_modules/,
                        /\/bower_components/
                    ],
                    test: /\.js(\?.*)?$/i,
                    parallel: true
                }),
                new OptimizeCSSPlugin({
                    cssProcessorOptions: {
                        safe: true,
                        map: {
                            inline: false
                        }
                    }
                }),
                new webpack.HashedModuleIdsPlugin(),
                // enable scope hoisting
                new webpack.optimize.ModuleConcatenationPlugin(),
                new webpack.optimize.CommonsChunkPlugin({
                    chunks: ['app'],
                    name: 'vendor',
                    minChunks: (module) => {
                        return module.context && module.context.indexOf('node_modules') !== -1
                    }
                }),
                new webpack.optimize.CommonsChunkPlugin({
                    chunks: ['app'],
                    name: 'manifest',
                    minChunks: Infinity
                }),
                new webpack.optimize.CommonsChunkPlugin({
                    name: 'app',
                    async: 'vendor-async',
                    children: true,
                    minChunks: 3
                }),
                new HtmlWebpackPlugin({
                    filename: config.utils_paths.views('partials/css-inject.html.twig'),
                    template: config.utils_paths.views('partials/css-inject-src.html.twig'),
                    cache: true,
                    inject: false
                }),
                new HtmlWebpackPlugin({
                    filename: config.utils_paths.views('partials/js-inject.html.twig'),
                    template: config.utils_paths.views('partials/js-inject-src.html.twig'),
                    chunks: ['app', 'vendor', 'manifest'],
                    cache: true,
                    inject: false
                }),
                new HtmlWebpackPlugin({
                    filename: config.utils_paths.views('partials/simple-js-inject.html.twig'),
                    template: config.utils_paths.views('partials/simple-js-inject-src.html.twig'),
                    chunks: ['simple', 'manifest'],
                    cache: true,
                    inject: false
                })
            ]
        }
    }
}
