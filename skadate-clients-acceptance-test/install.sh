#!/bin/bash

rm -rf selenium
git clone https://github.com/skalfa/selenium.git
rm -rf vendor
rm -rf composer.lock
composer install
