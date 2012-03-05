#!/bin/bash

conf="phpunit.local.xml";

if [ ! -z "$JENKINS_URL" ]; then
  conf="phpunit.jenkins.xml";
fi

phpunit -c "$conf" --debug $@
