<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Category field group') ?> <a href="<?php echo Language::get_url('admin/categoryFieldGroupEdit/') ?>" class="button blue" title="<?php echo View::escape(__('Add')) ?>"><i class="fa fa-plus" aria-hidden="true"></i></a></h1>
<?php
echo '<p>' . __('Define custom field groups for categories.') . '</p>';

if ($catfieldgroups)
{
	?>
	<table class="grid">
		<tr>
			<th><?php echo __('Name') ?></th>
			<th></th>
		</tr><?php
		foreach ($catfieldgroups as $cfg)
		{
			echo '<tr class="r' . ($tr++ % 2) . '">';
			echo '<td>' . View::escape(CategoryFieldGroup::getName($cfg)) . '</td>';
			echo '<td class="right-align">'
			. '<a href="' . Language::get_url('admin/categoryFieldGroupEdit/' . $cfg->id . '/') . '" class="button" title="' . __('Edit') . '"><i class="fa fa-edit" aria-hidden="true"></i></a> '
			. '<a href="' . Language::get_url('admin/categoryFieldGroupDelete/' . $cfg->id . '/') . '" class="button red" title="' . __('Delete') . '"><i class="fa fa-trash" aria-hidden="true"></i></a>'
			. '</td>';
			echo '</tr>';
		}
		?></table>
	<?php
}
else
{

	echo '<div class="empty"><p aria-hidden="true"><i class="fa fa-ban fa-5x" aria-hidden="true"></i></p>'
	. '<p class="h3">' . __('No records') . '</p></div>';
}