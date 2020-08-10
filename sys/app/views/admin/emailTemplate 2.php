<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Edit email templates') ?></h1>


<?php
foreach ($mail_template_defaults as $mtd_item)
{
	if ($current == $mtd_item->id)
	{
		$mtd = $mtd_item;
		//$email_tabs[] = '<span class="active">' . View::escape($mtd_item->title) . '</span>';
	}
	else
	{
		//<li><a href="#">name</a></li>
		$mtd_other[] = '<li><a href="' . Language::get_url('admin/emailTemplate/' . $mtd_item->id . '/') . '">'
				. View::escape($mtd_item->title)
				. '</a></li>';
	}
}
//echo '<p class="tabs_static">' . implode(' ', $email_tabs) . '</p>';
?>






<form action="" method="post">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="template"><?php echo __('Template'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<div class="select_alt input">
				<div class="select_alt_text" data-jq-dropdown="#jq-dropdown-template">
					<?php echo View::escape($mtd->title) ?>
				</div>
			</div>
			<div id="jq-dropdown-template" class="jq-dropdown">
				<ul class="jq-dropdown-menu">
					<?php echo implode('', $mtd_other); ?>
				</ul>
			</div>
			<p><?php echo $mtd->description ?></p>
		</div>
	</div>


	<?php
	$echo_tabs = '';
	$echo = '';
	$tab_key = 'name_' . $mtd->id . '_';
	foreach ($language as $lng)
	{
		$lng_label = Language::tabsLabelLngInfo($language, $lng);
		$available_vals = '';
		if ($mtd->vals)
		{
			foreach ($mtd->vals as $k => $v)
			{
				$available_vals .= '<a href="#' . View::escape($k) . '" data-id="' . View::escape($k) . '" data-target="" '
						. 'class="add_to_body button small white">'
						. '<i class="fa fa-plus" aria-hidden="true"></i> '
						. View::escape($v)
						. '</a> ';
			}
		}

		$_subject = $mail_template[$mtd->id][$lng->id]->subject;
		if (!strlen(trim($_subject)))
		{
			$_subject = $mtd->subject;
		}

		$_body = $mail_template[$mtd->id][$lng->id]->body;
		if (!strlen(trim($_body)))
		{
			$_body = $mtd->body;
		}

		$echo .= '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
						<div class="col col-12 sm-col-2 px1 form-label">
							<label for="mt[' . $lng->id . '][subject]">' . __('Subject') . $lng_label . '</label>
						</div>
						<div class="col col-12 sm-col-10 px1">
							<input name="mt[' . $lng->id . '][subject]" type="text" class="input input-long"
								id="mt[' . $lng->id . '][subject]"
								value="' . View::escape($_subject) . '" />
						</div>
					</div>
					<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
						<div class="col col-12 sm-col-2 px1 form-label">
							<label for="mt[' . $lng->id . '][body]">' . __('Body') . $lng_label . '</label>
						</div>
						<div class="col col-12 sm-col-10 px1">
							<textarea name="mt[' . $lng->id . '][body]" 
								id="mt[' . $lng->id . '][body]"
								class="mt_' . $lng->id . '_body">' . View::escape($_body) . '</textarea>
							<p>' . str_replace('data-target=""', 'data-target="mt_' . $lng->id . '_body"', $available_vals) . '</p>
						</div>
					</div>';
	}
	$tabs_template = '<div class="clearfix form-row">
							<div class="col col-12 px1 tabs">{tabs}</div>
						</div>';
	echo Language::tabs($language, $tab_key, $tabs_template) . $echo;
	?>	



	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<a href="#view" class="button" data-target=".suggested_values" data-toggle="cb_slide">
				<i class="fa fa-info-circle" aria-hidden="true"></i> 
				<?php echo __('View suggested values') ?>
			</a>
		</div>
	</div>
	<div class="suggested_values display-none">
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Subject') ?></div>
			<div class="col col-12 sm-col-10 px1"><?php echo $mtd->subject ?></div>
		</div>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Body') ?></div>
			<div class="col col-12 sm-col-10 px1"><?php echo nl2br($mtd->body) ?></div>
		</div>
	</div>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<input type="hidden" name="id" id="id" value="<?php echo $mtd->id ?>"  />
			<a href="<?php echo Language::get_url('admin/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>

<script language="javascript">
	addLoadEvent(function ()
	{
		$('.add_to_body').click(insertVar);
	});
</script>