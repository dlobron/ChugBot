#!/bin/bash

set -o nounset
set -o pipefail
set -o errexit

case ${1:-''} in
    start)
	echo "Starting services"
	;;
    stop)
	echo "Stopping services"
	;;
    *)
	echo "Usage: $0 [start|stop]"
	exit 1
esac
	  

for i in nginx php@8.0 mysql; do brew services $1 $i; done

export ADMIN_EMAIL_USERNAME="abbab081eb9a7c"
export ADMIN_EMAIL_PASSWORD="c61a5bc3b91779"
