# Makefile for generating rfcs from xml

xsltproc=xsltproc
xml2rfc=xml2rfc.tcl
images=images
xstyle=rfc2629.xsl
xindex=rfcindex

SOURCES=$(shell bk sfiles -g | grep ".xml")

SOURCENAMES = $(basename $(notdir $(SOURCES)))

HTMLS= $(addsuffix .html, $(SOURCENAMES))
TXTS = $(addsuffix .txt,  $(SOURCENAMES))

all: html txt
.PHONY: all

# Targets for the two different formats
html: index $(HTMLS)
txt : index $(TXTS)

# RFC index can be automatically generated from the rfc source files
$(xindex).xml: index

index: $(SOURCES) _indexprolog.txt _indexepilog.txt
	rm -f $(xindex).xml
	rm -f $(xindex).tmp
	cat _indexprolog.txt >> $(xindex).tmp
	./_indexbuild >> $(xindex).tmp 
	cat _indexepilog.txt >> $(xindex).tmp
	mv $(xindex).tmp $(xindex).xml

# How to produce a html file
%.html: %.xml $(xml2rfc) $(xindex).xml $(shell bk sfiles -g $(images) | grep "$(*F)")
	$(xsltproc) $(xstyle) $< > $(*F).html

# How to produce a txt file
%.txt: %.xml $(xml2rfc) $(xindex).xml
	$(xml2rfc) $< $(*F).txt

clean:
	rm -f rfc*.html rfc*.txt *.*~ *~ $(xindex).xml