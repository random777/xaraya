#
# Makefile for the Xaraya Module Developersguide
#
# Requirements:
# - xsltproc 
# - fop
# - lynx
# - docbook xsl stylesheets
#

# 
# Default values for the options (create make.opts.local to customize for yourself)
#
XSLTPROC=xsltproc --novalid
FOP=fop -q
LYNX=lynx
MT=monotone

# Gentoo on newton
DBXSL=/usr/share/sgml/docbook/xsl-stylesheets

# Where does the output need to go
# For newton
OUTPUT=/var/www/ddf/common/documentation/devguide

# Which stylesheets to use for the transformations
DB2HTML=$(DBXSL)/xhtml/docbook.xsl
DB2CHUNKS=$(DBXSL)/xhtml/chunk.xsl
DB2FOP=$(DBXSL)/fo/docbook.xsl

# Standard docbook images
DBIMAGES=$(DBXSL)/images

# HTML specific CSS
CSSSTYLE=xardevguide.css

# Source related
SOURCES=xmdg.xml xardevguide.xml
FINALDOC=xardevguide.xml

# Printed output parameters
PAPER.TYPE='USletter'
DRAFT.MODE='no'
DOUBLE.SIDED=0

# Allow to override all the above options locally
# which means that all options which may be overridden should be
# above this line and all others below this line.
-include make.opts.local

all: html pdf txt index.html
html: $(patsubst %.xml,%.html,$(SOURCES))
fop:  $(patsubst %.xml,%.fop,$(SOURCES))
pdf:  $(patsubst %.xml,%.pdf,$(SOURCES))
txt:  $(patsubst %.xml,%.txt,$(SOURCES))

%.html : %.xml 
	$(XSLTPROC) --stringparam html.stylesheet $(CSSSTYLE) \
				$(DB2HTML) $< > $@ 

index.html: $(FINALDOC)
	$(XSLTPROC) --stringparam html.stylesheet $(CSSSTYLE) \
				--stringparam chunk.section.depth 0 \
				$(DB2CHUNKS) $<

%.fop : %.xml
	$(XSLTPROC) --stringparam draft.mode $(DRAFT.MODE) \
				--stringparam double.sided $(DOUBLE.SIDED) \
				--stringparam fop.extensions 1 \
				--stringparam ignore.image.scaling 1 \
				--stringparam paper.type $(PAPER.TYPE) \
				$(DB2FOP) $< > $@

%.pdf: %.fop
	$(FOP) -fo $< -pdf $@

# txt target in fop sucks, but this is how it would be done
#xmdg.txt: xmdg.fop
#	$(FOP) -fo xmdg.fop -txt $@

# instead we use the poor mans txt formatter lynx!
%.txt: %.html
	$(LYNX) -dump $< > $@

clean:
	$(RM) *.html *.fop *.pdf $(patsubst %.xml,%.txt,$(SOURCES))

monotone:
	$(MT) pull mt.xaraya.com 'com.xaraya.documentation'
	$(MT) update

webdocs: monotone all
	mkdir -p $(OUTPUT)/stylesheet-images
	cp -a $(DBXSL)/images  $(OUTPUT)/stylesheet-images
	mkdir -p $(OUTPUT)/examples
	mkdir -p $(OUTPUT)/callouts
	mkdir -p $(OUTPUT)/images
	cp -rf images/*.* $(OUTPUT)/images
	cp *.css $(OUTPUT)
	cp *.txt $(OUTPUT)
	cp *.pdf $(OUTPUT)
	cp *.html $(OUTPUT)
