# Makefile for generating rfcs from xml

xml2rfc=./xml2rfc.tcl

SOURCES= $(wildcard *.xml)
SOURCENAMES = $(basename $(notdir $(SOURCES)))

HTMLS= $(addsuffix .html, $(SOURCENAMES))
TXTS = $(addsuffix .txt,  $(SOURCENAMES))

all: html txt


html: $(HTMLS)
txt : $(TXTS)


%.html: %.xml $(xml2rfc)
	$(xml2rfc) $< $(*F).html  

%.txt: %.xml $(xml2rfc)
	$(xml2rfc) $< $(*F).txt

clean:
	rm -f rfc*.html rfc*.txt *.*~ *~