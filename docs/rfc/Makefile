# Makefile for generating rfcs from xml

xml2rfc=./xml2rfc.tcl
images=images

SOURCES=$(shell bk sfiles -g | grep ".xml")
SOURCENAMES = $(basename $(notdir $(SOURCES)))

HTMLS= $(addsuffix .html, $(SOURCENAMES))
TXTS = $(addsuffix .txt,  $(SOURCENAMES))

all: html txt
.PHONY: all

# Targets for the two different formats
html: $(HTMLS)
txt : $(TXTS)

# How to produce a html file
%.html: %.xml $(xml2rfc)
	$(GET) -Sq $(shell bk sfiles -g $(images) | grep "$(*F)")
	$(xml2rfc) $< $(*F).html  

# How to produce a txt file
%.txt: %.xml $(xml2rfc)
	$(xml2rfc) $< $(*F).txt

clean:
	rm -f rfc*.html rfc*.txt *.*~ *~