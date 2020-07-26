<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Themes') ?></h1>

<?php
$current_theme_id = Theme::currentThemeId();
?>
<p class="tabs_static">
	<span class="active"><?php echo __('Manage Themes'); ?></span>
	<a href="<?php echo Language::get_url('admin/themesInstall/') ?>"><?php echo __('Install Themes'); ?></a>
</p>


<div class="current_theme clearfix mxn1" id="<?php echo $current_theme_id; ?>">
	<?php
	$theme = Theme::getTheme($current_theme_id);

	if ($theme->isUpdateAvailable())
	{
		$update_link = ' | <a href="' . Language::get_url('admin/updateTheme/' . $current_theme_id . '/') . '" class="button small green">' . __('Update') . '</a>';
		if ($theme->update_data->info_url)
		{
			$update_link .= ' <a href="' . View::escape($theme->update_data->info_url) . '" target="_blank">' . $theme->update_data->version . '</a>';
		}
	}
	else
	{
		$update_link = '';
	}
	?>
	<div class="col col-12 sm-col-6 md-col-4 p1">
		<a href="<?php echo Language::get_url('admin/themesCustomize/'); ?>">
			<img src="<?php echo $theme->screenshot() ?>" width="300" height="225" alt="<?php echo View::escape($theme->info['name']); ?>" />
		</a>
	</div>
	<div class="col col-12 sm-col-6 md-col-8 p1">

		<p class="my0 small muted"><?php echo __('Current Theme'); ?></p>
		<h2 class="mt0"><?php echo View::escape($theme->info['name']); ?></h2>
		<p><?php echo __('By') . ': ' . $theme->author() . ' | ' . __('Version') . ': ' . View::escape($theme->info['version']) . $update_link; ?></p>
		<p><?php echo View::escape($theme->info['description']); ?></p>
		<p><?php echo __('Theme files located in ') . '<code>/themes/' . $current_theme_id . '/</code>'; ?></p>

		<p>
			<a href="<?php echo Language::get_url('admin/themesCustomize/'); ?>" class="button"><i class="fa fa-paint-brush" aria-hidden="true"></i> <?php echo __('Customize'); ?></a> 
			<a href="<?php echo Language::get_url('admin/widgets/'); ?>" class="button"><i class="fa fa-puzzle-piece" aria-hidden="true"></i> <?php echo __('Widgets'); ?></a> 
		</p>
	</div>
</div>

<?php
$available_themes_html = '';

foreach ($themes as $th)
{
	$theme_id = View::escape($th);
	if ($theme_id == $current_theme_id)
	{
		// skip currently active theme 
		continue;
	}

	$theme = Theme::getTheme($theme_id);

	if ($theme->isUpdateAvailable())
	{
		$update_link = ' | <a href="' . Language::get_url('admin/updateTheme/' . $theme_id . '/') . '" class="button small green">' . __('Update') . '</a>';
		if ($theme->update_data->info_url)
		{
			$update_link .= ' <a href="' . View::escape($theme->update_data->info_url) . '" target="_blank">' . $theme->update_data->version . '</a>';
		}
	}
	else
	{
		$update_link = '';
	}


	$available_themes_html .= '<div class="col col-12 lg-col-3 md-col-4 sm-col-6 p1 theme" id="' . $theme_id . '">
			<a href="' . Language::get_url('admin/themesCustomize/' . $theme_id . '/') . '">
				<img src="' . $theme->screenshot() . '" width="300" height="225" alt="' . View::escape($theme->info['name']) . '" />
			</a>
			<h3><a href="#info" class="info" title="' . View::escape(__('Info')) . '">' . View::escape($theme->info['name']) . ' <i class="fa fa-info" aria-hidden="true"></i></a></h3>
			<p>' . __('By') . ': ' . $theme->author() . $update_link . '</p>
			<div class="info display-none">
				<p>' . __('Version') . ': ' . View::escape($theme->info['version']) . '</p>
				<p>' . View::escape($theme->info['description']) . '</p>
				<p>' . __('Theme files located in ') . '<code>/themes/' . $theme_id . '/</code></p>
			</div>
			<p>
				<span class="button-group button-group-block">
					<a href="' . Language::get_url('admin/themesActivate/' . $theme_id . '/') . '" class="button" title="' . View::escape(__('Activate')) . '">' . __('Activate') . '</a>
					<a href="' . Language::get_url('admin/themesCustomize/' . $theme_id . '/') . '" class="button" title="' . View::escape(__('Preview')) . '"><i class="fa fa-eye" aria-hidden="true"></i></a>
					<a href="' . Language::get_url('admin/themesDelete/' . $theme_id . '/') . '" class="button red delete" title="' . View::escape(__('Delete')) . '"><i class="fa fa-trash" aria-hidden="true"></i></a>
				</span>
			</p>			
			</div>';
}

if ($available_themes_html)
{
	echo '<div class="other_themes">'
	. '<hr>'
	. '<h2>' . __('Available Themes') . '</h2>'
	. '<div class="celarfix mxn1">'
	. $available_themes_html
	. '</div>'
	. '</div>';
}


// display existing backups 
if ($themes_backup)
{
	foreach ($themes_backup as $theme_id)
	{
		$str_themes_backup .= '<tr>
			<td>' . View::escape($theme_id) . '</td>
			<td><a href="#delete_theme" class="button red delete_theme" 
				id="' . View::escape($theme_id) . '" title="' . View::escape(__('Delete')) . '"><i class="fa fa-trash" aria-hidden="true"></i></a></td>
			</tr>';
	}

	if ($str_themes_backup)
	{
		echo '
			<div class="theme_backups clearfix">
			<h3>' . __('Old theme backups') . '</h3>
			<p>' . __('Delete old theme backups if they are not required any more. Backups located in <code>{name}</code>', array('{name}' => Theme::ThemesBackupRoot()))
		. ' <a href="#delete_all" class="button red delete_all">' . __('Delete all theme backups') . '</a></p>
			<table class="grid">
			' . $str_themes_backup . '
			</table>
			</div>';
	}
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

		$('a.delete_theme').click(deleteThemeBackup);
		$('a.delete_all').click(deleteThemeBackupAll);
	});
	var nounce = '<?php Config::nounceInput(); ?>';

	function deleteThemeBackup()
	{
		var $me = $(this);
		var $tr = $me.parents('tr:first');
		var id = $me.attr('id');

		$me.addClass('loading');

		$.post(BASE_URL + 'admin/themesDeleteBackup/', {
			action: 'delete_theme',
			theme_id: id,
			nounce: '<?php echo Config::nounce(); ?>'
		}, function (data) {
			if (data == 'ok') {
				// deleted remove row
				$tr.remove();
			} else {
				alert(data);
			}

		}).fail(function () {
			console.log('deleteThemeBackup:fail');
		}).always(function () {
			$me.removeClass('loading');
		});

		return false;
	}

	function deleteThemeBackupAll()
	{
		var $me = $(this);
		$me.addClass('loading');

		$.post(BASE_URL + 'admin/themesDeleteBackup/', {
			action: 'delete_all',
			nounce: '<?php echo Config::nounce(); ?>'
		}, function (data) {
			if (data == 'ok') {
				// deleted remove row
				$('.theme_backups').remove();
			} else {
				alert(data);
			}
		}).fail(function () {
			console.log('deleteThemeBackupAll:fail');
		}).always(function () {
			$me.removeClass('loading');
		});
		return false;
	}
</script>

