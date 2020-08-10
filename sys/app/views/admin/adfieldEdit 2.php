<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo $title ?></h1>
<form action="" method="post">



	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="type"><?php echo __('Type') ?></label></div>
		<div class="col col-12 sm-col-10 px1"><?php echo AdField::selectBox($adfield->type, 'type', !$add); ?></div>
	</div>

	<?php
	foreach ($language as $lng)
	{
		$lng_label = Language::tabsLabelLngInfo($language, $lng, ' <small class="muted">({name})</small>');

		/* $echo .= '<tr id="name_' . View::escape($lng->id) . '" class="name_ name_' . View::escape($lng->id) . '">
		  <td><label for="af_description[' . $lng->id . '][name]">' . __('Name') . $lng_label . ':</label></td>
		  <td><input name="af_description[' . $lng->id . '][name]" type="text"
		  id="af_description[' . $lng->id . '][name]" ' . Language::tabsRelDefault($lng) . '
		  value="' . View::escape(AdField::getNameByLng($adfield, $lng->id)) . '" /></td>
		  </tr>
		  <tr class="name_ name_' . View::escape($lng->id) . ' val">
		  <td><label for="af_description[' . $lng->id . '][val]">' . __('Unit') .
		  $lng_label . ' <span class="gray_info">(' . __('optional') . ')</span> ' . ':</label></td>
		  <td><input name="af_description[' . $lng->id . '][val]" type="text"
		  id="af_description[' . $lng->id . '][val]"
		  value="' . View::escape(AdField::getNameByLng($adfield, $lng->id, 'val')) . '" /></td>
		  </tr>';
		 */
		$lng_id = View::escape($lng->id);
		$name_name = 'af_description[' . $lng_id . '][name]';
		$name_val = 'af_description[' . $lng_id . '][val]';
		$val_name = View::escape(AdField::getNameByLng($adfield, $lng->id));
		$val_val = View::escape(AdField::getNameByLng($adfield, $lng->id, 'val'));


		$echo .= '<div class="clearfix form-row name_ name_' . View::escape($lng->id) . '" id="name_' . View::escape($lng->id) . '">'
				. '<div class="col col-12 sm-col-2 px1 form-label">'
				. '<label for="' . $name_name . '">' . __('Name') . $lng_label . '</label>'
				. '</div>'
				. '<div class="col col-12 sm-col-10 px1">'
				. '<input name="' . $name_name . '" type="text" id="' . $name_name . '" ' . Language::tabsRelDefault($lng) . ' value="' . $val_name . '" />'
				. '</div>'
				. '</div>'
				. '<div class="clearfix form-row name_ name_' . View::escape($lng->id) . ' val">'
				. '<div class="col col-12 sm-col-2 px1 form-label">'
				. '<label for="' . $name_val . '">' . __('Unit') . $lng_label . ' <small class="muted">(' . __('optional') . ')</small></label>'
				. '</div>'
				. '<div class="col col-12 sm-col-10 px1">'
				. '<input name="' . $name_val . '" type="text" id="' . $name_val . '" value="' . $val_val . '" />'
				. '</div>'
				. '</div>';
	}

	echo Language::tabs($language, 'name_', '<div class="clearfix form-row"><div class="col col-12 px1 tabs">{tabs}</div></div>') . $echo;

	// custom values 

	$num_lng = count($language);
	$num_cell = $num_lng;
	switch ($num_cell)
	{
		case 3:
			$class_cell = 'col col-6 sm-col-4 p1';
			$class_action = 'col col-6 sm-col-4 right-align p1';
			break;
		case 2:
			$class_cell = 'col col-6 p1';
			$class_action = 'col col-6 right-align p1';
			break;
		case 4:
		default:
			$class_cell = 'col col-6 sm-col-3 p1';
			$class_action = 'col col-12 center p1';
	}

	if ($num_lng > 1)
	{
		// format with language flag
		$input_pattern = '<div class="input-group input-group-block">'
				. '<div class="button addon"><img src="{img_src}" /></div>'
				. '<input type="text" name="{name}" id="{name}" value="{value}" class="input"/>'
				. '</div>';
	}
	else
	{
		// format regular input 
		$input_pattern = '<input type="text" name="{name}" id="{name}" value="{value}" class="input input-long"/>';
	}
	$arr_search = array('{name}', '{value}', '{img_src}');

	foreach ($language as $lng)
	{
		//$table_header .= '<div class="' . $class_cell . '">' . Language::formatName($lng) . '</div>';

		$arr_img[$lng->id] = Language::imageUrl($lng);

		$_name = 'afv[afvd][' . $lng->id . '][name][]';
		$_id = 'afv[afv_id][]';
		$table_new_row .= '<div class="' . $class_cell . '">' . str_replace($arr_search, array($_name, '', $arr_img[$lng->id]), $input_pattern) . '</div>';
	}

	$_id = 'afv[afv_id][]';
	echo '<div class="value panel">'
	. '<h3>' . __('Values') . '</h3>';
	/* echo  '<div class="clearfix">'
	  . '<hr>' . $table_header . '<div class="' . $class_action . '">' . __('Position') . '/' . __('Remove') . '</div>'
	  . '</div>'; */
	if ($adfield->AdFieldValue)
	{
		foreach ($adfield->AdFieldValue as $afv)
		{
			echo '<div class="clearfix table_old_row value_row"><hr>';
			foreach ($language as $lng)
			{
				$_name = 'afv[afvd][' . $lng->id . '][name][]';
				/* echo '<div class="' . $class_cell . '">'
				  . '<input type="text" name="' . $_name . '" id="' . $_name . '" value="' . View::escape(AdFieldValue::getNameByLng($afv, $lng->id)) . '" class="input input-long" />'
				  . '</div>'; */

				echo '<div class="' . $class_cell . '">'
				. str_replace($arr_search, array($_name, View::escape(AdFieldValue::getNameByLng($afv, $lng->id)), $arr_img[$lng->id]), $input_pattern)
				. '</div>';
			}

			echo '<div class="' . $class_action . '">'
			. '<input type="hidden" name="' . $_id . '" id="' . $_id . '" value="' . $afv->id . '" />'
			. '<div class="button-group">'
			. '<a href="#up" class="button move_up" title="' . __('up') . '">&uarr;</a>'
			. '<a href="#down" class="button move_down" title="' . __('down') . '">&darr;</a>'
			. '</div>'
			. '<a href="#remove" class="remove button red" title="' . View::escape(__('Remove')) . '">'
			. '<i class="fa fa-trash" aria-hidden="true"></i></a>'
			. '</div>'
			. '</div>';
		}
	}

	echo '<div class="clearfix table_new_row value_row"><hr>'
	. $table_new_row
	. '<div class="' . $class_action . '">'
	. '<input type="hidden" name="' . $_id . '" id="' . $_id . '" value="" />'
	. '<div class="button-group">'
	. '<a href="#up" class="button move_up" title="' . __('up') . '">&uarr;</a>'
	. '<a href="#down" class="button move_down" title="' . __('down') . '">&darr;</a>'
	. '</div>'
	. '<a href="#remove" class="remove button red" title="' . View::escape(__('Remove')) . '">'
	. '<i class="fa fa-trash" aria-hidden="true"></i></a>'
	. '</div>'
	. '</div>'
	. '<div class="clearfix">
			<div class="col col-12 center"><hr>
				<a href="#add_new_row" class="add_new_row button primary"><i class="fa fa-plus" aria-hidden="true"></i> ' . __('Add new value field') . '</a>
			</div>
		</div>'
	. '</div>';
	?>	



	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="hidden" name="id" id="id" value="<?php echo $adfield->id ?>"  />
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<a href="<?php echo Language::get_url('admin/itemfield/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>

