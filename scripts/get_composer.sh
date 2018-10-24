#!/bin/bash

# Download the latest composer binary

# File will be named "composer.phar"

if [[ ! -d "scripts" ]]; then
    >&2 echo "Please run this script from the project's root directory."
    exit 1
fi

EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('SHA384', 'composer-setup.php');")"

if [[ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]]; then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
RESULT=$?
rm composer-setup.php

if [[ -f "composer.phar" ]]; then
    >&1 echo "Composer successfully downloaded."
fi

exit $RESULT
