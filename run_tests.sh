#!/bin/bash

/usr/bin/phpunit tests/test_suite.php 2>&1; exit_code=$?;

if [ "$exit_code" -ne "0" ]; then
    cat <<EOT
*****************************
*        TESTS FAILED       *
*****************************
EOT
>&2;
    echo -e "$output" >&2;
    exit $exit_code;
fi
