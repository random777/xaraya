#
# Makefile for the dbrefcard
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
DBXSL=/opt/local/share/xsl/docbook-xsl
# Gentoo on newton
#DBXSL=/usr/share/sgml/docbook/xsl-stylesheets
# Cygwin/MSys on windows??
#DBXSL=????

OUTPUT=.

DB2HTML=$(DBXSL)/xhtml/docbook.xsl
DB2CHUNKS=$(DBXSL)/xhtml/chunk.xsl
DB2FOP=$(DBXSL)/fo/docbook.xsl
DB2FOP=dbrefcard.xsl
DBIMAGES=$(DBXSL)/images

# HTML specific
CSSSTYLE=dbrefcard.css

# Source related
TOPFILE=dbrefcard.xml
SOURCES=$(wildcard *.xml) 

all: html pdf txt 
html: dbrefcard.html
fop:  dbrefcard.fop
pdf:  dbrefcard.pdf
txt:  dbrefcard.txt

dbrefcard.html: $(SOURCES)
	$(XSLTPROC) --stringparam html.stylesheet $(CSSSTYLE) $(DB2HTML) $(TOPFILE) > $@ 

dbrefcard.fop: $(SOURCES) $(DB2FOP)
	$(XSLTPROC) --stringparam draft.mode no --stringparam fop.extensions 1 --stringparam ignore.image.scaling 1 $(DB2FOP) $(TOPFILE) > $@

dbrefcard.pdf: fop
	$(FOP) -fo dbrefcard.fop -pdf $@

# txt target in fop sucks, but this is how it would be done
#dbrefcard.txt: dbrefcard.fop
#	$(FOP) -fo dbrefcard.fop -txt $@

# instead we use the poor mans txt formatter lynx!
dbrefcard.txt: html
	$(LYNX) -dump $< > $@

clean:
	rm -f *.html
	rm -f dbrefcard.fop
	rm -f dbrefcard.pdf
	rm -f dbrefcard.txt

