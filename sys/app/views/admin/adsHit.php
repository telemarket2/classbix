<?php echo $this->validation()->messages() ?>
<?php
$arr_echo = array();
foreach ($periods as $k => $v)
{
	$arr_echo[] = '<a href="' . Language::get_url('admin/itemsHit/' . $v['url']) . '"'
			. ($k === $period ? ' class="active"' : '') . '>'
			. View::escape($v['title']) . '</a>';
}

if ($arr_echo)
{
	// show ads by period	
	echo '<p class="tabs_static">' . implode(' ', $arr_echo) . '</p>';
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
	addLoadEvent(function () {
		$('.check_all').change(checkall);
	});

	function checkall()
	{
		var $me = $(this);
		$(':checkbox[name="ad[]"]').prop('checked', $me.is(':checked'));
	}
</script>