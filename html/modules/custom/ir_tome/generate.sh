#!/bin/sh

drush="drush"
if [ -x "$(command -v fin)" ]; then
  drush="fin drush"
fi

$drush en ir_tome -y
$drush cr

# Create index.
cd indexer
node index.js --login="`$drush uli`" --index="http://indicatorregistry8-site.docksal/admin/config/lunr_search/indicators/index"
cd ..

# Make static site.
$drush tome:static --verbose -y
cd ../../../..

# Fixes.
ag GTM-xxxx ./static/ -l | xargs sed -i 's/GTM-xxxx/GTM-TQBWRWF/g'
mv static/export/indicators/index.html static/export/indicators/index.csv

# Zip it.
zip -r ir_static.zip static/

# Disable modules.
$drush pm-uninstall tome_base lunr azure_storage -y
