#!/bin/sh -x
cd themes/Install/static || exit 1;
yarn install --pure-lockfile || exit 1;
yarn run build;
