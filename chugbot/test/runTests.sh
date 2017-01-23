#!/bin/bash

echo "Running function tests"
phpunit functionsTest.php || exit 1

echo "Setting up database"
./dbSetup.sh || exit 1

echo "Running database tests"
phpunit -c dbTestConfig.xml dbTest.php || exit 1

echo "Running assignment tests"
phpunit -c dbTestConfig.xml assignmentTest.php || exit 1

echo "ALL TESTS PASSED"
exit 0
