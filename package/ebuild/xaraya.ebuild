# Copyright 1999-2003 , Xaraya Development Team
# Distributed under the terms of the GNU General Public License, v2 or later
# $Header: $


DESCRIPTION="Xaraya Application Framework"
HOMEPAGE="http://www.xaraya.com"
SLOT="0"
LICENSE="GPL-2"
KEYWORDS="~x86 ~ppc ~sparc ~alpha ~arm ~hppa ~mips"

IUSE=""
DEPEND=""
RDEPEND="virtual/php
	 net-www/apache"

inherit webapp-apache

S="${WORKDIR}/${P}"

SRC_URI="mirror://sourceforge/xaraya/${P}-full.tar.gz"

pkg_setup() {
	webapp-detect
}


src_unpack() {
	unpack ${A}
}

src_install() {
	cd ${S}
	dodir ${HTTPD_ROOT}/xaraya
	cp -r html/* ${D}/${HTTPD_ROOT}/xaraya
	dodoc CREDITS.txt INSTALL.txt LICENSE.txt INSTALL.txt XarayaGuide.txt releaselog-*
}

pkg_postinst() {
	HOSTNAME=`hostname`
	einfo ""
	einfo "Xaraya requires either mySQL or postgreSQL to run."
	einfo ""
	einfo "Once/If you have a database and webserver installed and started, proceed to"
	einfo "http://$HOSTNAME/xaraya/install.php"
	einfo ""
}
