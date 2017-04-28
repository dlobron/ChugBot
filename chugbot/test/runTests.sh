#!/bin/bash

function cleanup() {
    perl -pi -e 's/^\/\/    define\("MYSQL_HOST", "lo/    define\("MYSQL_HOST", "lo/' ../constants.php
    perl -pi -e 's/^    define\("MYSQL_HOST", "127/\/\/    define\("MYSQL_HOST", "127/' ../constants.php
    exit $1
}

perl -pi -e 's/^\/\/    define\("MYSQL_HOST", "127/    define\("MYSQL_HOST", "127/' ../constants.php
perl -pi -e 's/^    define\("MYSQL_HOST", "lo/\/\/    define\("MYSQL_HOST", "lo/' ../constants.php

echo "Running function tests"
phpunit functionsTest.php || cleanup 1

echo "Setting up database"
./dbSetup.sh || cleanup 1

echo "Running database tests"
phpunit -c dbTestConfig.xml dbTest.php || cleanup 1

echo "Running assignment tests"
phpunit -c dbTestConfig.xml assignmentTest.php || cleanup 1

echo "ALL TESTS PASSED"
cleanup 0
