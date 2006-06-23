<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

<xsl:output omit-xml-declaration="yes"/>
<xsl:template match="/">
  <t>
    <xsl:apply-templates/>
  </t>
</xsl:template>

<xsl:template match="rfc">
  RFC-<xsl:value-of select="@number"/> : <xsl:value-of select="front/title"/>
<xsl:text>
</xsl:text>
<eref><xsl:attribute name="target">http://www.xaraya.com/documentation/rfcs/rfc<xsl:value-of select="@number"/>.pdf</xsl:attribute>[PDF]
</eref>
<eref><xsl:attribute name="target">http://www.xaraya.com/documentation/rfcs/rfc<xsl:value-of select="@number"/>.html</xsl:attribute>[HTML]
</eref>
<eref><xsl:attribute name="target">http://www.xaraya.com/documentation/rfcs/rfc<xsl:value-of select="@number"/>.txt</xsl:attribute>[TXT]
</eref>
</xsl:template>
</xsl:stylesheet>

