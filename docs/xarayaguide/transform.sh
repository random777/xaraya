#!/bin/bash

# script to generate the Xaraya User Guide Output
# can be called with --nopdf to skip producing the PDF.

STYLESHEET_IMAGES=/usr/share/sgml/docbook/xsl-stylesheets/images
OUTPUT=/var/www/ddf/common/documentation/userguide

# make the HTML output
docbook2html -d ldp.dsl\#html -o $OUTPUT make.xml
cp -a $STYLESHEET_IMAGES $OUTPUT/stylesheet-images
mkdir -p $OUTPUT/callouts
mkdir -p $OUTPUT/sourceimages
cp -rf callouts/*.* $OUTPUT/callouts
cp -rf sourceimages/*.* $OUTPUT/sourceimages
cp *.css $OUTPUT
cp *.gif $OUTPUT
cp *.png $OUTPUT
cp *.jpg $OUTPUT
tidy -asxhtml -q -m -i $OUTPUT/*.html

# make the nochunks HTML output
docbook2html -d ldp.dsl\#html --nochunks -o $OUTPUT make.xml
mv $OUTPUT/make.html $OUTPUT/xarayaguide.html
tidy -asxhtml -q -m -i $OUTPUT/xarayaguide.html

# make the PDF output
if [ "X$1" = 'X--nopdf' ] 
then
  docbook2pdf -d ldp.dsl\#print -o $OUTPUT make.xml
  mv $OUTPUT/make.pdf $OUTPUT/xarayaguide.pdf
else
  rm $OUTPUT/xarayaguide.pdf	
fi
