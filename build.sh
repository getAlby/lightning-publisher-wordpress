#!/bin/sh
composer install --no-dev
zip -r bitcoin-lightning-publisher.zip . --exclude='.git/*'
echo "Done"


