<?php echo $this->validation()->messages() ?>
<!-- new search form -->
<div class="clearfix">
	<form class="filter_form minimal panel clearfix" id="filter_form" action="" method="get">
		<div class="row mini col col-12 sm-col-4 md-col-3 lg-col-2 p1">
			<label for="search"><?php echo __('Search term or ad ID') ?>:</label>
			<input class="input" type="search" name="search" id="search" size="20" value="<?php echo View::escape($_GET['search']); ?>" />
		</div>	

		<div class="row col col-12 sm-col-4 md-col-3 lg-col-2 p1">
			<label for="email"><?php echo __('Email') ?>:</label>
			<input class="input" type="text" name="email" id="email" size="20" value="<?php echo View::escape($_GET['email']); ?>">
		</div>
		<div class="row col col-12 sm-col-4 md-col-3 lg-col-2 p1">
			<label for="phone"><?php echo __('Phone') ?>:</label>
			<input class="input" type="tel" name="phone" id="phone" size="20" value="<?php echo View::escape($_GET['phone']); ?>">
		</div>
		<div class="row col col-12 sm-col-4 md-col-3 lg-col-2 p1">
			<label for="location_id"><?php echo __('Location') ?>:</label>			
			<input name="location_id" value="<?php echo View::escape($_GET['location_id']); ?>" 
				   data-src="<?php echo Config::urlJson(Location::STATUS_ALL); ?>"
				   data-key="location"
				   data-selectalt="1"
				   data-rootname="<?php echo View::escape(__('All locations')) ?>"
				   data-allpattern="<?php echo View::escape(__('All {name}')) ?>"
				   data-allallow="1"
				   class="display-none"
				   >
				   <?php // echo Location::selectBox($_GET['location_id'], 'location_id', Location::STATUS_ALL, true, __('All locations')); ?>
		</div>
		<div class="row col col-12 sm-col-4 md-col-3 lg-col-2 p1">
			<label for="category_id"><?php echo __('Category') ?>:</label>	
			<input name="category_id" value="<?php echo View::escape($_GET['category_id']); ?>" 
				   data-src="<?php echo Config::urlJson(Location::STATUS_ALL); ?>"
				   data-key="category"
				   data-selectalt="1"
				   data-rootname="<?php echo View::escape(__('All categories')) ?>"
				   data-allpattern="<?php echo View::escape(__('All {name}')) ?>"
				   data-allallow="1"
				   class="display-none"
				   >
				   <?php // echo Category::selectBox($_GET['category_id'], 'category_id', Category::STATUS_ALL, true, __('All categories')); ?>
		</div>

		<div class="row col col-12 sm-col-4 md-col-3 lg-col-2 p1">
			<label for="verified"><?php echo __('Verified') ?>:</label>
			<select name="verified" id="verified">
				<option value=""><?php echo __('All') ?></option>
				<option value="1" <?php echo ($_GET['verified'] === "1" ? 'selected="selected"' : '') ?>><?php echo __('Yes') ?></option>
				<option value="0" <?php echo ($_GET['verified'] === "0" ? 'selected="selected"' : '') ?>><?php echo __('No') ?></option>
			</select>
		</div>
		<div class="row col col-12 sm-col-4 md-col-3 lg-col-2 p1">
			<label for="enabled"><?php echo __('Status') ?>:</label>
			<select name="enabled" id="enabled">
				<option value=""><?php echo __('All') ?></option>
				<?php
				$arr_enabled = array(
					Ad::STATUS_PENDING_APPROVAL	 => Ad::statusName(Ad::STATUS_PENDING_APPROVAL),
					Ad::STATUS_ENABLED			 => Ad::statusName(Ad::STATUS_ENABLED),
					Ad::STATUS_PAUSED			 => Ad::statusName(Ad::STATUS_PAUSED),
					Ad::STATUS_COMPLETED		 => Ad::statusName(Ad::STATUS_COMPLETED),
					Ad::STATUS_INCOMPLETE		 => Ad::statusName(Ad::STATUS_INCOMPLETE),
					Ad::STATUS_DUPLICATE		 => Ad::statusName(Ad::STATUS_DUPLICATE),
					Ad::STATUS_BANNED			 => Ad::statusName(Ad::STATUS_BANNED),
					Ad::STATUS_TRASH			 => Ad::statusName(Ad::STATUS_TRASH),
					'_ex'						 => __('Expired'),
					'_en'						 => __('Not expired'),
					'_r'						 => __('Running'),
					'_rn'						 => __('Not running'),
					'_f'						 => __('Featured')
				);

				foreach ($arr_enabled as $k => $v)
				{
					echo '<option value="' . $k . '" '
					. ($_GET['enabled'] === $k . '' ? 'selected="selected"' : '') . '>'
					. View::escape($v) . '</option>';
				}
				?>			
			</select>
		</div>
		<div class="row col col-12 sm-col-4 md-col-3 lg-col-2 p1">
			<label for="payment"><?php echo __('Payment') ?>:</label>
			<select name="payment" id="payment">
				<option value=""><?php echo __('All') ?></option>
				<option value="0" <?php echo ($_GET['payment'] === "0" ? 'selected="selected"' : '') ?>><?php echo __('Not reqired') ?></option>
				<option value="1" <?php echo ($_GET['payment'] === "1" ? 'selected="selected"' : '') ?>><?php echo __('Reqired') ?></option>
			</select>
		</div>
		<div class="row col col-12 sm-col-4 md-col-3 lg-col-2 p1">
			<label for="abused"><?php echo __('Abuse reports') ?>:</label>
			<select name="abused" id="abused">
				<option value=""><?php echo __('All') ?></option>
				<option value="1" <?php echo ($_GET['abused'] === "1" ? 'selected="selected"' : '') ?>><?php echo __('Yes') ?></option>
				<option value="0" <?php echo ($_GET['abused'] === "0" ? 'selected="selected"' : '') ?>><?php echo __('No') ?></option>
			</select>
		</div>

		<div class="row col col-12 sm-col-4 md-col-3 lg-col-2 p1">
			<label for="perpage"><?php echo __('Items per page') ?>:</label>
			<select name="perpage" id="perpage">
				<?php
				$arr_perpage = array(20, 50, 100, 300, 500);
				foreach ($arr_perpage as $v)
				{
					echo '<option value="' . $v . '" '
					. ($_GET['perpage'] === $v . '' ? 'selected="selected"' : '') . '>' . $v . '</option>';
				}
				?>
			</select>
		</div>

		<div class="row col col-12 sm-col-4 md-col-3 lg-col-2 p1">
			<label class="input-checkbox">
				<input type="checkbox" name="search_exact" value="1" <?php echo ($_GET['search_exact'] ? 'checked="checked"' : ''); ?> />
				<span class="checkmark"></span>
				<?php echo __('Use exact search') ?>
			</label>
		</div>

		<div class="row action mini col col-12 p1">
			<input type="hidden" name="added_by" id="added_by" value="<?php echo View::escape($_GET['added_by']); ?>" />
			<button type="submit" class="button primary"><i class="fa fa-search" aria-hidden="true"></i> <span><?php echo __('Search') ?></span></button>
			<button type="button" class="button filter_advanced"><i class="fa fa-filter" aria-hidden="true"></i> <span><?php echo __('Filter') ?></span></button>
		</div>
	</form>
