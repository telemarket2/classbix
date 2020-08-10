<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="/">
		<html>
			<head>
				<style type="text/css">
					body {
					font-family:"Lucida Grande","Lucida Sans Unicode",Tahoma,Verdana;
					font-size:12px;
					line-height: 18px;
					color:#333;
					margin:0;
					padding:10px;
					text-align:left;				
					}					
					ol li{border-bottom:dotted 1px #ccc;padding:5px;}
					ol li:after {
					content: " "; /* Older browser do not support empty content */
					visibility: hidden;
					display: block;
					height: 0;
					clear: both;
					}
					ol li:nth-child(odd){background-color:#f9f9f9;}
					ol li:hover{background-color:#fcfcfc;}
					ol li img{float:left;margin-right:5px;}
					ol li h2{margin:0;}
					#footer {
					padding:2px;
					margin:10px;
					font-size:8pt;
					color:gray;
					}					
					#footer a {color:gray;}				
					a {color:#06c;}
				</style>
			</head>
			<body>
				<h1>
					<a href="{rss/channel/link}">
						<xsl:value-of select="rss/channel/title" />
					</a>
				</h1>
				<p>
					<xsl:value-of select="rss/channel/description" />
				</p>
				<ol>
					<xsl:for-each select="rss/channel/item">
						<li>
							<h2>
								<a href="{link}">
									<xsl:value-of select="title" />
								</a>
							</h2>
							<p>
								<xsl:value-of select="description" disable-output-escaping="yes" />								
							</p>
						</li>
					</xsl:for-each>
				</ol>
				<div id="footer">Generated with <a href="http://classibase.com/" title="Classifieds-Software">ClassiBase</a>.</div>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>