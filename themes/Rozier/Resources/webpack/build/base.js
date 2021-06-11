import webpack from 'webpack'
import ExtractTextPlugin from 'extract-text-webpack-plugin'
import CopyWebpackPlugin from 'copy-webpack-plugin'
import debug from 'debug'
import WebpackNotifierPlugin from 'webpack-notifier'
import path from 'path'
import CleanWebpackPlugin from 'clean-webpack-plugin'
// import BundleAnalyzer from 'webpack-bundle-analyzer'

// const BundleAnalyzerPlugin = BundleAnalyzer.BundleAnalyzerPlugin
const dbg = debug('Roadiz-front:webpack-config:base  ')
dbg.color = debug.colors[3]

function resolve (dir) {
    return path.join(__dirname, '..', '..', dir)
}

const createLintingRule = (config) => ({
    test: /\.(js|vue)$/,
    loader: 'eslint-loader',
    enforce: 'pre',
    include: [resolve('app'), resolve('test')],
    exclude: [/node_modules/, /bower_components/, /Resources\/app\/vendor/],
    options: {
        formatter: require('eslint-friendly-formatter'),
        emitWarning: !config.showEslintErrorsInOverlay
    }
})

const getWebpackConfigBase = (config) => {
    const paths = config.utils_paths

    dbg('⚙  Exporting default webpack configuration.')

    let webpackConfig = {
        cache: true,
        stats: config.stats,
        devtool: config.devtool,
        name: 'client',
        target: 'web',
        context: paths.dist(),
        entry: {
            app: paths.client('main.js'),
            simple: paths.client('simple.js')
        },
        output: {
            path: paths.dist(),
            filename: config.assets_name_js,
            chunkFilename: '[name].[chunkhash].js',
            publicPath: config.public_path
        },
        module: {
            rules: [...(config.useEslint ? [createLintingRule(config)] : []), {
                test: /\.js$/,
                enforce: 'pre',
                loader: 'eslint-loader',
                exclude: [/node_modules/, /bower_components/, /Resources\/app\/vendor/]
            }, {
                test: /\.js?$/,
                exclude: /(node_modules)/,
                loader: 'babel-loader',
                query: {
                    cacheDirectory: true
                }
            }, {
                test: /\.scss?$/,
                loader: ExtractTextPlugin.extract({
                    fallback: 'style-loader',
                    use: [{
                        loader: 'css-loader',
                        options: {
                            importLoaders: 2,
                            sourceMap: true
                        }
                    }, {
                        loader: 'postcss-loader',
                        options: {
                            sourceMap: true
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
            }, {
                test: /\.less|.css?$/,
                loader: ExtractTextPlugin.extract({
                    fallback: 'style-loader',
                    use: [{
                        loader: 'css-loader',
                        options: {
                            importLoaders: 2,
                            sourceMap: true
                        }
                    }, {
                        loader: 'postcss-loader',
                        options: {
                            sourceMap: true
                        }
                    }, {
                        loader: 'resolve-url-loader'
                    }, {
                        loader: 'less-loader',
                        options: {
                            sourceMap: true
                        }
                    }]
                })
            }, {
                test: /\.(png|jpe?g|gif|svg)(\?.*)?$/,
                loader: 'url-loader',
                options: {
                    limit: config.limit_image_size,
                    publicPath: '../',
                    name: config.assets_name_img
                }
            },
            {
                test: /\.(woff2|woff|ttf|eot|svg|otf)$/,
                loader: 'file-loader',
                options: {
                    name: config.assets_name_font,
                    publicPath: '../'
                }
            },
            {
                test: /\.vue$/,
                loader: 'vue-loader',
                options: {
                    postLoaders: {
                        html: 'babel-loader'
                    },
                    loaders: {
                        scss: 'vue-style-loader!css-loader!sass-loader'
                    },
                    cacheBusting: config.cacheBusting,
                    transformToRequire: {
                        video: ['src', 'poster'],
                        source: 'src',
                        img: 'src',
                        image: 'xlink:href'
                    }
                }
            }
            ]
        },
        node: {
            setImmediate: false,
            dgram: 'empty',
            fs: 'empty',
            net: 'empty',
            tls: 'empty',
            child_process: 'empty'
        },
        plugins: [
            // new BundleAnalyzerPlugin(),
            new CleanWebpackPlugin(['css', 'img', 'js', 'fonts', 'vendors'], {
                root: config.utils_paths.dist(),
                verbose: false
            }),
            new webpack.DefinePlugin(config.globals),
            new CopyWebpackPlugin([{
                from: paths.client('assets'),
                to: paths.dist('assets')
            }]),
            new CopyWebpackPlugin([{
                from: paths.client('vendor'),
                to: paths.dist('vendor')
            }]),
            new ExtractTextPlugin({
                filename: config.assets_name_css,
                allChunks: true
            }),
            new webpack.NoEmitOnErrorsPlugin(),
            new WebpackNotifierPlugin({
                alwaysNotify: true
            }),
            // new webpack.ProvidePlugin({
            //     $: 'jquery',
            //     jQuery: 'jquery',
            //     'window.jQuery': 'jquery'
            // }),
            new webpack.IgnorePlugin(/^\.\/locale$/)
        ],
        resolve: {
            extensions: ['.js', '.vue', '.json'],
            alias: {
                '@': path.join(__dirname, '..', '..', 'app')
            }
        },
        externals: config.externals
    }

    if (config.refreshOnChange) {
        webpackConfig.plugins.push(new webpack.HotModuleReplacementPlugin())
    }

    if (config.bundleAnalyzerReport) {
        const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin
        webpackConfig.plugins.push(new BundleAnalyzerPlugin())
    }

    return webpackConfig
}

export default getWebpackConfigBase
