<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" prefix="og: http://ogp.me/ns#" lang="<?php echo I18n::getLocale(); ?>">
	<head>
		<?php
		$main_links = '<link href="' . URL_ASSETS . 'css/normace.min.css?v=v8.0.2" rel="stylesheet" type="text/css" />'
				. '<link href="' . URL_ASSETS . 'css/font-awesome.min.css?v=v4.7" rel="stylesheet" type="text/css" />'
				. '<link href="' . Config::urlAssets() . 'css/front.css?v=' . Theme::getVersion() . '" rel="stylesheet" type="text/css" />';
		echo Config::getCustomHeader($meta, $main_links);
		?>
	</head>
	<body class="<?php echo Config::getBodyClass($meta, $this->vars, Theme::getTheme()->option('theme_presets', false, true) . ' layout_frontend'); ?>">
		<div class="wrap">
			<div class="header clearfix mxn1">
				<?php
				$top_search = Widget::renderThemeLocation('top_search', $this->vars, '<div class="col col-12 md-col-4 p1 {id} sticky">{content}</div>');


				if ($top_search)
				{
					$top_class = 'md-col-4';
				}
				else
				{
					$top_class = 'md-col-6';
				}
				?>

				<div class="col col-12 p1 <?php echo $top_class; ?>">
					<a href="<?php echo Language::get_url('admin/'); ?>" 
					   class="button link md-hide lg-hide left" 
					   rel="nofollow" 
					   data-jq-dropdown="#jq-dropdown-sidemenu" 
					   title="<?php echo View::escape(__('Menu')) ?>"><i class="fa fa-bars" aria-hidden="true"></i></a>
					<a href="<?php echo Ad::urlPost($selected_location, $selected_category); ?>" 
					   class="button link md-hide lg-hide right"
					   title="<?php echo View::escape(Config::optionElseDefault('site_button_title', __('Post ad'))); ?>"><i class="fa fa-plus" rel="nofollow" aria-hidden="true"></i></a>
					   <?php
					   $logo = Theme::getTheme()->option('logo', false, true);
					   if ($logo)
					   {
						   echo '<h1 class="logo">'
						   . '<a href="' . Language::urlHome() . '">'
						   . '<img src="' . Theme::getTheme()->uploadUrl($logo) . '" alt="' . Config::option('site_title') . '" />'
						   . '</a>'
						   . '</h1>';
					   }
					   else
					   {
						   echo '<h1><a href="' . Language::urlHome() . '">' . Config::option('site_title') . '</a></h1>';
					   }

					   if (Config::option('site_description'))
					   {
						   echo '<p class="site_description xs-hide sm-hide md-hide"><small>' . View::escape(Config::option('site_description')) . '</small></p>';
					   }
					   ?>

				</div>
				<?php echo $top_search; ?>
				<div class="col col-12 <?php echo $top_class; ?> p1 right-align sm-hide xs-hide">

					<a href="<?php echo Ad::urlPost($selected_location, $selected_category); ?>" class="button primary big post_listing" rel="nofollow"><i class="fa fa-plus" aria-hidden="true"></i> <?php echo View::escape(Config::optionElseDefault('site_button_title', __('Post ad'))) ?></a>

					<?php
					if (AuthUser::isLoggedIn(false))
					{
						// logged in user
						/* echo '<p>' . View::escape(AuthUser::$user->email) . ' | 
						  <a href="' . Language::get_url('admin/') . '">' . __('My account') . '</a> |
						  <a href="' . Language::get_url('login/logout/') . '">' . __('Log out') . '</a></p>'; */
						echo '<a href="' . Language::get_url('admin/') . '" class="button" data-jq-dropdown="#jq-dropdown-sidemenu"><i class="fa fa-bars" aria-hidden="true"></i> ' . __('My account') . '</a>';
					}
					else
					{
						// not logged in user
						// logged in user
						echo '<div class="button-group">'
						. '<a href="' . Language::get_url('admin/') . '" rel="nofollow" class="button">' . __('Log in') . '</a>'
						. '<button class="button" data-jq-dropdown="#jq-dropdown-sidemenu" title="' . View::escape(__('Menu')) . '"><i class="fa fa-bars" aria-hidden="true"></i></button>'
						. '</div>';
					}
					?>
				</div>
				<?php
				// render user menu 
				$sidemenu = Config::_sidemenu();
				?>
				<!-- Dropdown menu -->
				<div id="jq-dropdown-sidemenu" class="jq-dropdown">
					<?php
					echo Config::renderMenu(array(
						'menu_items'	 => $sidemenu,
						'menu_selected'	 => $menu_selected,
						'pattern_menu'	 => '<ul class="jq-dropdown-menu">{menu}</ul>',
					));
					?>
				</div>
				<!-- Dropdown menu END -->

			</div>		
			<?php echo Widget::renderThemeLocation('content_top', $this->vars, '<div class="{id} wide" role="complementary">{content}</div>'); ?>
			<?php echo Widget::renderThemeLocation('content_left', $this->vars, '<div class="{id} narrow" role="complementary">{content}</div>'); ?>
			<div class="content<?php
			echo ' has' . (Widget::isRendered('content_left', $this->vars) ? '_left' : '')
			. (Widget::isRendered('content_right', $this->vars) ? '_right' : '') . '_sidebar';
			?>" role="main">
					 <?php Config::renderBreadcrumb($breadcrumb, ' / ') ?>
					 <?php echo Config::cookieJsWarning(); ?>
					 <?php Validation::getInstance()->messages(); ?>

				<?php echo Widget::renderThemeLocation('inner_top', $this->vars, '<div class="{id} wide" role="complementary">{content}</div>'); ?>
				<!-- content --> 
				<?php echo $content_for_layout; ?>
				<!-- end content --> 
				<?php echo Widget::renderThemeLocation('inner_bottom', $this->vars, '<div class="{id} wide" role="complementary">{content}</div>'); ?>
				<div class="clear"></div>
			</div>
			<?php echo Widget::renderThemeLocation('content_right', $this->vars, '<div class="{id} narrow" role="complementary">{content}</div>'); ?>
			<div class="clear"></div>
			<?php echo Widget::renderThemeLocation('content_bottom', $this->vars, '<div class="{id} wide" role="complementary">{content}</div>'); ?>
			<div class="footer"> 
				<div class="info">&copy; <a href="<?php echo Language::urlHome() ?>"><?php echo Config::option('site_title') ?></a>
					<?php
					if (strlen(trim(Config::scriptName())) && !Config::option('powered_by_hide_front'))
					{
						echo ' - ' . __('powered by {name} {num}', array(
							'{name}' => Config::scriptName(),
							'{num}'	 => ''
						));
					}
					?>					  
				</div>
			</div>

		</div>
		<!-- javascript at end for faster loads -->
		<script src="<?php echo URL_ASSETS ?>js/jquery-1.12.4.min.js"	type="text/javascript"></script>
		<script src="<?php echo URL_ASSETS ?>js/admin.min.js?v=<?php echo Config::VERSION ?>" type="text/javascript"></script>		
		<?php echo Config::getCustomFooter($meta); ?>
		<!-- javascript END -->
	</body>
</html>
<?php display_benchmark(); ?>