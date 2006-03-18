#
# Makefile for the Xaraya Docbook refcard
#
# Requirements:
# - xsltproc 
# - fop
# - specific stylesheet which produces the reference card

# 
# Default values for the options (create make.opts.local to customize for yourself)
#
XSLTPROC=xsltproc --novalid
FOP=fop -q

# Which stylesheets to use for the transformations
DB2FOP=dbrefcard.xsl

# Source related
SOURCES=dbrefcard.xml

# Printed output parameters
PAPER.TYPE='USletter'
DRAFT.MODE='no'
DOUBLE.SIDED=0

# Allow to override all the above options locally
# which means that all options which may be overridden should be
# above this line and all others below this line.
-include make.opts.local

all: pdf $(DB2FOP)
fop:  $(patsubst %.xml,%.fop,$(SOURCES))
pdf:  $(patsubst %.xml,%.pdf,$(SOURCES))

%.fop: $(SOURCES)
	$(XSLTPROC) --stringparam draft.mode $(DRAFT.MODE) \
				--stringparam double.sided $(DOUBLE.SIDED) \
				--stringparam fop.extensions 1 \
				--stringparam ignore.image.scaling 1 \
				--stringparam paper.type $(PAPER.TYPE) \
				$(DB2FOP) $? > $@

%.pdf: %.fop
	$(FOP) -fo $? -pdf $@

clean:
	rm -f *.fop
	rm -f *.pdf



