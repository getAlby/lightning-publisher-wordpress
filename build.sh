#!/bin/sh
composer install --no-dev
echo "Please provide the path the dist folder"
read dist

echo "Copying plugin to $dist"
rsync -av --exclude='.git/*' --exclude='./dist' . $dist
# zip -r wordpress-lightning-publisher.zip composer.json lightning-publisher.php vendor css js README.md --exclude='**/.git/*'
echo "Zipping plugin from $dist"
zip -r bitcoin-lightning-publisher.zip $dist --exclude='.git/*'
echo "Done"


