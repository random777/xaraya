#!/bin/sh
openjade -t tex -d xar.dsl $1.dbk && {
        # Fix bad _ in template headers
        mv $1.tex $1.otex
        sed 's/_/\\_/g' <$1.otex >$1.tex
        rm -f $1.otex
        pdfjadetex $1.tex
        pdfjadetex $1.tex
        pdfjadetex $1.tex
}
