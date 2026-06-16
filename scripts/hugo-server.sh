#!/bin/bash

docker run --rm -it -p "1313:1313" -v ./:/src ghcr.io/phppdf/docker-image-hugo:dev server --bind 0.0.0.0 $@
