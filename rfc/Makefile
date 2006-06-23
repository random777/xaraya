# Makefile for generating rfcs from xml
# Config options, overridable in make.opts.local

# Where to put the output
OUTPUT=/var/www/ddf/common/documentation/rfcs
MT=mtn --quiet
MTHOST=mt.xaraya.com
DOCBRANCHES=com.xaraya.documentation


# General producer stuff
fop=fop
xsltproc=xsltproc
xml2rfc=./xml2rfc.tcl
images=images
xstyle=rfc2629.xsl
xpreprocess=rfcincludes.xsl
xstyleFO=rfc2629toFO.xslt
xstyleFOP=xsl11toFop.xslt
xdtd=rfc2629.dtd
xindex=rfcindex

# Allow to override all the above options locally
# which means that all options which may be overridden should be
# above this line and all others below this line.
-include make.opts.local

SOURCES=$(shell ls -1 rfc????.xml)
IDXSOURCES= _indexprolog.txt _indexepilog.txt _indexbuild _rfcindexpart.xslt

SOURCENAMES = $(basename $(notdir $(SOURCES)))

HTMLS= $(addsuffix .html, $(SOURCENAMES))
TXTS= $(addsuffix  .txt,  $(SOURCENAMES))
PDFS= $(addsuffix  .pdf, $(SOURCENAMES))
FLATS= $(addsuffix  .flat, $(SOURCENAMES))

# TODO: if someone needs it, look at the txt target, not working now
all: flat html pdf txt
.PHONY: all

# Targets for the two different formats
flat: $(FLATS) 
html: flat $(HTMLS) $(xindex).html
txt:  flat $(TXTS)  $(xindex).txt
pdf:  flat $(PDFS)  $(xindex).pdf

# RFC index can be automatically generated from the rfc source files
$(xindex).pdf: $(xindex).xml
$(xindex).html: $(xindex).xml
$(xindex).xml: index

index: flat $(IDXSOURCES)
	@echo "-- Generating index..."
	$(RM) $(xindex).xml
	cat _indexprolog.txt >> $(xindex).tmp
	./_indexbuild >> $(xindex).tmp
	cat _indexepilog.txt >> $(xindex).tmp
	mv $(xindex).tmp $(xindex).xml

# TODO: I dont know how to do this more generic
rfc0007.xml: $(images)/rfc0007*.png; @touch $@
rfc0010.xml: $(images)/rfc0010*.gif; @touch $@
rfc0028.xml: $(images)/rfc0028*.png; @touch $@
rfc0029.xml: $(images)/rfc0029*.png; @touch $@
rfc0034.xml: $(images)/rfc0034*.png; @touch $@
rfc0055.xml: rfc0055/*; @touch $@
rfc2629.xml: $(images)/rfc2629*.png; @touch $@

# Static pattern rules
# How to flatten (resolve includes) an xml file
# Basically just run the includes over it.
%.flat: %.xml
	@echo "-- $< -> $@ "
	$(xsltproc) $(xpreprocess) $< > $@
	
# How to produce a html file out of an flat xml file
%.html: %.flat $(xstyle) $(xdtd)
	@echo "-- $< -> $@ "
	$(xsltproc) $(xstyle) $< > $@

# How to produce a pdf file out of an xsl-fo file
%.pdf: %.fo $(xstyle) $(xstyleFOP) $(xdtd)
	@echo "-- $< -> $@ "
	$(fop) -q -xml $< -xsl $(xstyleFOP) -pdf $@

# How to produce a fop xml file out of a flat xml file
%.fo: %.flat $(xstyleFO)
	$(xsltproc) $(xstyleFO) $< > $@
	
# How to produce a txt file
%.txt: %.flat $(xml2rfc)
	@echo "-- $< -> $@ "
	$(xml2rfc) $<

clean:
	$(RM) *.flat *.fo rfc*.html rfc*.txt rfc*.pdf *.*~ *~ $(xindex).xml *.tmp

# specific target for xaraya net
forweb: catchup html pdf txt
	@echo '-- Publishing to web location... '
	mkdir -p $(OUTPUT)
	cp -p -f rfc????.html $(OUTPUT)
	cp -p -f rfc????.txt  $(OUTPUT)
	cp -p -f rfc????.pdf  $(OUTPUT)
	cp -p -f rfcindex.* $(OUTPUT)
	mkdir -p $(OUTPUT)/images
	cp -p -rf images/* $(OUTPUT)/images
	cp -p *.css $(OUTPUT)/
	ln -sf $(OUTPUT)/rfcindex.html $(OUTPUT)/index.html

catchup:
	@echo "-- Syncing files with repository... "
	$(MT) pull $(MTHOST) $(DOCBRANCHES)
	$(MT) update
