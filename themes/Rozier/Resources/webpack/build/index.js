import debug from 'debug'
import getWebpackConfigBase from './base'
import webpackConfigOverrides from './environments'
import WebpackMerge from 'webpack-merge'

const dbg = debug('Roadiz-front:webpack-config  ')
dbg.color = debug.colors[4]

const getWebpackConfig = (config) => {
    dbg('👷‍♂️  Creating webpack configuration')
    const base = getWebpackConfigBase(config)
    dbg(`🕵️‍♂️  Looking for environment overrides for NODE_ENV "${config.env}".`)

    const overrides = webpackConfigOverrides[config.env]
    if (webpackConfigOverrides[config.env]) {
        dbg('🙋‍♂️  Found overrides, applying to default configuration.')
        return WebpackMerge.smart(base, overrides(base, config))
    } else {
        dbg('🤷‍♂️  No environment overrides found.')
        return base
    }
}

export default getWebpackConfig
