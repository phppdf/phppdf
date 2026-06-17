#!/bin/bash

docker run --rm -t -v ./:/app ghcr.io/phppdf/docker-image-php-cli:dev composer phpcbf $@
