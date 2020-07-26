<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Themes') ?></h1>
<p class="tabs_static">
	<a href="<?php echo Language::get_url('admin/themes/') ?>"><?php echo __('Manage Themes'); ?></a>
	<span class="active"><?php echo __('Install Themes'); ?></span>
</p>
<?php
$available_themes_html = '';

foreach ($available_theme_updates->themes_new as $new_theme)
{
	$theme_id = $new_theme->id;
	$available_themes_html .= '<div class="col col-12 lg-col-3 md-col-4 sm-col-6 p1 theme" id="' . $theme_id . '">
			<a href="' . View::escape($new_theme->info_url) . '" target="_blank">
				<img src="' . View::escape($new_theme->screenshot) . '" width="300" height="225" alt="' . View::escape($new_theme->title) . '" />
			</a>
			<h3><a href="#info" class="info" title="' . View::escape(__('Info')) . '">' . View::escape($new_theme->title) . ' <i class="fa fa-info" aria-hidden="true"></i></a></h3>			
			<div class="info display-none">
				<p>' . __('Version') . ': ' . View::escape($new_theme->version) . '</p>
				<p>' . View::escape($new_theme->description) . '</p>
				<p><a href="' . View::escape($new_theme->info_url) . '" target="_blank">' . __('visit site') . '</a></p>
			</div>
			<p>
				<span class="button-group button-group-block">
					<a href="' . Language::get_url('admin/updateTheme/' . $theme_id . '/') . '" class="button green">' . __('Install') . '</a>
					<a href="' . View::escape($new_theme->demo_url) . '" class="button" target="_blank">' . __('Demo') . '</a>
				</span>
			</p>			
		</div>';
}

if ($available_themes_html)
{
	echo '<div class="other_themes">'
	. '<h2>' . __('New Themes') . '</h2>'
	. '<div class="celarfix mxn1">'
	. $available_themes_html
	. '</div>'
	. '</div>';
}
else
{
	echo '<p>' . __('No new themes available') . '</p>';
}
?>

<script type="text/javascript">
	addLoadEvent(function () {
		$('.other_themes div.info.display-none').hide().removeClass('display-none');
		$('a.info').click(function () {
			var $me = $(this);
			var $parent = $me.parents('.theme:first');
			$('div.info', $parent).slideToggle("fast");
			return false;
		});

	});
</script>

