<?php

// define theme info and custom locations
$info = array(
	'name' => 'Olxer',
	'version' => '1.9.3',
	'version_required' => '1.9',
	'description' => 'Olxer Responsive Classifieds Theme',
	'author_name' => 'ClassiBase',
	'author_url' => 'http://classibase.com/olxer-responsive-classifieds-theme/',
	'info_url' => 'http://classibase.com/olxer-responsive-classifieds-theme/',
	'locations' => array(
		'content_top' => array(
			'title' => __('Content top'),
			'description' => __('Before content and sides'),
		),
		'content_left' => array(
			'title' => __('Content left'),
			'description' => __('Sidebar on left of content')
		),
		'inner_top' => array(
			'title' => __('Inner top'),
			'description' => __('Before content')
		),
		'inner_bottom' => array(
			'title' => __('Inner bottom'),
			'description' => __('After content')
		),
		'content_right' => array(
			'title' => __('Content right'),
			'description' => __('Sidebar on right of content')
		),
		'content_bottom' => array(
			'title' => __('Content bottom'),
			'description' => __('After content and sides')
		)
	),
	'locations_preview' => '<table class="locations_peview">
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
	'customize' => array(
		'logo' => array(
			'title' => __('Logo'),
			'fields' => array(
				'logo' => array(
					'label' => __('Upload Logo'),
					'type' => 'image',
					'width_max' => '480',
					'height_max' => '120',
				),
			)
		),
		'background' => array(
			'title' => __('Background'),
			'fields' => array(
				'background' => array(
					'label' => __('Upload image'),
					'type' => 'image'
				)
			)
		),
		
		'general_search_form' => array(
			'title' => __('Custom'),
			'fields' => array(
				'general_search_form_hide' => array(
					'label' => __('Hide general search'),
					'type' => 'checkbox'
				),
				'display_sidebar_on_ad_page' => array(
					'label' => __('Display sidebars on ad page'),
					'type' => 'checkbox'
				),
			)
		),
	)
);