import debug from 'debug'
import path from 'path'
import ip from 'ip'

const dbg = debug('Roadiz-front:config:base  ')
dbg.color = debug.colors[2]

const getConfig = () => {
    let config = {
        env: process.env.NODE_ENV || 'development'
    }

    config = {
        ...config,
        address: ip.address(),
        port: '8090',
        devtool: 'source-map',
        cacheBusting: false,
        useEslint: false,
        showEslintErrorsInOverlay: false,

        // ----------------------------------
        // Project Structure
        // ----------------------------------
        path_base: path.resolve(__dirname, '..', '..'),
        dir_client: 'app',
        dir_dist: '../static',
        dir_root: '../',
        dir_views: 'views',

        /// For analysis only
        bundleAnalyzerReport: false,

        // ----------------------------------
        // Stats
        // ----------------------------------
        stats: {
            chunks: false,
            chunkModules: false,
            colors: true,
            children: false,
            version: false,
            reasons: false
        },

        // ----------------------------------
        // Watch options
        // ----------------------------------
        refreshOnChange: process.env.REFRESH_ON_CHANGE === 'true',
        watchInterval: 200, // Poll interval in ms

        // ----------------------------------
        // Inputs
        // ----------------------------------
        js_vendors: [],

        // ----------------------------------
        // Outputs
        // ----------------------------------
        assets_name_js: 'js/[name].js',
        assets_name_img: 'img/[name].[ext]',
        assets_name_css: 'css/[name].css',
        assets_name_font: 'fonts/[name].[ext]',

        // ----------------------------------
        // SVG Structure
        // ----------------------------------
        svg_paths: 'svg/*.svg',
        svg_sprite_name: 'sprite.svg.twig',
        svg_sprite_path: 'svg',

        // ----------------------------------
        // Images
        // ----------------------------------
        limit_image_size: 8000, // 8kb

        // ----------------------------------
        // Externals
        // ----------------------------------
        externals: {
            uikit: 'UIkit',
            vue: 'Vue',
            vuex: 'Vuex',
            $: '$',
            jquery: '$',
            jQuery: '$',
            'window.jQuery': '$'
        },

        // ----------------------------------
        // Globals
        // ----------------------------------
        // ⚠️ : You have to add all these constants to .eslintrc file
        globals: {
            'DEVELOPMENT': JSON.stringify(config.env === 'development'),
            'PRODUCTION': JSON.stringify(config.env === 'production'),
            'ENVIRONMENT': JSON.stringify(config.env)
        }
    }

    config.public_path = ''

    // ------------------------------------
    // Utilities
    // ------------------------------------
    const resolve = path.resolve
    const base = (...args) => {
        return Reflect.apply(resolve, null, [config.path_base, ...args])
    }

    config.utils_paths = {
        base: base,
        client: base.bind(null, config.dir_client),
        dist: base.bind(null, config.dir_dist),
        views: base.bind(null, config.dir_views)
    }

    dbg('⚙  Exporting default configuration.')
    return config
}

export default getConfig
