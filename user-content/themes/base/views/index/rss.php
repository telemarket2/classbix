<?php

echo '<?xml version="1.0" encoding="UTF-8" ?>
	<?xml-stylesheet type="text/xsl" href="' . URL_ASSETS . 'js/rss.xsl"?>
			<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
				<channel>
					<title>' . Config::option('site_title') . '</title>
				    <link>' . $rss_link . '</link>'
 . ($description ? '<description>' . View::escape($description) . '</description>' : '')
 . '<atom:link href="' . $rss_url . '" rel="self" type="application/rss+xml" />
				';

foreach($ads as $ad)
{
	$link = Ad::url($ad);
	if($ad->Adpics)
	{
		$thumb = '<a href="' . $link . '" title="'
				. View::escape(Ad::getTitle($ad)) . '" rel="nofollow"><img style="float:left;border:0px;" src="'
				. Adpics::imgThumb($ad->Adpics) . '" alt="'
				. View::escape(Ad::getTitle($ad)) . '"/></a> ';
	}
	else
	{
		$thumb = '';
	}

	echo '<item>
					<title><![CDATA[' . Ad::getTitle($ad) . ']]></title>
					<link>' . $link . '</link>
					<guid isPermaLink="false">' . $link . '</guid>
					<description><![CDATA[' . $thumb . View::escape($ad->description) . ' ' . Ad::formatLocationCategoryLink($ad) . ']]></description>		
					<pubDate>' . date('r', $ad->added_at) . '</pubDate>
				</item>
				';
}

echo '</channel>
			</rss>';
