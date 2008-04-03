<?xml version="1.0"?>
<!--
  XSLT to create a DDL fragment which represents the same
  information as the ddl XML
-->
<xsl:stylesheet version="1.0" 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
  xmlns:ddl="http://xaraya.com/2007/schema">
  <!--
      Import common templates, we use import instead of include so the
      imported templates get a lower priority than the ones in this file,
      giving the ability here to override the imports
  -->
  <xsl:import href="xml2ddl-base.xsl"/>
  
  <!-- we are outputting text -->
  <xsl:output method="text" />
  
<!-- Things to do before we start handling elements -->
<xsl:template match="/">
  <xsl:call-template name="topheader">
    <xsl:with-param name="remarks">
    - reference: http://dev.mysql.com/doc/refman/5.0/en/index.html
    - assuming for now we want to drop before create
    </xsl:with-param>
  </xsl:call-template>

  <xsl:text>/* Disable foreign key checks until we're done */</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:text>SET FOREIGN_KEY_CHECKS = 0;</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:apply-templates />
  <xsl:text>SET FOREIGN_KEY_CHECKS = 1;</xsl:text>
  <xsl:value-of select="$CR"/>
</xsl:template>

<!-- Default create database statement -->
<xsl:template match="ddl:schema">
  <xsl:text>/* Create the database if not already there */</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:text>CREATE DATABASE IF NOT EXISTS </xsl:text><xsl:value-of select="@name"/>;
  <xsl:value-of select="$CR"/>

  <xsl:text>/* Satisfy all tables */</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:apply-templates />
  <xsl:value-of select="$CR"/>
</xsl:template>

<!-- Create a table -->
<xsl:template match="ddl:table">
  <xsl:text>/* Table: </xsl:text><xsl:value-of select="@name"/>
  <xsl:text> */</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:text>CREATE TABLE IF NOT EXISTS </xsl:text>
  <xsl:value-of select="@name"/>
  <xsl:text> (</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:apply-templates select="ddl:column"/>
  <xsl:text>);</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:apply-templates select="ddl:constraints"/>
  <xsl:value-of select="$CR"/>
</xsl:template>

<!-- Create a column -->
<xsl:template match="ddl:column">
  <xsl:text>  </xsl:text>
  <xsl:value-of select="@name"/>
  <xsl:text> </xsl:text>
  <xsl:apply-templates select="ddl:number|ddl:text|ddl:binary|ddl:boolean|ddl:time"/>
  <xsl:text> </xsl:text>
  <xsl:if test="@required = 'true'"> NOT NULL</xsl:if>
  <xsl:if test="*[@defaultvalue]"> DEFAULT '<xsl:value-of select="*/@defaultvalue"/>'</xsl:if>
  <!-- Auto increment looks odd here, but look at the spec at mysql, it *is* correct -->
  <xsl:if test="@auto ='true'"> AUTO_INCREMENT </xsl:if>
  <xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if>
  <xsl:value-of select="$CR"/>
</xsl:template>

<!-- Number datatype -->
<xsl:template match="ddl:number">
  <xsl:text>INTEGER</xsl:text>
  <xsl:if test="@size != ''">(<xsl:value-of select="@size"/>)</xsl:if>
</xsl:template>

<!-- Binary datatype -->
<xsl:template match="ddl:binary">
  <xsl:text>BLOB</xsl:text>
</xsl:template>

<!-- Text datatype -->
<xsl:template match="ddl:text">
  <xsl:choose>
    <xsl:when test="@size != ''">
        <xsl:text>VARCHAR(</xsl:text><xsl:value-of select="@size"/><xsl:text>)</xsl:text>
    </xsl:when>
    <xsl:otherwise>
      <xsl:text>TEXT</xsl:text>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- Boolean datatype -->
<xsl:template match="ddl:boolean">
  <xsl:text>BOOLEAN</xsl:text>
</xsl:template>

<!-- Time datatype -->
<xsl:template match="ddl:time">
  <xsl:text>TIME</xsl:text>
</xsl:template>


<!--
    Constraints handling
    
    The columns in the constraints are specified referencing their id
    attribute. This means we need an index over that attribute so we can
    construct a node and access the rest of the column properties through that
    index.
    
    @todo foreign key constraints
-->
<xsl:key name="columns" match="ddl:column" use="@id"/>

<xsl:template match="ddl:constraints">
  <xsl:text>ALTER TABLE </xsl:text>
  <xsl:value-of select="../@name"/>
  <xsl:value-of select="$CR"/>
  <xsl:apply-templates select="ddl:primary|ddl:unique|ddl:index"/>
  <xsl:text>;</xsl:text>
</xsl:template>

<xsl:template match="ddl:primary|ddl:unique|ddl:index">
  <xsl:text>  ADD </xsl:text>
  <xsl:choose>
    <xsl:when test="name() = 'index'"/>
    <xsl:otherwise>
      <xsl:value-of select="translate(name(), $smallcase, $uppercase)" />
      <xsl:text> </xsl:text>
    </xsl:otherwise>
  </xsl:choose>
  <xsl:text>KEY(</xsl:text>
  
  <!-- for every column in here, add the actual name for the column -->
  <xsl:for-each select="ddl:column">
    <xsl:value-of select="key('columns',@ref)/@name"/>
    <xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if>
  </xsl:for-each>
  <xsl:text>)</xsl:text>
  <xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if>
  <xsl:value-of select="$CR"/>
</xsl:template>

<!-- Treat a description as comment by default -->
<xsl:template match="ddl:description">
  <xsl:text>/* </xsl:text>
  <xsl:value-of select="."/>
  <xsl:text> */</xsl:text>
  <xsl:value-of select="$CR"/>
</xsl:template>

<!-- Supress things we dont want or havent gotten around to yet -->
<xsl:template match="text()"/>
<xsl:template match="ddl:table/ddl:description"/>

</xsl:stylesheet>