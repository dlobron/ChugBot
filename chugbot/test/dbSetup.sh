#!/bin/bash

DBNAME="camprama_chugbot_db"
SQLPATH="/Applications/MAMP/htdocs/ChugBot.sql"
MYSQL="/Applications/MAMP/Library/bin/mysql"

echo "Clearing old database"
$MYSQL -uroot -proot -e "DROP DATABASE $DBNAME" || exit 1
echo "Creating database"
$MYSQL -uroot -proot -e "SOURCE $SQLPATH" || exit 1

exit 0