</div>
<!-- new search form END -->

<?php
if ($search_desc)
{
	// be smart show "clear all" only if has more than one applied filter
	if (substr_count($search_desc, '>x<') > 1)
	{
		$search_desc = '<a href="' . Language::get_url('admin/items/') . '" '
				. 'class="search_filter_clear" title="' . __('Clear') . '">'
				. '<i class="fa fa-close" aria-hidden="true"></i>'
				. '</a> '
				. $search_desc;
	}
	echo '<p class="search_filters">' . str_replace('>x<', '><i class="fa fa-close" aria-hidden="true"></i><', $search_desc) . '</p>';
}


if ($ads)
{
	// use snippet because this listing used in many places 
	$vals = array(
		'ads'		 => $ads,
		'returl'	 => $returl,
		'paginator'	 => $paginator
	);
	echo View::renderAsSnippet('admin/_listing', $vals);
}
else
{
	echo '<div class="empty"><p aria-hidden="true"><i class="fa fa-ban fa-5x" aria-hidden="true"></i></p>'
	. '<p class="h3">' . __('No ads found.') . '</p></div>';
}
?>

<script language="javascript">
	addLoadEvent(function ()
	{
		//toggleFilterForm(false);
		$('.filter_advanced').click(toggleFilterForm);
		$('.check_all').change(checkall);
	});

	function toggleFilterForm(scroll)
	{
		$('.filter_form').toggleClass('minimal');
		if (typeof scroll !== 'undefined' && scroll)
		{
			scrollTop('.filter_form');
		}
		return false;
	}

	function checkall()
	{
		var $me = $(this);
		$(':checkbox[name="ad[]"]').prop('checked', $me.is(':checked'));
	}


</script>