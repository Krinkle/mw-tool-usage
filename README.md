[![Build Status](https://travis-ci.org/Krinkle/mw-tool-usage.svg?branch=master)](https://travis-ci.org/Krinkle/mw-tool-usage)

# mw-tool-usage

## Deployment

1. Clone this repository.
2. Run `composer update --no-dev`.
3. Link `mw-tool-usage/public_html` to your server.

For Wikimedia Toolforge, run something like:
```
$ ln -s ~/src/mw-tool-usage/public_html/ ~/public_html
```

## Development

1. Clone this repository.
2. Run `composer update`.
3. Run `cd public_html/ && php -S localhost:4000`

View <http://localhost:4000/>.
