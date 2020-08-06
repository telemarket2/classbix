<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" prefix="og: http://ogp.me/ns#" lang="<?php echo I18n::getLocale(); ?>">
	<head>
		<?php echo Config::getCustomHeader($meta, '<link href="' . Config::urlAssets() . 'css/front.min.css?v=2020.04.28" rel="stylesheet" type="text/css" media="all" />'); ?>
	</head>
	<body class="<?php echo Theme::getTheme()->option('theme_presets', false, true); ?> layout_frontend">
		<div class="wrap">			
			<?php echo Language::htmlLanguage(); ?>
			<div class="header">
				<div class="c1">
					<?php
					$logo = Theme::getTheme()->option('logo', false, true);
					if($logo)
					{
						echo '<h1 class="logo"><a href="' . Language::urlHome() . '">
								<img src="' . Theme::getTheme()->uploadUrl($logo) . '" alt="' . Config::option('site_title') . '" />
								</a></h1>';
					}
					else
					{
						echo '<h1><a href="' . Language::urlHome() . '">' . Config::option('site_title') . '</a></h1>';
					}

					if(Config::option('site_description'))
					{
						echo '<p>' . View::escape(Config::option('site_description')) . '</p>';
					}
					?>
				</div>
				<div class="c2">
					<?php
					if(AuthUser::isLoggedIn(false))
					{
						// logged in user
						echo '<p>' . View::escape(AuthUser::$user->email) . ' | 
							<a href="' . Language::get_url('admin/') . '">' . __('My account') . '</a> | 
							<a href="' . Language::get_url('login/logout/') . '">' . __('Log out') . '</a></p>';
					}
					else
					{
						// not logged in user
						// logged in user
						echo '<p><a href="' . Language::get_url('admin/') . '" rel="nofollow">' . __('My account') . '</a></p>';
					}
					?>
					<a href="<?php echo Ad::urlPost($selected_location, $selected_category); ?>" class="button primary big post_listing" rel="nofollow"><?php echo View::escape(Config::optionElseDefault('site_button_title', __('Post ad')))  ?></a>
				</div>
				<div class="clear"></div>
			</div>			
			<?php echo Widget::renderThemeLocation('content_top', $this->vars, '<div class="{id} wide" role="complementary">{content}</div>'); ?>
			<?php echo Widget::renderThemeLocation('content_left', $this->vars, '<div class="{id} narrow" role="complementary">{content}</div>'); ?>
			<div class="content<?php
			echo ' has' . (Widget::isRendered('content_left', $this->vars) ? '_left' : '')
			. (Widget::isRendered('content_right', $this->vars) ? '_right' : '') . '_sidebar';
			?>" role="main">
					 <?php Config::renderBreadcrumb($breadcrumb, ' / ') ?>
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
					if(strlen(trim(Config::scriptName())) && !Config::option('powered_by_hide_front'))
					{
						echo ' - ' . __('powered by {name} {num}', array(
							'{name}' => Config::scriptName(),
							'{num}' => ''
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