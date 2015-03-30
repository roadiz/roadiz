# Rozier backend theme

## Contribute

To enhance Rozier backend theme you must install Grunt and Bower:

```shell
cd themes/Rozier/static

npm install

bower install

# Launch Grunt to generate prod files
grunt

# Or… launch watch grunt when you’re
# working on LESS and JS files.
grunt watch
```

Then you will be able to switch theme to development mode
in `RozierApp.php`:

```php
$this->assignation['head']['backDevMode'] = true;
```

This will make Rozier theme to load each Roadiz JS file and Bower
components separately.

**Do not forget to set `$this->assignation['head']['backDevMode']` to `false` and to run
`grunt` before pushing your code!**
