#!/bin/sh -x
cd themes/DefaultTheme/static || exit 1;
yarn install --pure-lockfile || exit 1;
yarn run build;
