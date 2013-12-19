#!/bin/bash

echo get_bob start `date`
if [ ! -f /tmp/lock_get_bob ]; then
  touch /tmp/lock_get_bob

  pushd bob
  wget -r -l1 --no-parent -N --no-remove-listing --user=ottrls --password='Nassau2Mexico!' -A "*201312*" -o wget.log ftp://173.201.208.1/data2/
  popd

  php ./getBOB.php

  rm -f /tmp/lock_get_bob
fi
echo get_bob end `date`

