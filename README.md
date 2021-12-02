[![Development build](https://travis-ci.com/UN-OCHA/indicatorregistry8-site.svg?branch=develop)](https://travis-ci.com/UN-OCHA/indicatorregistry8-site)
[![Master build](https://travis-ci.com/UN-OCHA/indicatorregistry8-site.svg?branch=master)](https://travis-ci.com/UN-OCHA/indicatorregistry8-site)
![Development image](https://github.com/UN-OCHA/indicatorregistry8-site/workflows/Build%20docker%20image/badge.svg?branch=develop)
![Master image](https://github.com/UN-OCHA/indicatorregistry8-site/workflows/Build%20docker%20image/badge.svg?branch=master)

# Indicator Registry

https://ir.hpc.tools/

## Development

For local development, add this line to settings.local.php:
`$config['config_split.config_split.config_dev']['status'] = TRUE;`
After importing a fresh database, run `drush cim` to enable devel, database log
and stage_file_proxy.

## Pages

- https://indicatorregistry8-site.docksal/
