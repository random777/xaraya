<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- Identity transform, but "flatten" xincludes -->

<xsl:output method="xml" indent="yes"/>
<xsl:preserve-space elements="*"/>

<xsl:template match="xi:include" xmlns:xi="http://www.w3.org/2001/XInclude">
  <xsl:apply-templates select="document(@href)"/>
</xsl:template>

<!-- identity transform -->

<xsl:template match="/ | node() | @* | comment() | processing-instruction()">
  <xsl:copy>
    <xsl:apply-templates select="@* | node()"/>
  </xsl:copy>
</xsl:template>

</xsl:stylesheet>