<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

  <xsl:template match="xar:page-title">
    <xsl:processing-instruction name="php">
        <xsl:choose>
          <xsl:when test="@title">
            <xsl:text>$title='</xsl:text>
            <xsl:value-of select="@title"/>
            <xsl:text>';</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>$title=xarTplGetPageTitle();</xsl:text>
          </xsl:otherwise>
        </xsl:choose>

        <xsl:choose>
          <xsl:when test="@module">
            <xsl:text>$module='</xsl:text>
            <xsl:value-of select="@module"/>
            <xsl:text>';</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>$module=ucwords(xarMod::getDisplayName());</xsl:text>
          </xsl:otherwise>
        </xsl:choose>

        <xsl:choose>
          <xsl:when test="@separator">
            <xsl:text>$separator='</xsl:text>
            <xsl:value-of select="@separator"/>
            <xsl:text>';</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>$separator=xarModVars::get('themes', 'SiteTitleSeparator');</xsl:text>
          </xsl:otherwise>
        </xsl:choose>

        <xsl:choose>
          <xsl:when test="@order">
            <xsl:text>$order='</xsl:text>
            <xsl:value-of select="@order"/>
            <xsl:text>';</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>$order='xarModVars::get('themes', 'SiteTitleOrder')';</xsl:text>
          </xsl:otherwise>
        </xsl:choose>

        <xsl:text>switch(strtolower($order)) {
            case 'sp':
                echo xarModVars::get('themes', 'SiteName') . $separator . $title;
            break;
            case 'mps':
                echo $module . $separator . $title . $separator .  xarModVars::get('themes', 'SiteName');
            break;
            case 'pms':
                echo $title . $separator .  $module . $separator . xarModVars::get('themes', 'SiteName');
            break;
            case 'to':
                echo $title;
            break;
            default:
                echo xarModVars::get('themes', 'SiteName') . $separator . $module . $separator . $title;
            break;
        }</xsl:text>
    </xsl:processing-instruction>
  </xsl:template>

</xsl:stylesheet>