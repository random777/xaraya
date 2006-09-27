<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:output method="text"/>

<xsl:template match="rfc">
  <xsl:text>RFC-</xsl:text>
  <xsl:value-of select="@number"/>
  <xsl:text> : </xsl:text>
  <xsl:value-of select="front/title"/>
  <xsl:text>
</xsl:text>
<xsl:for-each select="/rfc/front/author">
  <xsl:text>    </xsl:text>
  <xsl:value-of select="@fullname"/>
  <xsl:text>
</xsl:text>
</xsl:for-each>
</xsl:template>
</xsl:stylesheet>
