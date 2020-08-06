<h1><?php echo __('Post ad') ?></h1>
<?php
if(!AuthUser::isLoggedIn(false))
{
	echo '<p>' . __('If you have an account please <a href="{url}">log in</a>.', array('{url}' => Language::get_url('post/login/?login=1'))) . '</p>';
}
?>
<p><?php echo __('Select location and category.') ?></p>
<form action="<?php echo Language::get_url('post/'); ?>" method="post" enctype="multipart/form-data">
	<?php
	if(Location::hasValidPostingLocations())
	{
		echo '<p><label for="location_id">' . __('Location') . ':</label> '
		. Location::selectBox($selected_location->id, 'location_id', Location::STATUS_ENABLED, true, __('Select location'), 0, true)
		. Location::selectBoxChain($selected_location->id, 'location_id', Location::STATUS_ENABLED, __('Select location'))
		. '</p>';
	}

	if(Category::hasValidPostingCategories())
	{
		echo '<p><label for="category_id">' . __('Category') . ':</label> '
		. Category::selectBox($selected_category->id, 'category_id', Category::STATUS_ENABLED, true, __('Select category'), 0, true)
		. Category::selectBoxChain($selected_category->id, 'category_id', Category::STATUS_ENABLED, __('Select category'))
		. '</p>';
	}
	?>
	<p><input type="submit" name="submit" id="submit" value="<?php echo __('Continue') ?>" /></p>
</form>