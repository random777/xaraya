#!/bin/bash

# script to generate the RFCS for output on xaraya.com

# script needs to be run from within the rfc directory

OUTPUT=/var/www/ddf/common/documentation/rfcs

# make sure the clone is up to date
# TODO: Activate monotone equivalent here
monotone pull mt.xaraya.com 'com.xaraya.documentation'
monotone update

#make clean
make html
make txt

cp rfc????.html $OUTPUT
cp rfcindex.* $OUTPUT
cp rfc????.txt $OUTPUT
cp -rf images/* $OUTPUT/images
cp -a *.css $OUTPUT
ln -sf $OUTPUT/rfcindex.html $OUTPUT/index.html
