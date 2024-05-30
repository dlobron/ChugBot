#!/usr/bin/env bash

ENV=$(/opt/elasticbeanstalk/bin/get-config container -k environment_name)

if [[ $ENV == "Chugbotwi-env" ]]; then
  DOMAIN="wi.campramahchug.org"
elif [[ $ENV == "Chugbotne-env" ]]; then
  DOMAIN="ne.campramahchug.org"
elif [[ $ENV == "Chugbotdc-env" ]]; then
  DOMAIN="dc.campramahchug.org"
elif [[ $ENV == "Chugbotrb-env" ]]; then
  DOMAIN="rb.campramahchug.org"
elif [[ $ENV == "Chugbotbos-env" ]]; then
  DOMAIN="bos.campramahchug.org"
elif [[ $ENV == "Chugbotrr-env" ]]; then
  DOMAIN="rr.campramahchug.org"
else
  DOMAIN="test.campramahchug.org"
fi

sudo certbot -n -d ${DOMAIN} --nginx --agree-tos --email admin@campramahchug.org
