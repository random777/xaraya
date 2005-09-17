# Makefile for generating rfcs from xml

xsltproc=xsltproc
xml2rfc=./xml2rfc.tcl
images=images
xstyle=rfc2629.xsl
xdtd=rfc2629.dtd
xindex=rfcindex

SOURCES=$(shell ls -1 rfc????.xml)
IDXSOURCES= _indexprolog.txt _indexepilog.txt _indexbuild _rfctitle.xslt

SOURCENAMES = $(basename $(notdir $(SOURCES)))

HTMLS= $(addsuffix .html, $(SOURCENAMES))
TXTS = $(addsuffix .txt,  $(SOURCENAMES))

# TODO: if someone needs it, look at the txt target, not working now
all: html 
.PHONY: all

# Targets for the two different formats
html: $(HTMLS) $(xindex).html
txt : $(TXTS)  $(xindex).txt


# RFC index can be automatically generated from the rfc source files
$(xindex).html: $(xindex).xml
$(xindex).xml: index

index: $(SOURCES) $(IDXSOURCES)
	rm -f $(xindex).xml
	rm -f $(xindex).html
	cat _indexprolog.txt >> $(xindex).tmp
	./_indexbuild >> $(xindex).tmp 
	cat _indexepilog.txt >> $(xindex).tmp
	mv $(xindex).tmp $(xindex).xml

# How to produce a html file out on an xml file

%.html: %.xml $(xindex).xml $(images)/$(*F)*.png $(xstyle) $(xdtd)
	$(xsltproc) $(xstyle) $(*F).xml > $(*F).html

# How to produce a txt file
%.txt: %.xml $(xml2rfc) $(xindex).xml
	$(xml2rfc) $< $(*F).txt

clean:
	rm -f rfc*.html rfc*.txt *.*~ *~ $(xindex).xml