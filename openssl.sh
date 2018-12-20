#!/bin/bash

DOMAIN="gnupg.org"
echo | openssl s_client -showcerts -servername $DOMAIN -connect $DOMAIN:443 2>/dev/null | openssl x509 -inform pem -noout -text | grep "Not After"
