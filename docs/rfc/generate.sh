#!/bin/bash

# script to generate the RFCS for output on xaraya.com

# script needs to be run from within the rfc directory

OUTPUT=/var/www/ddf/common/documentation/rfcs

# make sure the clone is up to date
bk pull

make clean
make html

mv *.html $OUTPUT
cp -rf images/* $OUTPUT/images
cp -a *.css $OUTPUT
ln -sf $OUTPUT/rfcindex.html $OUTPUT/index.html
