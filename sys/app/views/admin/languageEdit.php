<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo $title ?></h1>
<form action="" method="post">



	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="id"><?php echo __('ID') ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			if ($add)
			{
				echo '<input name="id" type="text" id="id" value="' . View::escape($language->id) . '" required />
					<p>' . __('2 character language code like en, es, de, ru, tr...') . '</p>';
			}
			else
			{
				echo View::escape($language->id)
				. '<input name="id" type="hidden" id="id" value="' . View::escape($language->id) . '" />';
			}
			?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="name"><?php echo __('Name') ?></label></div>
		<div class="col col-12 sm-col-10 px1"><input name="name" type="text" id="name" value="<?php echo View::escape($language->name) ?>" required /></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="img"><?php echo __('Image') ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="img" type="hidden" id="img" value="<?php echo View::escape($language->img) ?>"  />
			<?php
			echo '<a href="#change" class="button white" data-toggle="cb_slide" data-target=".lng_img_selector"><img src="' . Language::imageUrl($language) . '" class="lng_img_selector_preview" /> ' . __('change') . '</a>';


			// display image selector
			$images = Language::getImages();
			echo '<div class="lng_img_selector display-none clearfix">';
			echo '<div class="clearfix"><a href="#">' . __('Cancel') . '</a></div>';
			foreach ($images as $img)
			{
				echo '<a href="#" data-img="' . $img . '"><img src="' . Language::imageUrlByFile($img) . '" /> ' . $img . '</a>';
			}

			echo '</div>';
			?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<div>
				<label class="input-checkbox">
					<input name="enabled" type="checkbox" id="enabled" value="1" <?php echo $language->enabled ? 'checked="checked"' : ''; ?>  />
					<span class="checkmark"></span>
					<?php echo __('Enabled') ?>
				</label>
			</div>
			<div>
				<label class="input-checkbox">
					<input name="default" type="checkbox" id="default" value="1" <?php echo Language::isDefault($language->id) ? 'checked="checked"' : ''; ?>  />
					<span class="checkmark"></span>
					<?php echo __('Default') ?>
				</label>
			</div>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit') ?>" />
			<a href="<?php echo Language::get_url('admin/language/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>
<script>
	addLoadEvent(function () {
		$(document).on('click','.lng_img_selector a',selectImg);
	});

	function selectImg(e)
	{
		var $me = $(e.target);
		var img = $me.data('img');
		var img_base = '<?php echo Language::imageUrlByFile() ?>';

		if (typeof img !== 'undefined')
		{
			$('img.lng_img_selector_preview').attr('src', img_base + img);
			$('input#img').val(img);
		}

		$('.lng_img_selector').slideUp('fast');

		return false;
	}

</script>
