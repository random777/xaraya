<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
  xmlns:ddl="http://xaraya.com/2007/schema" >

  <!-- we are outputting text -->
  <xsl:output method="text" />

  <!-- remove all the whitespace -->
  <xsl:strip-space elements="*"/>

  <!--
      We probably want to specify parameters at some point like:
      - vendor      - generate ddl compatible with $vendor backend
      - version     - generate ddl compatible with $vendor-$version backend
      - drop4create - drop tables before creating them
      - createdb    - create the database too
      - tableprefix - self explanatory
      - etc.
  -->
  <xsl:param name="vendor"  />
  <xsl:param name="version" />
  <xsl:param name="dbcreate"/>
  <xsl:param name="drop4create"/>
  <xsl:param name="tableprefix"/>

  <!-- Variables, xslt style -->
  <xsl:variable name="CR">
<xsl:text>
</xsl:text>
  </xsl:variable>

  <!-- These two help with implenting case translations -->
  <xsl:variable name="smallcase" select="'abcdefghijklmnopqrstuvwxyz'" />
  <xsl:variable name="uppercase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" />

  <!-- Stuff we ignore for now -->
  <!-- Supress things we dont want or havent gotten around to yet -->
  <xsl:template match="text()"/>
  <xsl:template match="ddl:schema/ddl:description"/>
  <xsl:template match="ddl:index/ddl:description"/>
  <xsl:template match="ddl:prototypes"/>


  <!-- File header -->
  <xsl:template name="topheader">
    <xsl:param name="remarks"/>
/* ---------------------------------------------------------------------------
 * Model generated from: TODO
 * Name                : <xsl:value-of select="ddl:schema/@name"/>
 * Vendor              : <xsl:value-of select="$vendor"/>
 * Date                : TODO
 * Remarks:            :
 *   <xsl:value-of select="$remarks"/>
 */
</xsl:template>

<!-- Context sensitive header, reacts on name and element-name -->
<xsl:template name="dynheader">
/* ---------------------------------------------------------------------------
 * <xsl:value-of select="local-name()"/>: <xsl:value-of select="@name" />
 */
</xsl:template>

<!-- Easy TODO inclusion -->
<xsl:template name="TODO">
    <xsl:text>/* TODO: Template for: </xsl:text>
    <xsl:value-of select="local-name()"/>
    <xsl:text> </xsl:text>
    <xsl:value-of select="@name"/>
    <xsl:text> handling (vendor: </xsl:text>
    <xsl:value-of select="$vendor"/>
    <xsl:text>) */
</xsl:text>
</xsl:template>

  <!-- Default create database statement -->
  <xsl:template match="ddl:schema">
      <xsl:text>CREATE DATABASE </xsl:text><xsl:value-of select="@name"/>;
  <xsl:apply-templates/>
  </xsl:template>

  <!--  @todo make this a generic template? -->
  <xsl:key name="columnid" match="ddl:table/ddl:column" use="@id"/>
  <xsl:template name="columnrefscsv">
    <xsl:for-each select="ddl:column">
      <xsl:value-of select="key('columnid',@ref)/@name"/>
      <xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if>
    </xsl:for-each>
  </xsl:template>

  <!-- Index base create is pretty portable
       @todo put these back together?
  -->
  <xsl:template match="ddl:table/ddl:constraints/ddl:index">
    <xsl:text>CREATE INDEX </xsl:text>
    <xsl:value-of select="@name"/> ON 
    <xsl:if test="$tableprefix != ''">
      <xsl:value-of select="$tableprefix"/>
      <xsl:text>_</xsl:text>
    </xsl:if>
    <xsl:value-of select="../../@name"/> (<xsl:call-template name="columnrefscsv"/>);
  </xsl:template>

  <xsl:template match="ddl:table/ddl:constraints/ddl:unique">
    <xsl:text>CREATE UNIQUE INDEX </xsl:text>
    <xsl:value-of select="@name"/> ON 
    <xsl:if test="$tableprefix != ''">
      <xsl:value-of select="$tableprefix"/>
      <xsl:text>_</xsl:text>
    </xsl:if>
    <xsl:value-of select="../../@name"/> (<xsl:call-template name="columnrefscsv"/>);
  </xsl:template>

  <!-- Primary key creation -->
  <xsl:template match="ddl:table/ddl:constraints/ddl:primary">
    <xsl:text>ALTER TABLE </xsl:text>
    <xsl:if test="$tableprefix != ''">
      <xsl:value-of select="$tableprefix"/>
      <xsl:text>_</xsl:text>
    </xsl:if>
    <xsl:value-of select="../../@name"/>
    <xsl:for-each select="ddl:column">
      <xsl:for-each select="key('columnid',@ref)">
        <xsl:if test="@auto = 'true'">
          <xsl:text> CHANGE COLUMN </xsl:text>
          <xsl:value-of select="@name"/><xsl:text> </xsl:text>
          <xsl:value-of select="@name"/><xsl:text> </xsl:text>
          <!-- <xsl:call-template name="columnattributes"> -->
          <!--   <xsl:with-param name="ignoreauto">false</xsl:with-param> -->
          <!-- </xsl:call-template> -->
          <xsl:text>, </xsl:text>
        </xsl:if>
      </xsl:for-each>
    </xsl:for-each>
    <xsl:text> ADD PRIMARY KEY (</xsl:text><xsl:call-template name="columnrefscsv"/>);
  </xsl:template>
</xsl:stylesheet>
