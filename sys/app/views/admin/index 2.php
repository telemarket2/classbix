<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Overview') ?></h1>

<div class="clearfix overview_cards mxn1">
	<?php
//echo '<ul class="overview">';
	foreach ($overview as $ov)
	{
		if ($ov->val)
		{
			if ($ov->val > 100000)
			{
				$val = $ov->val / 1000000;
				if ($val > 10)
				{
					$precision = 0;
				}
				else
				{
					$precision = 1;
				}
				$val = round($val, $precision) . 'M';
			}
			else
			{
				$val = $ov->val;
			}

			echo '<div class="col col-6 sm-col-3 lg-col-2 p1">
				<a href="' . $ov->url . '" class="panel ' . View::escape($ov->id) . ' c' . abs(crc32($ov->id) % 10) . '" href="' . $ov->url . '">
					<span class="block h1">' . View::escape($val) . '</span>
					<small class="block muted">' . View::escape($ov->title) . '</small></a>
				</div>';

			/* echo '<li class="' . View::escape($ov->id) . ' c' . abs(crc32($ov->id) % 10) . '"><a href="' . $ov->url . '"><span>'
			  . View::escape($val) . '</span> '
			  . View::escape($ov->title) . '</a>
			  </li>'; */
		}
	}
//echo '</ul>';
	?>
</div>

<?php
if ($classibase_news)
{
	echo '<div class="clearfix">
		<div class="col lg-col-6">
		<div class="panel">
			<div class="panel_header"><h3>' . __('Latest news from classibase.com') . '</h3></div>
			<div class="site_news">' . $classibase_news . '</div>
		</div>
		</div>
		</div>';
}