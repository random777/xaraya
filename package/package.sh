#!/bin/sh

# Produce a package of the repository
# All official packages must be produced by running this script on
# the main server and not locally.

export VERSION=$1
if [$# < 1]; then
        echo "Usage $0 <Version>\n"
        echo "   Version - The version of the package being produced. \n"
        echo "             e.g. 0.9.0.3\n"
fi
REPNAME="xaraya-stable xaraya-modules"
DOWNLOADS=/var/www/ddf/downloads/
TYPE=plain

function preparefiles()
{
	test -d $DEST/html/var||return
        mv $DEST/html/var/config.system.php.dist $DEST/html/var/config.system.php
        chmod 666 $DEST/html/var/config.system.php
        chmod 777 $DEST/html/var/cache
	mkdir $DEST/html/var/cache/templates
        chmod 777 $DEST/html/var/cache/templates
}

for i in $REPNAME; do
	DEST=$i-$VERSION
	SOURCE=$i
	bk export  $SOURCE $DEST
	bk changes -c-1d $SOURCE > $DEST/Changelog
	preparefiles
	tar -czf $DEST.tar.gz $DEST
	zip -r $DEST.zip $DEST
	rm -rf $DEST
	mv $DEST.tar.gz $DOWNLOADS
	mv $DEST.zip $DOWNLOADS
done

# and create the xaraya-all tarball
REPNAME=xaraya-all
DEST=xaraya-all-$VERSION
bk export  xaraya-stable $DEST
bk changes -c-1d xaraya-stable > $DEST/Changelog-core
bk export  xaraya-modules $DEST/html/modules
bk changes -c-1d xaraya-modules > $DEST/Changelog-modules
preparefiles
tar -czf $DEST.tar.gz $DEST
zip -r $DEST.zip $DEST
rm -rf $DEST
mv $DEST.tar.gz $DOWNLOADS
mv $DEST.zip $DOWNLOADS
