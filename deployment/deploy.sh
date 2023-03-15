#!/bin/bash

cd $(dirname 0)

# push assets
cp -a assets/* ../webroot/res/

# run composer update
cd ..
composer update