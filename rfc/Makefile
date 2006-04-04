# Makefile for generating rfcs from xml

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

SOURCES=$(shell ls -1 rfc????.xml)
IDXSOURCES= _indexprolog.txt _indexepilog.txt _indexbuild _rfctitle.xslt

SOURCENAMES = $(basename $(notdir $(SOURCES)))

HTMLS= $(addsuffix .html, $(SOURCENAMES))
TXTS= $(addsuffix .txt,  $(SOURCENAMES))
PDFS= $(addsuffix .pdf, $(SOURCENAMES))

# TODO: if someone needs it, look at the txt target, not working now
all: html pdf txt
.PHONY: all

# Targets for the two different formats
html: $(HTMLS) $(xindex).html
txt: $(TXTS)  $(xindex).txt
pdf: $(PDFS)  $(xindex).pdf

# RFC index can be automatically generated from the rfc source files
$(xindex).pdf: $(xindex).xml
$(xindex).html: $(xindex).xml
$(xindex).xml: index

index: $(SOURCES) $(IDXSOURCES)
	rm -f $(xindex).xml
	cat _indexprolog.txt >> $(xindex).tmp
	./_indexbuild >> $(xindex).tmp 
	cat _indexepilog.txt >> $(xindex).tmp
	mv $(xindex).tmp $(xindex).xml

# How to produce a html file out of an xml file
%.html: %.xml $(images)/$(*F)*.png $(xstyle) $(xdtd)
	$(xsltproc) $(xpreprocess) $(*F).xml > $(*F).tmp
	$(xsltproc) $(xstyle) $(*F).tmp > $(*F).html
	rm $(*F).tmp

# How to produce a pdf file out of an xml file
%.pdf: %.xml $(images)/$(*F)*.png $(xstyle) $(xstyleFO) $(xstyleFOP) $(xdtd)
	$(xsltproc) $(xpreprocess) $(*F).xml > $(*F).tmp
	$(xsltproc) $(xstyleFO) $(*F).tmp > tmp.fo
	$(fop) -q -xml tmp.fo -xsl $(xstyleFOP) -pdf $(*F).pdf
	rm tmp.fo
	rm $(*F).tmp

# How to produce a txt file
%.txt: %.xml $(xml2rfc)
	$(xsltproc) $(xpreprocess) $(*F).xml > $(*F).tmp
	$(xml2rfc) $(*F).tmp
	rm $(*F).tmp

clean:
	rm -f *.fo rfc*.html rfc*.txt rfc*.pdf *.*~ *~ $(xindex).xml *.tmp
