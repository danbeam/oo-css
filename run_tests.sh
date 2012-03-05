#!/bin/bash

if [ ! -z "$JENKINS_URL" ] || [ ! -z "$HUDSON_URL" ]; then
  mkdir -p coverage/html
  phpunit test/test_suite.php --coverage-html coverage/html --coverage-xml coverage/clover.xml 2>&1
else
  phpunit tests/test_suite.php 2>&1
fi
