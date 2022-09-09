#!/bin/sh
composer install --no-dev
echo "Please provide the path the dist folder"
read dist

echo "Copying plugin to $dist"
rsync -av --exclude='.git/*' --exclude='./dist' --exclude='.gitignore' --exclude='.git' . $dist
echo "Done"


