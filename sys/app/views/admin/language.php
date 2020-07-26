<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Language') ?> <a href="<?php echo Language::get_url('admin/languageEdit/') ?>" class="button blue" title="<?php echo View::escape(__('Add')); ?>"><i class="fa fa-plus" aria-hidden="true"></i></a></h1>


<table class="grid tblmin">
	<tr>
		<th><?php echo __('Name') ?></th>
		<th><?php echo __('ID') ?></th>
		<th><?php echo __('Translation') ?></th>
		<th><?php echo __('Enabled') ?></th>
		<th><?php echo __('Position') ?></th>
		<th></th>
	</tr>
	<?php
	if ($language)
	{
		foreach ($language as $l)
		{


			if (Language::isDefault($l->id))
			{
				$default = ' <small class="muted">(' . __('Default language') . ')</small>';
			}
			else
			{
				$default = '';
			}

			// can translate all languages 
			$translate_link = '<a href="' . Language::get_url('admin/translate/' . $l->id . '/') . '" class="button blue">' . __('Translate') . '</a>';
			/* if ($l->id != 'en')
			  {
			  $translate_link = '<a href="' . Language::get_url('admin/translate/' . $l->id . '/') . '" class="button blue">' . __('Translate') . '</a>';
			  }
			  else
			  {
			  $translate_link = '';
			  } */

			echo '<tr class="r' . ($tr++ % 2) . '">';
			echo '<td>' . Language::formatName($l) . $default . '</td>';
			echo '<td data-title="' . View::escape(__('ID')) . '">' . View::escape($l->id) . '</td>';
			echo '<td>' . $translate_link . '</td>';
			echo '<td>'
			. '<label class="input-switch" data-id="' . $l->id . '" data-switch="enabled">'
			. '<input type="checkbox" value="1" ' . ($l->enabled ? 'checked="checked"' : '') . ' />'
			. '<span class="checkmark"></span>'
			. '</label>'
			. '</td>';
			echo '<td>'
			. '<div class="button-group">'
			. '<a href="' . Language::get_url('admin/languageOrder/' . $l->id . '/up/') . '" class="button" title="' . __('up') . '">&uarr;</a>'
			. '<a href="' . Language::get_url('admin/languageOrder/' . $l->id . '/down/') . '" class="button" title="' . __('down') . '">&darr;</a>'
			. '</div>'
			. '</td>';
			echo '<td class="right-align">'
			. '<a href="' . Language::get_url('admin/languageEdit/' . $l->id . '/') . '" class="button" title="' . View::escape(__('Edit')) . '"><i class="fa fa-edit" aria-hidden="true"></i></a> '
			. '<a href="' . Language::get_url('admin/languageDelete/' . $l->id . '/') . '" class="button red" title="' . View::escape(__('Delete')) . '"><i class="fa fa-trash" aria-hidden="true"></i></a>'
			. '</td>';
			echo '</tr>';
		}
	}
	else
	{
		echo '<tr><td colspan="5">' . __('No records found. You can add new records here.') . '</td></tr>';
	}
	?>
</table>

<?php
echo '<p>' . __('After adding new language update content in following places.') . '</p>' .
 '<ul>
		<li><a href="' . Language::get_url('admin/settings/') . '">' . __('Site title and description') . '</a></li>
		<li><a href="' . Language::get_url('admin/categories/') . '">' . __('Categories') . '</a></li>
		<li><a href="' . Language::get_url('admin/locations/') . '">' . __('Locations') . '</a></li>
		<li><a href="' . Language::get_url('admin/itemfield/') . '">' . __('Custom fields') . '</a></li>
		<li><a href="' . Language::get_url('admin/categoryFieldGroup/') . '">' . __('Category field groups') . '</a></li>
		<li><a href="' . Language::get_url('admin/emailTemplate/') . '">' . __('Email templates') . '</a></li>
		<li><a href="' . Language::get_url('admin/pages/') . '">' . __('Pages') . '</a></li>
	</ul>';
?>

<script>
	addLoadEvent(function ()
	{
		//$('.categoriesAction').click(categoriesAction);
		cb.buttonSwitch('[data-switch]', {url: 'admin/languageAction/'});
	});
</script>
