#!/bin/sh -x
cd themes/Rozier || exit 1;
yarn install --pure-lockfile
yarn run install
yarn run build
