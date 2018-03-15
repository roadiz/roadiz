/*
 * Copyright © 2017, Rezo Zero
 *
 * @file webpack.config.babel.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

import getConfig from './Resources/webpack/config'
import getWebpackConfig from './Resources/webpack/build'

module.exports = getWebpackConfig(getConfig())
