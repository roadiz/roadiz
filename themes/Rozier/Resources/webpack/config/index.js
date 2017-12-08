import debug from 'debug'
import getConfigBase from './base'
import configOverrides from './environments'

const dbg = debug('Roadiz-front:config  ')
dbg.color = debug.colors[1]

const getConfig = () => {
    dbg('👷‍♂️  Creating configuration.')
    let configBase = getConfigBase()

    dbg(`🕵️‍♂️  Looking for environment overrides for NODE_ENV "${configBase.env}".`)

    const overrides = configOverrides[configBase.env]
    if (configOverrides[configBase.env]) {
        dbg('🙋‍♂️  Found overrides, applying to default configuration.')
        Object.assign(configBase, overrides(configBase))
    } else {
        dbg('🤷‍♂️  No environment overrides found.')
    }

    return configBase
}

export default getConfig
