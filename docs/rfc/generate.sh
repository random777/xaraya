#!/bin/bash

# script to generate the RFCS for output on xaraya.com

# script needs to be run from within the rfc directory

OUTPUT=/var/www/ddf/common/documentation/rfcs

make html

cp -a *.html $OUTPUT
cp -rf images/* $OUTPUT/images