</form>
<script>
	addLoadEvent(function () {
		cbAFedit.initTypeActions();
	});

	var cbAFedit = {
		table_new_row: null,
		initTypeActions: function () {
			// init type selector 
			$(document).on('change', '#type', cbAFedit.typeChange);
			$('#type').change();

			// if no empty value field left then append new field to bottom of field for each language 

			// store empty value field example for each language 
			cbAFedit.table_new_row = $('.table_new_row');
			cbAFedit.table_new_row.hide();

			// if no rows then add empty field 
			cbAFedit.checkEmptyValueField();

			$(document).on('click', '.add_new_row', cbAFedit.addNewValueField);
			$(document).on('click', '.remove', cbAFedit.removeValueField);

			$(document).on('click', '.move_up', cbAFedit.moveUp);
			$(document).on('click', '.move_down', cbAFedit.moveDown);
		},
		typeChange: function () {
			// type changed hide show according to type
			var $me = $(this);
			var $val = $('.val');
			var $value = $('.value');
			$val.hide().attr('keep_hidden', 'keep_hidden');
			$value.hide();
			switch ($me.val())
			{
				case 'number':
					$val.removeAttr('keep_hidden');
					if ($('.tabs').length > 0)
					{
						$('.tabs .active').click();
					}
					else
					{
						$val.show();
					}
					break;
				case 'checkbox':
				case 'radio':
				case 'dropdown':
					$value.show();
					break;
			}
		},
		addNewValueField: function () {
			// check if no empty value field then add new one
			cbAFedit.table_new_row.clone(true).insertBefore(cbAFedit.table_new_row).slideDown('slow');

			return false;
		},
		removeValueField: function () {
			var $me = $(this);
			var $tr = $me.parents('.value_row:first');
			if ($tr.length)
			{
				$tr.remove();
			}
			cbAFedit.checkEmptyValueField();

			return false;
		},
		checkEmptyValueField: function () {
			// check if no visible value field then add initial empty value field
			if ($('.table_old_row').length)
			{
				// has visible old value 
				return true;
			}

			if ($('.table_new_row').length > 1)
			{
				// has visible new row
				return true;
			}

			// dont have any visible rows then add empty new row
			cbAFedit.addNewValueField();
		},
		moveUp: function () {
			// get first row 
			var $tr = $(this).parents('.value_row:first');

			var $prev = $tr.prev('.value_row:visible');
			if ($prev.length > 0)
			{
				$tr.insertBefore($prev);
				$tr.hide().slideDown('slow');
			}

			return false;
		},
		moveDown: function () {
			// get first row 
			var $tr = $(this).parents('.value_row:first');
			var $next = $tr.next('.value_row:visible');
			if ($next.length > 0)
			{
				$tr.insertAfter($next);
				$tr.hide().slideDown('slow');
			}

			return false;
		}
	};
</script>