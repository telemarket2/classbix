$(function() {
	$('#fcp_close,#fcp_closed,#fcp_title').click(fcpToggle);
	$('.ui-accordion-header').click(fcpAccardionClick);
	fcpToggle();

	// load initial data 
	fcpLoadData(fcp_data);

	// init coolor picker
	initSpectrum();

	$('input.color,select', $('form[name="fcp"]')).change(fcpUpdatePage);


	$('.fcp_save', $('form[name="fcp"]')).click(fcpSave);
	$('form[name="fcp"]').submit(fcpSave);
});


var predefined_cheme = {
	default: {
		fcp_colors_main: '#0076BE',
		fcp_colors_secondary: '#E07314',
		fcp_colors_contact: '#E0F4FF',
		fcp_colors_contact_text: '#333',
		fcp_colors_link: '#0066DD',
		fcp_colors_background: '#fff',
		fcp_image_background: 'none'
	},
	blue: {
		fcp_colors_main: '#4cb2c9',
		fcp_colors_secondary: '#f02b63',
		fcp_colors_contact: '#e8f6fa',
		fcp_colors_contact_text: '#103d45',
		fcp_colors_link: '#4cb2c9',
		fcp_colors_background: '#cbeef6',
		fcp_image_background: 'images/bg-grunge-dark.png'
	},
	green: {
		fcp_colors_main: '#789048',
		fcp_colors_secondary: '#604848',
		fcp_colors_contact: '#f0f0d8',
		fcp_colors_contact_text: '#607848',
		fcp_colors_link: '#789048',
		fcp_colors_background: '#f0f0d8',
		fcp_image_background: 'images/bg-grunge-dark.png'
	},
	orange: {
		fcp_colors_main: '#fa6900',
		fcp_colors_secondary: '#69d2e7',
		fcp_colors_contact: '#fcf5f1',
		fcp_colors_contact_text: '#f38630',
		fcp_colors_link: '#fa6900',
		fcp_colors_background: '#e0e4cc',
		fcp_image_background: 'images/bg-diagonal-bold-light.png'
	},
	magenta: {
		fcp_colors_main: '#8a0651',
		fcp_colors_secondary: '#87a7be',
		fcp_colors_contact: '#fcebf5',
		fcp_colors_contact_text: '#8a0651',
		fcp_colors_link: '#8a0651',
		fcp_colors_background: '#474747',
		fcp_image_background: 'images/bg-dots-light.png'
	},
	treehouse: {
		fcp_colors_main: '#00ace9',
		fcp_colors_secondary: '#d43f3f',
		fcp_colors_contact: '#f6f6e8',
		fcp_colors_contact_text: '#6a9a1f',
		fcp_colors_link: '#00ace9',
		fcp_colors_background: '#404040',
		fcp_image_background: 'images/bg-noise-light.png'
	},
	wood: {
		fcp_colors_main: '#af6900',
		fcp_colors_secondary: '#4c8b13',
		fcp_colors_contact: '#d6cfb4',
		fcp_colors_contact_text: '#5d3e17',
		fcp_colors_link: '#af6900',
		fcp_colors_background: '#42341f',
		fcp_image_background: 'images/bg-wood-light.png'
	}

};


function initSpectrum()
{
	$("input.color").spectrum("destroy");

	$('input.color').spectrum({
		showPalette: true,
		showSelectionPalette: true,
		palette: [],
		localStorageKey: "spectrum.homepage", // Any Spectrum with the same string will share selection
		clickoutFiresChange: true,
		showInitial: true,
		showInput: true,
		preferredFormat: "hex6"
	});
}

function fcpToggle()
{
	if ($('#fcp').hasClass('fcp_mini'))
	{
		// already mini, display cp
		$('#fcp').removeClass('fcp_mini').animate({right: '0'}, 'fast');
		$('#fcp_wrapper').animate({height: 'toggle'}, 'fast');
		$('#fcp_close').html('x');
	}
	else
	{
		$('#fcp').addClass('fcp_mini').animate({right: '-135px'}, 'fast');
		$('#fcp_wrapper').animate({height: 'toggle'}, 'fast');
		$('#fcp_close').html('&laquo;');
	}
	return false;
}

function fcpAccardionClick()
{
	var $me = $(this);
	if ($me.hasClass('ui-active'))
	{
		return false;
	}

	$('.ui-accordion-header').removeClass('ui-active');
	$('.ui-accordion-content').slideUp('fast');
	$me.addClass('ui-active');
	//$('.ui-accordion-content.ui-active .ui-accordion-content');
	$me.next('.ui-accordion-content').slideDown('fast');
	return false;
}

function fcpUpdatePage()
{
	//alert('update page');
	var _custom_style_pattern = custom_style_pattern;
	var ret = '';
	var $form = $('form[name="fcp"]');
	var $me = $(this);


	if ($me.prop('name') == 'fcp_predefined_scheme')
	{
		// change colors with predefined presets 
		if (predefined_cheme[$me.val()] != undefined)
		{
			//alert('load_pre:' + $me.val());
			fcpLoadData(predefined_cheme[$me.val()]);
			return false;
		}
	}


	$('input,select', $form).each(function(i) {
		var $me = $(this);
		var name = $me.attr('name');
		var val = $me.val();

		//alert(name+'->'+_custom_style_pattern[name]);
		if (_custom_style_pattern[name] !== undefined && val.length > 0)
		{
			if (name.indexOf('fcp_image') == 0)
			{
				// this is image 
				if (val !== 'none')
				{
					val = "url(" + THEME_ASSETS + val + ")";
					//alert('val is img=' + val);
				}
				else
				{
					//alert('val is none=' + val);
				}
			}

			ret += _custom_style_pattern[name].replace(new RegExp("__VAL__", "gm"), val);
			// + ' !important'
		}
	});

	// replace escaped &quot; -> "
	ret = ret.replace(new RegExp("&quot;", "gm"), '"');
	//alert(ret);
	$('#custom_style_pattern').text(ret);
	//alert('update page done');
}

/**
 * load stored data to input elements and update view
 * @returns {undefined}
 */
function fcpLoadData(fcp_data)
{
	for (key in fcp_data)
	{
		$('#' + key + ',[name="' + key + '"]').val(fcp_data[key]);
	}

	initSpectrum();
	fcpUpdatePage();
}

function fcpSave()
{
	$.post(BASE_URL, {
		nounce: nounce,
		data: $('form[name="fcp"]').serialize(),
		preview_theme: theme_id,
		fcp_save: '1'
	}, function(data) {
		alert(data);
	});

	return false;
}