[![Build Status](https://travis-ci.org/Krinkle/mw-tool-usage.svg?branch=master)](https://travis-ci.org/Krinkle/mw-tool-usage)

# mw-tool-usage

## Install

1. Clone this repository.
2. Run `composer install --no-dev` (production), or `composer update` (development).
3. Copy `sample-config.php to `config.php`.
4. In `config.php`, set `$kgConf->remoteBase` to the URL at which
   your copy of `./mw-tool-usage/public_html/base` is served.
5. View `your-server/mw-tool-usage/public_html`.
