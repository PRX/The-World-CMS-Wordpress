ZIPFILE=`find ./reference/ -maxdepth 1 -name "*.sql.gz"`
SQLFILE=`find ./reference/ -maxdepth 1 -name "*.sql"`

# TODO: Replace all lando command calls with lando-less option.

if [ ${#ZIPFILE} -gt 0 ];
 then
  echo "Import local $ZIPFILE"
  # lando db-import $ZIPFILE
elif [ ${#SQLFILE} -gt 0 ];
 then
  echo "Import local $SQLFILE"
  # lando db-import $SQLFILE
else
  echo "Import Pantheon Dev database"
  # lando pull --database=dev --files=none --code=none
fi
echo "Sync ACF Groups automatically from JSON files."
# lando wp tw acf-sync-field-groups

echo "Create local admin user"
# lando wp user create local-admin local-admin@the-world-wp.com
# lando wp user set-role local-admin administrator
