# Copyright 1999-2003 , Xaraya Development Team
# Distributed under the terms of the GNU General Public License, v2 or later
# $Header: $


DESCRIPTION="Xaraya Application Framework"
HOMEPAGE="http://www.xaraya.com"
SLOT="0"
LICENSE="GPL-2"
KEYWORDS="~x86"

DEPEND="virtual/php"

S="${WORKDIR}/${P}"

SRC_URI="mirror://sourceforge/xaraya/${P}-full.tar.gz"


src_unpack() {
	unpack ${A}
}

src_install() {
       # stolen from phpwebsite ebuild, needs to be made non webserver specific
       HTDOC_ROOT="`grep '^DocumentRoot' /etc/apache/conf/apache.conf | cut -d\  -f2`"
       [ -z "${HTDOC_ROOT}" ] && HTDOC_ROOT="`grep '^DocumentRoot' /etc/apache2/conf/apache2.conf | cut -d\  -f2`"
       [ -z "${HTDOC_ROOT}" ] && HTDOC_ROOT="/home/httpd/htdocs"

	cd ${S}
	dodir ${HTDOC_ROOT}/xaraya
	cp -r html/* ${D}/${HTDOC_ROOT}/xaraya
        dodoc CREDITS.txt INSTALL.txt LICENSE.txt INSTALL.txt XarayaDocs.txt releaselog-*
       
}

pkg_postinst() {
        HOSTNAME=`hostname`
	einfo
	einfo ""
	einfo "Xaraya requires either mySQL or postgreSQL to run"
	einfo
	einfo "Once/If you have a database installed proceed to"
	einfo "http://$HOSTNAME/xaraya/install.php"
	einfo
	einfo
}
