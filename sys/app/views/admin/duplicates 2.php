<?php

function format_ad_duplicate($ad1, $ad2)
{
	// check for differences to build skip array

	$skip = array();
	if ($ad1->location_id == $ad2->location_id)
	{
		$skip['location'] = 1;
	}
	if ($ad1->category_id == $ad2->category_id)
	{
		$skip['category'] = 1;
	}

	if (date('Y-m-d', $ad1->added_at) === date('Y-m-d', $ad2->added_at))
	{
		$skip['date'] = 1;
	}

	if ($ad1->added_by == $ad2->added_by)
	{
		$skip['user'] = 1;
	}


	if ($ad1->id == $ad2->id)
	{
		return false;
	}
	elseif ($ad1->id < $ad2->id)
	{
		return format_ad_duplicate_item($ad1, 'item_1', $skip)
				. format_ad_duplicate_item($ad2, 'item_2', $skip);
	}

	return format_ad_duplicate_item($ad2, 'item_1', $skip)
			. format_ad_duplicate_item($ad1, 'item_2', $skip);
}

function format_ad_duplicate_item($ad, $class = "item_1", $skip = array())
{
	// get picture url
	$_img_thumb = Adpics::imgThumb($ad->Adpics, '', $ad->User, 'lazy', $ad);
	if ($_img_thumb)
	{
		$_data_lazy = 'data-src="' . $_img_thumb . '"';
		$_thumb_class = 'img-yes';
		$thumb = '<a href="' . Ad::url($ad) . '" class="item_thumb lazy" ' . $_data_lazy . '></a>';
	}
	else
	{
		$_data_lazy = '';
		$_thumb_class = 'img-no';
		$thumb = '';
	}



	$link_email = Language::get_url('admin/items/?email=' . urldecode($ad->email));

	$date_title = __('Added at') . ": \t" . Config::dateTime($ad->added_at);
	if (strcmp(date("Y-m-d", $ad->added_at), date("Y-m-d", $ad->published_at)) != 0)
	{
		$date_title .= "\n" . __('Published at') . ": \t" . Config::dateTime($ad->published_at);
	}
	$date_title .= "\n" . __('Expires at') . ": \t" . Config::dateTime($ad->expireson);

	if ($ad->featured)
	{
		$date_title .= "\n" . __('Featured until') . ": \t" . Config::dateTime($ad->featured_expireson);
	}

	$arr_extra = array();
	$arr_extra[] = $ad->id;
	if ($ad->hits)
	{
		$arr_extra[] = '<i class="fa fa-eye" aria-hidden="true"></i> ' . number_format($ad->hits);
	}
	if (!isset($skip['category']))
	{
		$arr_extra[] = Category::getFullNameById($ad->category_id);
	}
	if (!isset($skip['location']))
	{
		$arr_extra[] = Location::getFullNameById($ad->location_id);
	}
	if (!isset($skip['user']))
	{
		$arr_extra[] = '<a href="' . $link_email . '">' . $ad->email . '</a>';
	}
	if (!isset($skip['date']))
	{
		$arr_extra[] = '<abbr title="' . $date_title . '">' . Config::date($ad->published_at) . '</abbr> ';
	}


	$return = '<li class="item clearfix ' . $_thumb_class . ' ' . $class . ' ' . ($ad->listed ? '' : ' not_listed_ad') . '" id="' . $ad->id . '">'
			. $thumb
			. '<div class="item_content">'
			. Ad::labelFeatured($ad)
			. Ad::labelAbused($ad)
			. Ad::labelVerified($ad)
			. Ad::labelEnabled($ad)
			. Ad::labelPayment($ad)
			. Ad::labelExpired($ad)
			. '<a class="item_title" href="' . Ad::url($ad) . '" target="_blank">' . View::escape(Ad::getTitle($ad)) . '</a>'
			. '<div class="item_description">' . Ad::snippet($ad) . '</div>'
			. '<ul class="item_extra"><li>'
			. implode('</li><li>', $arr_extra)
			. '</li></ul>'
			. '<p class="action_buttons">
					<a href="' . Ad::urlEdit($ad) . '" class="button">' . __('Edit') . '</a>  
					<a href="#del" class="button red del_item">' . __('Duplicate') . '</a>
				</p>'
			. '</div>'
			. '</li>';

	return $return;
}

