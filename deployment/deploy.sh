#!/bin/bash

cd $(dirname 0)

# push assets
cp -a assets/* ../webroot/assets/

# run composer update
cd ..
composer update