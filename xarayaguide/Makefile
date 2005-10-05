#
# Makefile for the Xaraya guide
#
# Requirements:
# - xsltproc
# - fop
# - docbook xsl stylesheets
#

# Programs we use
XSLTPROC=xsltproc
FOP=fop

# Stylesheets we need are provided by standard docbook xsl distribution
# which unfortunately can live in many different locations, we add the ones
# we know and use (uncomment) the relevant one when making
# Darwinports
DBXSL=/opt/local/share/xsl/docbook-xsl
# Gentoo on newton
#DBXSL=/usr/share/sgml/docbook/xsl-stylesheets-1.69.1/

DB2HTML=$(DBXSL)/xhtml/docbook.xsl
DB2FOP=$(DBXSL)/fo/docbook.xsl
DBIMAGES=$(DBXSL)/images

# HTML specific
CSSSTYLE=xarayaguide.css

# Source related
TOPFILE=xarayaguide.xml
SOURCES=$(wildcard *.xml) $(wildcard modules/*.xml) 

all: html pdf
html: xarayaguide.html
pdf: xarayaguide.pdf

xarayaguide.html: $(SOURCES)
	$(XSLTPROC) --novalid --stringparam html.stylesheet $(CSSSTYLE) $(DB2HTML) $(TOPFILE) > $@ 

xarayaguide.pdf: $(SOURCES) 
	$(FOP) -xsl $(DB2FOP) -xml $(TOPFILE) -pdf $@

clean:
	rm xarayaguide.html