<xsl:transform xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                version="1.0"
                xmlns:ed="http://greenbytes.de/2002/rfcedit"
>

<xsl:template match="ed:del" />

<xsl:template match="ed:issue[@status='closed']" />

<xsl:template match="ed:ins">
  <xsl:apply-templates />
</xsl:template>

<xsl:template match="ed:replace">
  <xsl:apply-templates />
</xsl:template>


<!-- rules for identity transformations -->

<xsl:template match="node()|@*"><xsl:copy><xsl:apply-templates select="node()|@*" /></xsl:copy></xsl:template>

<xsl:template match="/">
	<xsl:copy><xsl:apply-templates select="node()" /></xsl:copy>
</xsl:template>

</xsl:transform>