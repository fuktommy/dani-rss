<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet href="/atomfeed.xsl" type="text/xsl"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>dアニメストアニコニコ支店のシリーズ一覧</title>
  <link rel="self" href="{$config.site_top}" />
  <link rel="alternate" href="{$config.series_list_url}" type="text/html"/>
  <updated>{if $serieses}{$serieses[0]->date|atom_date_format}{else}{$smarty.now|atom_date_format}{/if}</updated>
  <generator>https://github.com/fuktommy/dani-rss</generator>
  <id>tag:fuktommy.com,2017:dani.rss</id>
  <icon>{$config.site_top}/favicon.ico</icon>
{foreach from=$serieses item=seriese}
  <entry>
    <title>{$seriese->title|mbtruncate:140}</title>
    <link rel="alternate" href="{$seriese->url}"/>
    <summary type="text">{$seriese->title}</summary>
    <published>{$seriese->date|atom_date_format}</published>
    <updated>{$seriese->date|atom_date_format}</updated>
    <id>tag:fuktommy.com,2020:/{$seriese->url}</id>
  </entry>
{/foreach}
</feed>