// check if it is ajax response then just render ads 
if ($response_type === 'ajax')
{
	$sim_all = array();
	echo '<div class="page_wrap last_id_' . $next_last_id . '">';
	if ($ads)
	{
		$ads_loaded = array();
		//var_dump($ads);
		foreach ($ads as $ad_d)
		{
			if (!$ad_d->duplicates)
			{
				continue;
			}
			if ($ad_d->duplicates)
			{
				// reorder results by similarity percentage 
				echo '<div class="duplicates">'
				. '<p class="action_buttons">'
				. '<a href="#skip_duplicates" class="button skip_duplicates">'
				. __('Skip All')
				. '</a></p>';
				$arr_rank = array();
				foreach ($ad_d->duplicates as $ad)
				{
					$similarity = $ad_d->arr_similarity[$ad->id];
					$key = ($ad_d->id < $ad->id ? $ad_d->id . '|' . $ad->id : $ad->id . '|' . $ad_d->id);

					/* if (!isset($sim_all[$key]))
					  {
					  $sim_all[$key] = array();
					  }
					  $sim_all[$key][] = $similarity; */
					// rank by similarity
					$rank_key = sprintf("%03d", $similarity->similarity_min) . '|';
					// with minimum diff
					$rank_key .= sprintf("%05d", (10000 - $similarity->len_diff)) . '|';
					// id for uniqueness 
					$rank_key .= $ad->id;


					if (!isset($ads_loaded[$key]))
					{
						$ads_loaded[$key] = 1;
						$item = format_ad_duplicate($ad_d, $ad);
						if ($item)
						{
							$arr_rank[$rank_key] = '<div class="item_wrap">'
									. '<ul class="list_style_admin">' . $item . '</ul>'
									. '<p class="action_buttons">'
									. '<b>'
									. $similarity->similarity_min . '% | '
									. $similarity->similarity_avg . '% | '
									. $similarity->similarity_max . '% , '
									. $similarity->len_same . '/' . $similarity->len_diff
									. '</b>'
									. ' <a href="#skip" class="button primary skip_item">' . __('Skip') . '</a>'
									. '</p>'
									. '</div>';
						}
					}
				}
				krsort($arr_rank);
				echo implode('', $arr_rank);
				echo '</div>';
			}// duplicates
		}
	}

	/* echo '<textarea style="width:100%;height:3rem;">';
	  print_r($sim_all);
	  echo '</textarea>'; */

	echo '<div class="next_last_id" id="' . $next_last_id . '"></div>';
	// page_wrap
	echo '</div>';
	// finish ajax response here 

	display_benchmark();

	exit;
}


// load regular html page 
echo $this->validation()->messages();

// image placeholder css, used for lazy loaded images 
$_img_placeholder_src = Adpics::imgPlaceholder();
echo '<style>.list_style_admin .thumb{background-image:url(' . $_img_placeholder_src . ');}</style>';
?>
<h2 class="mt0">
	<?php echo __('Duplicates'); ?>
	<button class="button search_form_toggle" type="button" 
			data-target="form.duplicates_filter" 
			data-target-literal="1">
		<i class="fa fa-search" aria-hidden="true"></i> 
		<?php echo __('Search'); ?>
	</button>
</h2>


<!-- CUSTOM POPUP FORM -->
<form action="" method="post" class="search_form duplicates_filter display-none clearfix">
	<div class="search_form_extra">
		<p>
			<small class="muted block"><label for="matches"><?php echo __('Minimum matches') ?></label></small>
			<input type="number" name="matches" id="matches" size="3" class="input" placeholder="5" />
		</p>
		<p>
			<small class="muted block"><label for="diff"><?php echo __('Maximum difference') ?></label></small>
			<input type="number" name="diff" id="diff" size="3" class="input" placeholder="10" />
		</p>
		<p>
			<small class="muted block"><label for="perc"><?php echo __('Match max % ') ?></label></small>
			<input type="number" name="perc" id="perc" size="3" class="input" placeholder="90" />
		</p>
		<p>
			<small class="muted block"><label for="perc_deviation"><?php echo __('Max - Min % range') ?></label></small>
			<input type="number" name="perc_deviation" id="perc_deviation" size="3" class="input" placeholder="10" />
		</p>
		<p>
			<small class="muted block"><label for="matches_max"><?php echo __('Maximum matches') ?></label></small>
			<input type="number" name="matches_max" id="matches_max" size="3" class="input"  />
		</p>
		<p>
			<small class="muted block"><label for="id"><?php echo __('Check this item id only') ?></label></small>
			<input type="number" name="id" id="id" size="3" class="input" placeholder="id" />
		</p>

		<p>
			<label class="input-checkbox">
				<input type="checkbox" name="pending" value="1" /> 
				<span class="checkmark"></span>
				<?php echo __('Pending only') ?>
			</label>
			<label class="input-checkbox">
				<input type="checkbox" name="owner" value="1" /> 
				<span class="checkmark"></span>
				<?php echo __('Same owner only') ?>
			</label>
			<input type="hidden" name="last_id" value="0" />
		</p>
		<p>
			<button type="submit" name="s" id="s" class="button load_items"><i class="fa fa-search" aria-hidden="true"></i> <?php echo __('Search'); ?></button> 
			<button type="button" class="button link cancel"><?php echo __('Cancel'); ?></button>
		</p>
	</div>
