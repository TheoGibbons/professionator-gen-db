This is used to build the items database in ../Database
 1. > cd /.../blizzard-addon/ExternalScripts/PHP
 2. > composer install
 3. > cd /.../blizzard-addon/ExternalScripts/PHP/scripts
 3. > chmod 777 /.../blizzard-addon/TODO/Cache
 4. > php wh-pre-cache.php      :: This will pre-cache the wowhead item database
 4. > php bz-pre-cache.php      :: This will pre-cache the battle.net item database
 6. > php build-database.php    :: This will build the items database in ../Database