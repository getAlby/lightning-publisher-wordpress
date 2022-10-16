#!/bin/sh
composer install --no-dev
echo "Please provide the path the dist folder (the trunk folder of the SVN repo)"
read dist

echo "Copying plugin to $dist"
rsync -av --exclude='.git/*' --exclude='./dist' --exclude='.gitignore' --exclude='.git' --exclude='bitcoin-lightning-publisher.zip' . $dist
echo "Done"


