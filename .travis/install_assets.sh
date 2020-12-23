#!/bin/sh -x
cd themes/Install/static || exit 1;
yarn install --pure-lockfile;
yarn run build;
