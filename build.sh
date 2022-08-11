#!/bin/sh
composer install --no-dev
rm bitcoin-lightning-publisher.zip
zip -r bitcoin-lightning-publisher.zip . --exclude='.git/*'
echo "Done"


