<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
		xmlns:fo="http://www.w3.org/1999/XSL/Format" version="1.0">
<xsl:output method="xml" indent="yes"/>

<xsl:template match="/">
 <fo:root>
  <xsl:call-template name="page-setup"/>
  <xsl:apply-templates/>
 </fo:root>
</xsl:template>

<xsl:template name="page-setup">
  <fo:layout-master-set>
   <fo:simple-page-master master-name="main_page"
			  page-height="8.5in"
			  page-width="11in"
			  margin-top=".5in"
			  margin-bottom=".5in"
			  margin-right=".5in"
			  margin-left=".5in">
    <fo:region-before extent=".3in" 
		      font-size="8pt"/>
    <fo:region-body margin-top=".3in"
		    column-count="4"
		    column-gap="6pt"/> 
   </fo:simple-page-master>
  </fo:layout-master-set>
</xsl:template>

<xsl:template match="/section">
 <fo:page-sequence master-reference="main_page">
  <fo:static-content flow-name="xsl-region-before">
   <fo:block text-align="center">
     <xsl:text>Xaraya DocBook Element Quick Reference Card</xsl:text>
   </fo:block>
   <fo:block line-height=".2pt">
     <fo:leader leader-alignment="reference-area"
		leader-pattern="rule" />
   </fo:block>
  </fo:static-content>
  <fo:flow flow-name="xsl-region-body"
	   font-size="10pt">
   <xsl:apply-templates select="section"/>
  </fo:flow>
 </fo:page-sequence>
</xsl:template>

<xsl:template match="section/section">
 <fo:block>
  <xsl:apply-templates/>
 </fo:block>
</xsl:template>

<xsl:template match="title">
 <fo:block font-weight="bold"
	   background-color="#E8E8E8"
	   keep-with-next="always">
  <xsl:apply-templates/>
 </fo:block>
</xsl:template>

<xsl:template match="simplelist">
 <fo:block text-indent=".2in"
	   space-after="4pt"
	   keep-together="always">
  <xsl:apply-templates/>
 </fo:block>
</xsl:template>

<xsl:template match="member">
 <xsl:apply-templates/>
 <xsl:if test="following-sibling::member">
  <xsl:text>, </xsl:text>
 </xsl:if>
</xsl:template>

</xsl:stylesheet>