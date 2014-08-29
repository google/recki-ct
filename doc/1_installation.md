<!--
 * Copyright 2014 Google Inc. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2014 Google Inc. All rights reserved
 * @license http://www.apache.org/licenses/LICENSE-2.0.txt Apache-2.0
-->

Installation
============

To use Recki-CT as a compiler, you *must* install the [`JIT-Fu` PECL Extension](https://github.com/krakjoe/jitfu).

Recki-CT can also be used as an analysis toolkit without `JIT-Fu` installed, and can generate compiler targets for other platforms.

### Install the dependencies

 1. Install [composer](https://getcomposer.org/download/) if you do not already have it installed:

        $ curl -sS https://getcomposer.org/installer | php

 2. Install the composer dependencies:

        $ composer.phar install

 3. Enjoy:

    $ php examples/01-basic-usage.php

### For Development Only:

 1. Install the development composer dependencies

        $ composer install --dev

 2. Run the unit tests

        $ vendor/bin/phpunit
