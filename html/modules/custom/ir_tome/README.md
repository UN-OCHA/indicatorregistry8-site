# Pocam Tomifier

## Steps

1. `drush en pocam_tome -y`
2. Set GTM production Id
3. Create index at http://pocam8-site.docksal/admin/config/lunr_search/extracts/index
4. `drush tome:static`

## Local settings

Set output directory and make sure aggregation is enabled.

```php
$settings['tome_static_directory'] = '../static';
$config['system.performance']['css']['preprocess'] = TRUE;
$config['system.performance']['js']['preprocess'] = TRUE;
$config['system.logging']['error_level'] = 'hide';
```

## Local server

```bash
php -S localhost:8080 -t static
```
