<?php echo new View('customize', $this->vars); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" prefix="og: http://ogp.me/ns#" lang="<?php echo I18n::getLocale(); ?>">
	<head>
		<?php echo Config::getCustomHeader($meta, '<link href="' . Config::urlAssets() . 'css/front.min.css?v=2020.04.28" rel="stylesheet" type="text/css" media="all" />'); ?>
	</head>
	<body class="<?php echo Theme::getTheme()->option('theme_presets', false, true); ?> layout_frontend">
		<div class="wrap">	
			<div class="login_controls">
				<?php
				if (AuthUser::isLoggedIn(false))
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
				$htmlLanguage = Language::htmlLanguage('', array(
							'wrap' => '<div class="top language">{LINK_SEL} <div class="language_other">{LINK_OTHER}</div></div>'
				));
				if ($htmlLanguage)
				{
					echo ' | ' . $htmlLanguage;
				}
				?>
			</div>
			<div class="header">
				<div class="c1">
					<?php
					$logo = Theme::getTheme()->option('logo', false, true);
					$logo_class = Theme::getTheme()->option('general_search_form_hide') ? '' : 'with_search';
					if ($logo)
					{
						echo '<h1 class="logo ' . $logo_class . '"><a href="' . Language::urlHome() . '">
								<img src="' . Theme::getTheme()->uploadUrl($logo) . '" alt="' . Config::option('site_title') . '" />
								</a></h1>';
					}
					else
					{
						echo '<h1 class="' . $logo_class . '"><a href="' . Language::urlHome() . '">' . Config::option('site_title') . '</a></h1>';
					}

					if (!Theme::getTheme()->option('general_search_form_hide'))
					{
						?>
						<!-- general search form -->
						<form action="<?php echo Language::get_url('search/') ?>" method="get" id="top_search_form">
							<div>
								<input type="text" name="q" id="q" value="<?php echo View::escape($_GET['q']); ?>" placeholder="<?php echo View::escape(__('What are you looking for?')); ?>" />
								<input type="submit" name="s" id="s" value="<?php echo View::escape(__('Search')); ?>" />
							</div>
						</form>
						<!-- general search form END -->
						<?php
					}
					?>
				</div>
				<div class="c2">
					<a href="<?php echo Ad::urlPost($selected_location, $selected_category); ?>" class="button primary big post_listing" rel="nofollow"><?php echo View::escape(Config::optionElseDefault('site_button_title', __('Post ad'))) ?></a>
				</div>
				<div class="clear"></div>
			</div>			
			<?php echo Widget::renderThemeLocation('content_top', $this->vars, '<div class="{id} wide" role="complementary">{content}</div>'); ?>
			<?php
			$_breadcrumb = Config::renderBreadcrumb($breadcrumb, ' / ', null, false);
			switch ($page_type)
			{
				case IndexController::PAGE_TYPE_AD:
					// add previous / next ad links
					// split breadcrumb to cells
					// previous next ads 
					$_prev_next = Ad::formatPrevNext($ad, array(
								'wrap'			 => '<p class="item_prev_next_simple">{BUTTON_PREV} {BUTTON_NEXT}</p>',
								'button_prev'	 => '<a href="{URL_PREV}" class="button button_prev" title="{TITLE_PREV}"> &larr; </a>',
								'button_next'	 => '<a href="{URL_NEXT}" class="button button_next" title="{TITLE_NEXT}"> &rarr; </a>'
					));
					if ($_prev_next)
					{
						echo '<div class="c1">' . $_breadcrumb . '</div><div class="c2">' . $_prev_next . '</div><div class="clear"></div>';
					}
					else
					{
						echo $_breadcrumb;
					}

					break;
				case IndexController::PAGE_TYPE_CATEGORY:
				// add simple page navigation 
				default :
					echo $_breadcrumb;
					break;
			}
			?>
			<?php
			if ($page_type !== IndexController::PAGE_TYPE_AD || Theme::getTheme()->option('display_sidebar_on_ad_page'))
			{
				echo Widget::renderThemeLocation('content_left', $this->vars, '<div class="{id} narrow" role="complementary">{content}</div>');
			}
			?>
			<div class="content<?php
			if ($page_type !== IndexController::PAGE_TYPE_AD || Theme::getTheme()->option('display_sidebar_on_ad_page'))
			{
				echo ' has' . (Widget::isRendered('content_left', $this->vars) ? '_left' : '')
				. (Widget::isRendered('content_right', $this->vars) ? '_right' : '') . '_sidebar';
			}
			?>" role="main">

				<?php Validation::getInstance()->messages(); ?>

				<?php echo Widget::renderThemeLocation('inner_top', $this->vars, '<div class="{id} wide" role="complementary">{content}</div>'); ?>
				<!-- content --> 
				<?php echo $content_for_layout; ?>
				<!-- end content --> 
				<?php echo Widget::renderThemeLocation('inner_bottom', $this->vars, '<div class="{id} wide" role="complementary">{content}</div>'); ?>
				<div class="clear"></div>
			</div>
			<?php
			if ($page_type !== IndexController::PAGE_TYPE_AD || Theme::getTheme()->option('display_sidebar_on_ad_page'))
			{
				echo Widget::renderThemeLocation('content_right', $this->vars, '<div class="{id} narrow" role="complementary">{content}</div>');
			}
			?>
			<div class="clear"></div>
			<?php echo Widget::renderThemeLocation('content_bottom', $this->vars, '<div class="{id} wide" role="complementary">{content}</div>'); ?>
			<div class="footer"> 
				<?php
				if (Config::option('site_description'))
				{
					echo '<p>' . View::escape(Config::option('site_description')) . '</p>';
				}
				?>
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
		<script>
			addLoadEvent(function () {
				columnizeCats('.content .widget_categories .widget_body ul:first,.content .widget_locations .widget_body ul:first');
			});
		</script>
		<?php echo Config::getCustomFooter($meta); ?>
		<!-- javascript END -->
	</body>
</html>
<?php display_benchmark(); ?>