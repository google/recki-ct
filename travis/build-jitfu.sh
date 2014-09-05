#!/bin/sh -x
set -e
cd $TRAVIS_BUILD_DIR/build/ext
phpize
./configure --with-jitfu=$HOME
make -j2 --quiet
make install
echo "extension=jitfu.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini

