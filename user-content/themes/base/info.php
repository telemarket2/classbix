<?php

// define theme info and custom locations
$info = array(
	'name'				 => 'Classifieds Base Theme',
	'version'			 => '2.0.7',
	'version_required'	 => '2',
	'description'		 => 'This is the Classifieds Base theme',
	'author_name'		 => 'ClassiBase',
	'author_url'		 => 'http://classibase.com',
	'info_url'			 => 'http://classibase.com',
	'locations'			 => array(
		'content_top'	 => array(
			'title'			 => __('Content top'),
			'description'	 => __('Before content and sides'),
		),
		'content_left'	 => array(
			'title'			 => __('Content left'),
			'description'	 => __('Sidebar on left of content')
		),
		'inner_top'		 => array(
			'title'			 => __('Inner top'),
			'description'	 => __('Before content')
		),
		'inner_bottom'	 => array(
			'title'			 => __('Inner bottom'),
			'description'	 => __('After content')
		),
		'content_right'	 => array(
			'title'			 => __('Content right'),
			'description'	 => __('Sidebar on right of content')
		),
		'content_bottom' => array(
			'title'			 => __('Content bottom'),
			'description'	 => __('After content and sides')
		),
		'top_search'	 => array(
			'title'			 => __('Top search'),
			'description'	 => __('Location for searchbox at the top'),
		)
	),
	'locations_preview'	 => '<table class="locations_peview">
						<tr><td colspan="3" class="preview_content_top" title="' . __('Content top') . '"></td></tr>
						<tr>
						<td class="preview_content_left" rowspan="3" width="25%" title="' . __('Content left') . '"></td>
						<td class="preview_inner_top" title="' . __('Inner top') . '"></td>
						<td class="preview_content_right" rowspan="3" width="25%" title="' . __('Content right') . '"></td>
						</tr>
						<tr><td class="preview_content" width="50%" height="100" title="' . __('Content') . '"></td></tr>
						<tr><td class="preview_inner_bottom" title="' . __('Inner bottom') . '"></td></tr>
						<tr><td colspan="3" class="preview_content_bottom" title="' . __('Content bottom') . '"></td></tr>
						</table>',
	/* customize theme by changing some color values */
	'customize'			 => array(
		'theme_presets'	 => array(
			'title'	 => __('Theme Color Presets'),
			'fields' => array(
				'theme_presets' => array(
					'label'	 => __('Theme Color Presets'),
					'type'	 => 'dropdown',
					'value'	 => array(
						'green'	 => __('Green'),
						'red'	 => __('Red'),
						'black'	 => __('Black'),
						'white'	 => __('White'),
						'blue'	 => __('Blue')
					),
				),
			)
		),
		'logo'			 => array(
			'title'	 => __('Logo'),
			'fields' => array(
				'logo' => array(
					'label'		 => __('Upload Logo'),
					'type'		 => 'image',
					'width_max'	 => '480',
					'height_max' => '120',
				),
			)
		)
	)
);