</form>
<!-- /CUSTOM POPUP FORM -->
<p class="stats"></p>
<div class="loaded_items"></div>

<script language="javascript">
	addLoadEvent(function ()
	{
		cbDuplicates.init();
	});

	var cbDuplicates = {
		timeoutLoadId: 0,
		arr_deleted: [],
		arr_skipped: [],
		page: 0,
		init: function ()
		{
			// start on submit or click 
			// click is not inside form on modals
			$(document).on('submit', '.duplicates_filter', cbDuplicates.loadItemsStart);

			var $loaded_items = $('.loaded_items');
			$loaded_items.on('click', '.del_item', cbDuplicates.deleteItem);
			$loaded_items.on('click', '.skip_item', cbDuplicates.skipItem);
			$loaded_items.on('click', '.skip_duplicates', cbDuplicates.skipDuplicates);
			$loaded_items.on('click', ':checkbox[name="ad[]"]', cbDuplicates.update_checked_count);
		},
		resetLoadForm: function ()
		{
			console.log('resetLoadForm');
			// reset last loaded id to 0 
			// will load from start again 
			var $form = $('form.duplicates_filter');
			$form.find('input[name="last_id"]').val(0);
			cbDuplicates.page = 0;
			$('.loaded_items').html('');

			cbDuplicates.updateStats();

			return false;
		},
		checkLoadItems: function ()
		{
			cbDuplicates.updateStats();

			console.log('checkLoadItems');
			var $loaded_items = $('.loaded_items');
			// if more than min then load more 
			var min_items = 10;

			if ($loaded_items.find('.item_wrap').length < min_items)
			{
				var $form = $('form.duplicates_filter');
				// check if not ended 
				var last_id = $form.find('input[name="last_id"]').val();
				// and if not checking only one item
				var id = $form.find('input[name="id"]').val();
				if (last_id != 'END' && (id * 1 === 0))
				{
					cbDuplicates.loadItems();
				}
			}
		},
		loadItemsStart: function ()
		{
			console.log('loadItemsStart');
			// close modal
			cb.modal.close();
			// reset loading 
			cbDuplicates.resetLoadForm();
			// start loading 
			cbDuplicates.loadItems();
			// prevent page redirect after form submission 
			return false;
		},
		cleanEmpty: function ()
		{
			console.log('cleanEmpty');

			var $loaded_items = $('.loaded_items');
			// clean duplictes
			var seen = {};
			$loaded_items.find('.item_wrap').each(function ()
			{
				var $me = $(this);
				var txt = $me.find('.item_1').attr('id') + '|' + $me.find('.item_2').attr('id');
				if (seen[txt])
				{
					$me.remove();
				}
				else
				{
					seen[txt] = true;
				}
			});

			// cleanup empty tables 
			var $page_wrap_stay = $loaded_items.find('.page_wrap').has('.item');
			$loaded_items.find('.page_wrap').not($page_wrap_stay).remove();
			var $duplicates_stay = $loaded_items.find('.duplicates').has('.item_wrap');
			$loaded_items.find('.duplicates').not($duplicates_stay).remove();

			// mark hasmany
			$loaded_items.find('.duplicates').each(function ()
			{
				var $me = $(this);
				var $items = $me.find('.item_wrap');
				$me.find('.skip_duplicates').find('sup').remove();
				if ($items.length > 1)
				{
					$me.addClass('hasmany');
					$me.find('.skip_duplicates').append(' <sup class="muted">' + $items.length + '</sup>');
				}
				else
				{
					$me.removeClass('hasmany');
				}
			});
		},
		updateStats: function ()
		{

			// clean and tidy results
			cbDuplicates.cleanEmpty();

			var $loaded_items = $('.loaded_items');
			var $stats = $('.stats');
			var last_id = $('input[name="last_id"]').val();
			var duplicates = $loaded_items.find('.duplicates').length;
			var items = $loaded_items.find('.item_wrap').length;

			$stats.text('page:' + cbDuplicates.page
					+ ' | last_id:' + last_id
					+ ' | duplicates:' + duplicates
					+ ' | items:' + items
					+ ' | del:' + cbDuplicates.countObj(cbDuplicates.arr_deleted)
					+ ' | skip:' + cbDuplicates.countObj(cbDuplicates.arr_skipped)
					);
		},
		countObj: function (obj)
		{
			var element_count = 0;
			for (e in obj)
			{
				if (obj.hasOwnProperty(e))
				{
					element_count++;
				}
			}

			return element_count;
		},
		loadItems: function ()
		{
			cbDuplicates.updateStats();

			console.log('loadItems');
			// load next page of duplicate items 
			var $form = $('form.duplicates_filter');
			var $loaded_items = $('.loaded_items');
			var data = $form.serialize();


			if ($form.find('input[name="last_id"]').val() == 'END')
			{
				// pages ended. needs reset
				alert('Ended loading pages.');
				return false;
			}

			// check if loading then set timeout 
			if ($loaded_items.find('.loading').length)
			{
				// currently loadign resource. will call self after finishing.
				/// no need to set timer
				return false;
				/*
				 window.clearTimeout(cbDuplicates.timeoutLoadId);
				 cbDuplicates.timeoutLoadId = window.setTimeout(function () {
				 // remove loading and call self 
				 $loaded_items.find('.loading').remove();
				 cbDuplicates.loadItems();
				 }, 2000);
				 return false;*/
			}

			var $loading = $('<div class="loading">Loading...</div>');
			$loaded_items.append($loading);
			$loading.slideUp(10000, function ()
			{
				// remove loading after 10 seconds incativity		
				$loading.remove();
			});
			// clear timeout before starting to load. because we are loading now. no need to keep delayed calls.
			//window.clearTimeout(cbDuplicates.timeoutLoadId);

			console.log('loadItems:' + $form.find('input[name="last_id"]').val());

			$.post(BASE_URL + 'admin/duplicates/', data, function (data_get)
			{
				console.log('loadItems:data');
				//console.log(data_get);
				$loaded_items.find('.loading').remove();
				if ($.trim(data_get) == 'END')
				{
					// finished 
					console.log('loadItems:Finished:' + data_get);
					// set last id END
					$form.find('input[name="last_id"]').val('END');
				}
				else
				{
					// get last id 
					var $data = $('<div class="page_wrap_wrap">' + data_get + '</div>');
					var $next_last_id = $data.find('.next_last_id');
					if ($next_last_id.length)
					{
						// data loaded ok 
						var next_last_id = $next_last_id.attr('id') * 1;
						var cur_last_id = $form.find('input[name="last_id"]').val() * 1;
						if (next_last_id !== cur_last_id)
						{
							// increase page count 
							cbDuplicates.page++;
						}

						if (next_last_id < cur_last_id || cur_last_id === 0)
						{
							// set last id 
							$form.find('input[name="last_id"]').val(next_last_id);
						}
					}
					$next_last_id.remove();

					// append data 
					if ($data.find('.page_wrap'))
					{
						// check before adding items 
						var $item_wraps = $data.find('.item_wrap');
						console.log('$item_wraps:' + $item_wraps.length);
						$item_wraps.each(function ()
						{
							var $me = $(this);
							var id1 = $me.find('.item_1').attr('id') + '';
							var id2 = $me.find('.item_2').attr('id') + '';
							if (cbDuplicates.arr_deleted[id1])
							{
								console.log('$me (arr_deleted):[' + id1 + ']');
								console.log($me);
								$me.remove();
								$data.find('.item_wrap').has('.item#' + id1).remove();
							}

							if (cbDuplicates.arr_deleted[id2])
							{
								console.log('$me (arr_deleted):[' + id2 + ']');
								console.log($me);
								$me.remove();
								$data.find('.item_wrap').has('.item#' + id2).remove();
							}

							if (cbDuplicates.arr_skipped[id1 + '|' + id2] || cbDuplicates.arr_skipped[id2 + '|' + id1])
							{
								console.log('$me (arr_skipped):[' + id1 + '|' + id2 + ']');
								console.log($me);
								$me.remove();
								$data.find('.item_wrap').has('.item#' + id1).has('.item#' + id2).remove();
							}

							// check if current item_wrap already in view
							if ($loaded_items.find('.item_wrap').has('.item#' + id1).has('.item#' + id2).length)
							{
								console.log('$me (exists in $loaded_items):[' + id1 + '|' + id2 + ']');
								console.log($me);
								$me.remove();
								$data.find('.item_wrap').has('.item#' + id1).has('.item#' + id2).remove();
							}
						});

						$loaded_items.append($data.find('.page_wrap'));

						// lazy load if needed
						cb.lazy.init();
					}

					// load next page 
					cbDuplicates.checkLoadItems();
				}
			}).fail(function ()
			{
				console.log('loadItems:post:fail');
				// remove loading 
				$loaded_items.find('.loading').remove();
				// continue to load 
				cbDuplicates.checkLoadItems();

			}).always(function ()
			{
				// clean and tidy results
				cbDuplicates.updateStats();
			});
			return false;
		},

		deleteItem: function ()
		{
			console.log('deleteItem');
			var $me = $(this);
			var $item = $me.parents('.item:first');
			var id = $item.attr('id');
			var text = $me.text();

			if (id)
			{
				// increase del count 
				$me.text(text + '...');
				cbDuplicates.removeItemJq($item, 'hide');
				$.post(BASE_URL + 'admin/duplicates/', {del_ajax: id}, function (data)
				{
					console.log('deleteItem:' + data);
					if ($.trim(data) == 'ok')
					{
						cbDuplicates.removeItemJq($item, 'delete');
					}
					else
					{
						cbDuplicates.removeItemJq($item, 'show');
						alert(data);
					}
					// load next page 
					cbDuplicates.checkLoadItems();
				}).fail(function ()
				{
					console.log('deleteItem:fail');
					cbDuplicates.removeItemJq($item, 'show');
				});
			}
			return false;
		},
		skipItem: function ()
		{
			console.log('skipItem');
			var $me = $(this);
			var $item_wrap = $me.parents('.item_wrap:first');
			cbDuplicates.removeItemJq($item_wrap, 'skip');
			// increase skip count 
			cbDuplicates.checkLoadItems();
			return false;
		},
		skipDuplicates: function ()
		{
			console.log('skipDuplicates');
			var $me = $(this);
			var $duplicates = $me.parents('.duplicates:first');
			var $item_wraps = $duplicates.find('.item_wrap');
			// increase skip count 
			$item_wraps.each(function ()
			{
				var $me = $(this);
				cbDuplicates.removeItemJq($me, 'skip');
			});
			cbDuplicates.checkLoadItems();
			return false;
		},
		removeItemJq: function ($item, is_delete)
		{
			// when deleting remove only currnt item from loaded data
			var id = $item.attr('id');
			var $loaded_items = $('.loaded_items');
			switch (is_delete)
			{
				case 'delete':
					// remove item 
					$loaded_items.find('.item_wrap').has('.item#' + id).remove();
					cbDuplicates.arr_deleted[id + ''] = true;
					break;
				case 'hide':
					// hide item 
					$loaded_items.find('.item_wrap').has('.item#' + id).slideUp(1000);
					break;
				case 'show':
					// hide item 
					$loaded_items.find('.item_wrap').has('.item#' + id).slideDown(1000);
					break;
				case 'skip':
				default:
					// skip item, remove only combination id1 vs id2
					var $item_wrap = $item;
					var id1 = $item_wrap.find('.item_1').attr('id') + '';
					var id2 = $item_wrap.find('.item_2').attr('id') + '';
					// remove only if has both items 
					var $item_wraps = $loaded_items.find('.item_wrap').has('.item#' + id1).has('.item#' + id2);
					$item_wraps.slideUp(500, function ()
					{
						$item_wraps.remove();
						// clean and tidy results
						cbDuplicates.updateStats();
					});
					cbDuplicates.arr_skipped[id1 + '|' + id2] = true;
					break;
			}

			// clean and tidy results
			cbDuplicates.updateStats();
		}
	};
</script>