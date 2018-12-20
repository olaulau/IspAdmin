#!/bin/bash

source cli.conf.sh

curl --header "Content-Type: application/json" \
  --insecure --request POST \
  --data '{"username":"'$username'","password":"'$password'"}' \
  https://ispconfig.d-l.fr/remote/json.php?login
