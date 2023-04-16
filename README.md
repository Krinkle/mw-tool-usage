# Usage tool

## Local development

1. `composer update`
2. `composer serve`

View <http://localhost:4000/>.

## Deployment on Toolforge

Initial setup, as per [Toolforge documentation](https://wikitech.wikimedia.org/wiki/Help:Toolforge/PHP):

1. Clone this repository.
2. Create public link, e.g.
   `$ ln -s ~/src/mw-tool-usage/public_html/ ~/public_html`
3. Start webservice
   `$ webservice --backend=kubernetes php7.4 restart`
4. Run composer (in a webservice shell, to ensure correct PHP version context)
   ```
   $ webservice shell
   tools-shell$ cd mw-tool-usage
   tools-shell$ composer update --no-dev
   ```

Updates:

```
tools-login:~$ webservice shell

tools-shell:~$ cd mw-tool-usage/
tools-shell:usage$ git pull
tools-shell:usage$ composer update --no-dev
```
