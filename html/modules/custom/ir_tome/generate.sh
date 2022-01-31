#!/bin/sh

drush="drush"
if [ -x "$(command -v fin)" ]; then
  drush="fin drush"
fi

$drush tome:static --verbose -y
cd ../../../..
ag GTM-xxxx ./static/ -l | xargs sed -i 's/GTM-xxxx/GTM-TQBWRWF/g'
mv static/export/indicators/index.html static/export/indicators/index.csv
