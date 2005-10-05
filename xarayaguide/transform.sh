#!/bin/bash


STYLESHEET_IMAGES=/usr/share/sgml/docbook/xsl-stylesheets/images
OUTPUT=/var/www/ddf/common/documentation/userguide

# make the HTML output
cp -a $STYLESHEET_IMAGES $OUTPUT/stylesheet-images
mkdir -p $OUTPUT/examples
mkdir -p $OUTPUT/callouts
mkdir -p $OUTPUT/sourceimages
cp -rf examples/*.* $OUTPUT/examples
cp -rf callouts/*.* $OUTPUT/callouts
cp -rf sourceimages/*.* $OUTPUT/sourceimages
cp *.css $OUTPUT
cp *.gif $OUTPUT
cp *.png $OUTPUT
cp *.jpg $OUTPUT

# make the nochunks HTML output
cp xarayaguide.html $OUTPUT/xarayaguide.html
cp xarayaguide.txt $OUTPUT
cp xarayaguide.pdf $OUTPUT
