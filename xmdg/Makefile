#
# Makefile for the Xaraya Module Developersguide
#
# Requirements:
# - xsltproc 
# - fop
# - lynx
# - docbook xsl stylesheets
#

# Programs we use
XSLTPROC=xsltproc --novalid
FOP=fop -q
LYNX=lynx
MT=monotone

# Stylesheets we need are provided by standard docbook xsl distribution
# which unfortunately can live in many different locations, we add the ones
# we know and use (uncomment) the relevant one when making

# Darwinports on OSX
# DBXSL=/opt/local/share/xsl/docbook-xsl
# Gentoo on newton
DBXSL=/usr/share/sgml/docbook/xsl-stylesheets
# Cygwin/MSys on windows??
#DBXSL=????

# Where does the output need to go
# For newton
OUTPUT=/var/www/ddf/common/documentation/devguide
# For doc writers
#OUTPUT=.

DB2HTML=$(DBXSL)/xhtml/docbook.xsl
DB2CHUNKS=$(DBXSL)/xhtml/chunk.xsl
DB2FOP=$(DBXSL)/fo/docbook.xsl
DBIMAGES=$(DBXSL)/images

# HTML specific
CSSSTYLE=xmdg.css

# Source related
TOPFILE=xmdg.xml
SOURCES=$(wildcard *.xml) $(wildcard modules/*.xml) 

all: html pdf txt index.html
html: xmdg.html
fop:  xmdg.fop
pdf:  xmdg.pdf
txt:  xmdg.txt

xmdg.html: $(SOURCES)
	$(XSLTPROC) --stringparam html.stylesheet $(CSSSTYLE) $(DB2HTML) $(TOPFILE) > $@ 

index.html: $(SOURCES)
	$(XSLTPROC) --stringparam html.stylesheet $(CSSSTYLE) --stringparam chunk.section.depth 0 $(DB2CHUNKS) $(TOPFILE)

xmdg.fop: $(SOURCES)
	$(XSLTPROC) --stringparam draft.mode no --stringparam fop.extensions 1 --stringparam ignore.image.scaling 1 $(DB2FOP) $(TOPFILE) > $@

xmdg.pdf: fop
	$(FOP) -fo xmdg.fop -pdf $@

# txt target in fop sucks, but this is how it would be done
#xmdg.txt: xmdg.fop
#	$(FOP) -fo xmdg.fop -txt $@

# instead we use the poor mans txt formatter lynx!
xmdg.txt: html
	$(LYNX) -dump $< > $@

clean:
	rm -f *.html
	rm -f xmdg.fop
	rm -f xmdg.pdf
	rm -f xmdg.txt

monotone:
	$(MT) pull mt.xaraya.com 'com.xaraya.documentation'
	$(MT) update

webdocs: monotone all
	cp -a $(DBXSL)/images  $(OUTPUT)/stylesheet-images
	mkdir -p $(OUTPUT)/examples
	mkdir -p $(OUTPUT)/callouts
	mkdir -p $(OUTPUT)/sourceimages
	mkdir -p $(OUTPUT)/images
	#cp -rf examples/*.* $(OUTPUT)/examples
	#cp -rf callouts/*.* $(OUTPUT)/callouts
	#cp -rf sourceimages/*.* $(OUTPUT)/sourceimages
	cp *.css $(OUTPUT)
	#cp images/*.gif $(OUTPUT)/images
	#cp images/*.png $(OUTPUT)/images
	#cp images/*.jpg $(OUTPUT)/images
	cp xmdg.txt $(OUTPUT)
	cp xmdg.pdf $(OUTPUT)
	cp *.html $(OUTPUT)
