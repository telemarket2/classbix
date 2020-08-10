<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php echo I18n::getLocale(); ?>">
	<head>
		<?php
		$main_links = '			
			<link href="' . URL_ASSETS . 'css/normace.min.css?v=v8.0.2" rel="stylesheet" type="text/css" />
			<link href="' . URL_ASSETS . 'css/font-awesome.min.css?v=v4.7" rel="stylesheet" type="text/css" />
			<link href="' . URL_ASSETS . 'css/screen.min.css?v=' . Config::VERSION . '" rel="stylesheet" type="text/css" />
			<script src="' . URL_ASSETS . 'js/jquery-1.12.4.min.js"	type="text/javascript"></script>			
			<script src="' . URL_ASSETS . 'js/admin.min.js?v=' . Config::VERSION . '" type="text/javascript"></script>';
		echo Config::getCustomHeader($meta, $main_links);
		?>
	</head>
	<body class="<?php echo Config::getBodyClass($meta, $this->vars, 'login layout_login e_jqd'); ?>">
		<div class="content">
			<?php echo Config::cookieJsWarning(); ?>
			<!-- content --> 
			<?php echo $content_for_layout; ?>
			<!-- end content --> 
		</div>
		<?php echo Language::htmlLanguage('login/'); ?>
		<p><a href="<?php echo Language::get_url() ?>"><?php echo Language::get_url() ?></a></p>

		<!-- javascript at end for faster loads -->
		<?php echo Config::getCustomFooter($meta); ?>
		<!-- javascript END -->	
		<?php display_benchmark(); ?>
	</body>
</html>