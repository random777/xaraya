#
# Makefile for the Xaraya guide
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

# Stylesheets we need are provided by standard docbook xsl distribution
# which unfortunately can live in many different locations, we add the ones
# we know and use (uncomment) the relevant one when making
# Darwinports
#DBXSL=/opt/local/share/xsl/docbook-xsl
# Gentoo on newton
DBXSL=/usr/share/sgml/docbook/xsl-stylesheets

DB2HTML=$(DBXSL)/xhtml/docbook.xsl
DB2FOP=$(DBXSL)/fo/docbook.xsl
DBIMAGES=$(DBXSL)/images

# HTML specific
CSSSTYLE=xarayaguide.css

# Source related
TOPFILE=xarayaguide.xml
SOURCES=$(wildcard *.xml) $(wildcard modules/*.xml) 

all: html pdf txt
html: xarayaguide.html
fop:  xarayaguide.fop
pdf:  xarayaguide.pdf
txt:  xarayaguide.txt

xarayaguide.html: $(SOURCES)
	$(XSLTPROC) --stringparam html.stylesheet $(CSSSTYLE) $(DB2HTML) $(TOPFILE) > $@ 

xarayaguide.fop: $(SOURCES)
	$(XSLTPROC) --stringparam draft.mode no --stringparam fop.extensions 1 --stringparam ignore.image.scaling 1 $(DB2FOP) $(TOPFILE) > $@

xarayaguide.pdf: xarayaguide.fop
	$(FOP) -fo xarayaguide.fop -pdf $@

# txt target in fop sucks, but this is how it would be done
#xarayaguide.txt: xarayaguide.fop
#	$(FOP) -fo xarayaguide.fop -txt $@
# instead we use the poor mans txt formatter lynx!
xarayaguide.txt: xarayaguide.html
	$(LYNX) -dump > $@
clean:
	rm -f xarayaguide.html
	rm -f xarayaguide.fop
	rm -f xarayaguide.pdf
	rm -f xarayaguide.txt
