#!/bin/bash

# script to generate the Xaraya User Guide Output

STYLESHEET_IMAGES=/usr/share/sgml/docbook/dsssl-stylesheets/images
OUTPUT=/var/www/ddf/downloads/documentation

# make the HTML output
docbook2html -d ldp.dsl\#html -o $OUTPUT make.xml
cp -a $STYLESHEET_IMAGES $OUTPUT/stylesheet-images
mkdir -p $OUTPUT/callouts
mkdir -p $OUTPUT/sourceimages
cp -rf callouts/*.* $OUTPUT/callouts
cp -rf sourceimages/*.* $OUTPUT/sourceimages
cp *.gif $OUTPUT
cp *.png $OUTPUT
cp *.jpg $OUTPUT

# make the nochunks HTML output
docbook2html -d ldp.dsl\#html --nochunks -o $OUTPUT make.xml
mv $OUTPUT/make.html $OUTPUT/xarayaguide.html

# make the PDF output
docbook2pdf -d ldp.dsl\#print -o $OUTPUT make.xml
mv $OUTPUT/make.pdf $OUTPUT/xarayaguide.pdf
