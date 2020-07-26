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
		echo Config::getCustomHeader($meta, $main_links, __('Admin panel'));
		?>
	</head>
	<body class="<?php echo Config::getBodyClass($meta, $this->vars, 'layout_backend e_jqd'); ?>">
		<div class="wrap">
			<div class="header clearfix">

				<a href="#menu" class="popup_sidebar left p1 md-hide lg-hide block" title="<?php echo View::escape(__('Menu')) ?>"><i class="fa fa-fw fa-bars" aria-hidden="true"></i></a> 
				<a href="<?php echo Language::get_url() ?>" class="left p1 block" title="<?php echo __('View site') ?>"><i class="fa fa-fw fa-arrow-left" aria-hidden="true"></i></a>				
				<h1<?php echo isset($meta->title_sub) ? ' class="has_sub"' : ''; ?>>
					<?php
					/* title and subtitle */
					if (!isset($meta->title))
					{
						$meta->title = AuthUser::hasPermission(User::PERMISSION_MODERATOR) ? __('Admin panel') : __('User panel');
					}
					if (!isset($meta->title_url))
					{
						$meta->title_url = Language::get_url('admin/');
					}
					?>
					<a href="<?php echo $meta->title_url ?>"><?php
						echo $meta->title;
						if (isset($meta->title_sub))
						{
							echo ' <small class="muted">' . $meta->title_sub . '</small>';
						}
						?></a>
				</h1>
			</div>
			<div class="clearfix">
				<div class="col col-8 md-col-2">
					<!-- sidebar --> 
					<?php echo new View('admin/sidebar', $this->vars) ?>
					<!-- end sidebar --> 
				</div>
				<div class="content col col-12 md-col-10 p2">
					<?php Config::renderBreadcrumb($breadcrumb, '<span class="arrow_right"></span>') ?>
					<?php echo Config::cookieJsWarning(); ?>
					<!-- content --> 
					<?php echo $content_for_layout; ?>
					<!-- end content -->
				</div>
			</div>
			<?php
			//echo Language::htmlLanguage('admin/');
			?>
			<div class="footer muted p1">
				<?php
				if (strlen(trim(Config::scriptName())) && (!Config::option('powered_by_hide_front') || AuthUser::hasPermission(User::PERMISSION_ADMIN)))
				{
					echo __('powered by {name} {num}', array(
						'{name}' => Config::scriptName(),
						'{num}'	 => Config::option('site_version')
					));
				}
				?>
			</div>
			<?php display_benchmark(); ?>
			<?php echo Config::getCustomFooter($meta); ?>
		</div>
	</body>
</html>