$(function ()
{
	// first thing to load images 
	cb.lazy.init();
	// admin panel only
	if ($('.layout_backend').length)
	{
		cb.initTabs($('body'));
		cb.initSidebar();
	}
	// keep it to work on old themes 
	chainSelect.autoinit();
	// priority to forms 
	cb.select.init();
	// general 
	initDropzone();
	initContactForm();
	cb.init();
});

/**
 * check if cookie is enabled
 * @returns {Boolean}
 */
function checkCookie()
{
	// Quick test if browser has cookieEnabled host property
	if (navigator.cookieEnabled)
	{
		return true;
	}
	// Create cookie
	document.cookie = "cookietest=1";
	var ret = (document.cookie.indexOf("cookietest=") != -1);
	// Delete cookie
	document.cookie = "cookietest=1; expires=Thu, 01-Jan-1970 00:00:01 GMT";
	return ret;
}



/* insert text to textarea */
/* usage: $('#element1, #element2, #element3, .class-of-elements').insertAtCaret('text'); */
jQuery.fn.extend({
	insertAtCaret: function (myValue)
	{
		return this.each(function (i)
		{
			if (document.selection)
			{
				//For browsers like Internet Explorer
				this.focus();
				sel = document.selection.createRange();
				sel.text = myValue;
				this.focus();
			}
			else if (this.selectionStart || this.selectionStart == '0')
			{
				//For browsers like Firefox and Webkit based
				var startPos = this.selectionStart;
				var endPos = this.selectionEnd;
				var scrollTop = this.scrollTop;
				this.value = this.value.substring(0, startPos) + myValue + this.value.substring(endPos, this.value.length);
				this.focus();
				this.selectionStart = startPos + myValue.length;
				this.selectionEnd = startPos + myValue.length;
				this.scrollTop = scrollTop;
			}
			else
			{
				this.value += myValue;
				this.focus();
			}
		});
	}
});


/* insert variable to text field */
function insertVar()
{
	var $me = $(this);
	var myvar = $me.data('id');
	var my_target = $me.data('target');
	var $editpane = $('.' + my_target);
	$editpane.insertAtCaret(myvar);
	return false;
}

function scrollTop(ancor)
{
	// ancor = #item_id
	if ($(ancor).length)
	{
		$('html,body').animate({scrollTop: $(ancor).offset().top}, 'slow');
	}
}




/**
 * load external script and use browser cache
 * // Usage
 $.getScriptCached( "ajax/test.js" ).done(function( script, textStatus ) {
 console.log( textStatus );
 });
 
 * @param {string} url
 * @param {obj} options
 * @returns {jqXHR}
 */
jQuery.getScriptCached = function (url, options)
{
	// Allow user to set any option except for dataType, cache, and url
	options = $.extend(options || {}, {
		dataType: "script",
		cache: true,
		url: url
	});

	if (typeof jQuery.getScriptCached.loaded[url] === 'undefined')
	{
		//console.log('getScriptCached:load:' + url);
		jQuery.getScriptCached.loaded[url] = jQuery.ajax(options)
				.done(function ()
				{
					//console.log('getScriptCached.done:' + url);
				})
				.fail(function (jqXHR, textStatus, errorThrown)
				{
					console.log('getScriptCached.fail:' + textStatus + ':' + url);
				});


	}
	//console.log('getScriptCached:return:' + url);
	return jQuery.getScriptCached.loaded[url];


	// Use $.ajax() since it is more flexible than $.getScript
	// Return the jqXHR object so we can chain callbacks
	/*return jQuery.ajax(options)
	 .done(function ()
	 {
	 console.log('getScriptCached.done:' + url);
	 })
	 .fail(function (jqXHR, textStatus, errorThrown)
	 {
	 console.log('getScriptCached.fail:' + textStatus + ':' + url);
	 });*/
};
jQuery.getScriptCached.loaded = {};


function setupColorbox(fnc)
{
	if (typeof $.colorbox === 'undefined')
	{
		// load colorbox and call again 
		$.getScriptCached(URL_PUBLIC + 'public/js/jquery.colorbox-min.js').done(function ()
		{
			setupColorbox(fnc);
		});
		return false;
	}

	if (typeof setupColorbox.init === 'undefined')
	{
		// It has not... perform the initialization
		setupColorbox.init = true;
		console.log('setupColorbox.init');

		// run this once 
		$.extend($.colorbox.settings, {
			maxWidth: '100%',
			maxHeight: '100%',
			initialWidth: '60px',
			initialHeight: '100px',
			current: "{current} / {total}",
			previous: "◀",
			next: "▶",
			close: "&times;",
			fixed: true,
			imgError: function ()
			{
				var url = $(this).attr('href');
				return 'This <a href="' + url + '" target="_blank"><b>image</b></a> failed to load.';
			}
		});

		$(document).bind('cbox_open', function ()
		{
			$('body').addClass('cboxModal');
		});
		$(document).bind('cbox_cleanup', function ()
		{
			$('body').removeClass('cboxModal');
		});
	}

	if (typeof fnc !== 'undefined')
	{
		fnc();
	}
}

// dropzone integration 1.9
function initDropzone()
{
	//alert('initDropzone');
	if ($('input[type="file"]').length && $('input#image_token').length && typeof dropzone_settings !== 'undefined')
	{
		// load css first 
		var _ds = dropzone_settings;
		var image_token = $('input#image_token').val();
		var $form = $('input[type="file"]:first').parents('form:first');
		var $mydropzone = $('.dropzone', $form);
		var $submit = $('input[type="submit"][name="submit"]:first', $form);
		var url = $form.prop('action');
		var mockFiles = _ds.mockFiles;
		delete _ds.mockFiles;

		// convert existing images to dropbzone style images for fallback
		if (mockFiles.length)
		{
			var $mockFileFallbackWrap = $('<span class="dropzone-previews"></span>');
			var $thumb_grid = $('.dropzone .fallback .thumb_grid');
			for (x in mockFiles)
			{
				// Create the mock file:					
				var mockFile = mockFiles[x];
				var $mockFileFallback = $('<div class="dz-preview dz-complete dz-image-preview"><div class="dz-image"><img data-dz-thumbnail="" alt="a.jpg" src="a.jpg"></div><a class="dz-remove" href="javascript:undefined;" data-dz-remove="">×</a></div>');
				$('img', $mockFileFallback).attr('alt', mockFile.name);
				$('img', $mockFileFallback).attr('src', mockFile.imageUrl);
				$('a.dz-remove', $mockFileFallback).data('id', mockFile.id);
				$mockFileFallbackWrap.append($mockFileFallback);
			}

			$thumb_grid.after($mockFileFallbackWrap);
			$thumb_grid.remove();

			$mockFileFallbackWrap.on('click', 'a.dz-remove', function (e)
			{
				e.preventDefault();
				var $me = $(this);
				var id = $me.data('id');
				var $img = $me.parents('.dz-preview:first');

				console.log('a.dz-remove:click:' + id);
				$.post(url, {id: id, action: 'img_remove'}, function (data)
				{
					if (data == 'ok')
					{
						console.log('a.dz-remove:click:removed:' + id);
						// image removed from server 
						$img.remove();
						// increase image input field
						var $field = $($('.file_upload_template', $form).html());
						$field.attr('name', $field.attr('name') + id);
						$('.file_upload_fields', $form).append($field);
					}
				});
			});
		}

		//$('body').append($('<link rel="stylesheet" type="text/css" />').attr('href', URL_PUBLIC + 'public/css/dropzone.css'));
		// load dropzone javascript
		$.getScriptCached(URL_PUBLIC + 'public/js/dropzone.min.js').done(function ()
		{
			console.log('initDropzone:JS loaded');
			Dropzone.autoDiscover = false;

			var submit_val = $submit.val();
			var submit_form;
			var submit_form_after_upload = false;


			var preventFormSubmit = function (dz)
			{
				if (dz.getUploadingFiles().length === 0 && dz.getQueuedFiles().length === 0)
				{
					setFormSubmit(true);
				}
			};
			var setFormSubmit = function (val)
			{
				if (submit_form !== val)
				{
					submit_form = val;
					//console.log('initDropzone:submit_form:' + val);
					if (val === false)
					{
						$submit.val(submit_val + ' ...');
					}
					else
					{
						$submit.val(submit_val);
						//console.log('submit_form_after_upload:' + submit_form_after_upload);
						if (submit_form_after_upload)
						{
							// call submit 
							//console.log('submit_form()');
							//$form.submit();
							$submit.click();
						}
					}
				}
			};
			var logCount = function (dz)
			{
				console.log('files:' + dz.files.length);
				console.log('getAcceptedFiles:' + dz.getAcceptedFiles().length);
				console.log('getRejectedFiles:' + dz.getRejectedFiles().length);
				console.log('getQueuedFiles:' + dz.getQueuedFiles().length);
				console.log('getUploadingFiles:' + dz.getUploadingFiles().length);
			};
			setFormSubmit(true);
			$form.submit(function (e)
			{
				submit_form_after_upload = true;
				//console.log('$form.submit():called:submit_form=' + submit_form);
				return submit_form;
			});

			// add custom file select button 
			if (typeof _ds.addIcon === "undefined")
			{
				_ds.addIcon = '+';
			}
			$mydropzone.prepend('<span class="dropzone-enabled"><span class="dropzone-previews"></span><span class="dz-message"><span class="dz-message-upload">' + _ds.addIcon + '</span></span></span>');

			_ds.url = url;
			_ds.addRemoveLinks = true;
			_ds.clickable = '.dz-message';
			_ds.previewsContainer = '.dropzone-previews';
			_ds.dictCancelUpload = '×';
			_ds.dictRemoveFile = '×';
			_ds.timeout = 180000;

			_ds.fallback = function ()
			{
				$('.dropzone-enabled').remove();
			};

			_ds.init = function ()
			{
				var dz = this;
				this.on("complete", function (file)
				{
					//console.log('initDropzone:complete');
					//console.log(file);
					//console.log('[in:w-' + file.width + ',h-' + file.height + ',s-' + file.size + '][sent:w-,h-,s-' + ((typeof file.upload !== 'undefined') ? file.upload.total : '') + ']');
					preventFormSubmit(this);
				});
				this.on("success", function (file, name_tmp)
				{
					//console.log('initDropzone:success:name_tmp:' + name_tmp);
					//console.log(file);
					if (typeof name_tmp !== 'undefined' && name_tmp.length < 100)
					{
						file.name_tmp = name_tmp;
					}
					preventFormSubmit(this);
				});
				this.on("error", function (file, msg)
				{
					this.removeFile(file);
					preventFormSubmit(this);
					//console.log('initDropzone:error:' + msg);
					//console.log(file);
				});
				this.on("canceled", function (file)
				{
					this.removeFile(file);
					preventFormSubmit(this);
					submit_form_after_upload = false;
					//console.log('initDropzone:canceled');
					//console.log(file);
				});
				this.on("addedfile", function (file)
				{
					setFormSubmit(false);
					//console.log('initDropzone:addedfile');
				});
				this.on("sending", function (file, xhr, formData)
				{
					// '?action=img&image_token=' + image_token;
					formData.append('image_token', image_token);
					formData.append('action', 'img');
				});
				this.on("removedfile", function (file)
				{
					var name_tmp = (typeof file.name_tmp !== 'undefined') ? file.name_tmp : file.name;
					var id = (typeof file.id !== 'undefined') ? file.id : 0;
					//console.log('initDropzone:removedfile');
					//console.log(file);
					$.ajax({
						type: 'POST',
						url: url,
						data: {action: "img_remove", image_token: image_token, file: name_tmp, id: id},
						dataType: 'html'
					});
					preventFormSubmit(this);
					submit_form_after_upload = false;
				});


				//add existing files 
				// add mockfiles 
				for (x in mockFiles)
				{
					// Create the mock file:					
					var mockFile = mockFiles[x];
					mockFile.accepted = true;

					// Call the default addedfile event handler
					this.files.push(mockFile);
					this.emit("addedfile", mockFile);

					// And optionally show the thumbnail of the file:
					this.emit("thumbnail", mockFile, mockFile.imageUrl);

					// Make sure that there is no progress bar, etc...
					this.emit("complete", mockFile);
					//this.emit("success", mockFile);

				}

				if (this.options.maxFiles <= this.getAcceptedFiles().length)
				{
					// max files reached 
					$mydropzone.addClass('dz-max-files-reached');
					//console.log('maxfiles reached');
				}


			};
			$mydropzone.dropzone(_ds);
			console.log('initDropzone:set');
		});
	}
}


/**
 * version 1.0.1 (17.04.2018)
 * http://classibase.com
 * 
 * It will replace existing single select box with chained select box. Data is parsed from ids and multiple select boxes created.
 * 
 * example ids:
 * var ids={name:"category_id",selected_id:"41",root_title:"Select category",arr:{"parent_0":{"id_1":"Transport","id_2":"Property","id_4":"Jobs","id_50":"Services","id_55":"Stuff for sale"},"parent_1":{"id_31":"Cars","id_33":"Car parts","id_32":"Motor bikes","id_30":"Vans, trucks"},"parent_2":{"id_34":"Flats and hauses for sale","id_35":"Flats and hauses for rent","id_36":"Office for rent"},"parent_4":{"id_41":"Nursing","id_42":"Teaching","id_43":"Data entry, office clerk","id_44":"Programming","id_45":"Security","id_46":"Constrtuction","id_47":"Lawyer","id_48":"Accountant"},"parent_50":{"id_51":"Tuition","id_52":"Building, repairing house","id_53":"Computing, telephony","id_54":"Entertainment"},"parent_55":{"id_56":"Computers, Games","id_57":"Phones","id_58":"Baby products","id_59":"Musical instruments","id_60":"Household, furniture","id_61":"Building, renovaton","id_62":"Food products"}}};
 * 
 * usage:
 * chainSelect.autoinit();
 * or 
 * chainSelect.init(ids);
 */
var chainSelect = {
	version: '1.0.1',
	autoinit: function ()
	{
		if (typeof chain_location_id != 'undefined')
		{
			chainSelect.init(chain_location_id);
		}
		if (typeof chain_category_id != 'undefined')
		{
			chainSelect.init(chain_category_id);
		}
	},
	init: function (ids)
	{
		if (typeof ids != 'undefined')
		{
			ids_selects = '';
			for (x in ids.arr)
			{
				ids_select = '';
				for (y in ids.arr[x])
				{
					ids_select += '<option value="' + y.replace('id_', '') + '">' + ids.arr[x][y] + '</option>';
				}
				ids_selects += '<select name="' + ids.name + '" id="' + ids.name + '" class="' + x + '"><option value="' + x.replace('parent_', '') + '">' + ids.root_title + '</option>' + ids_select + '</select> ';
			}
			var $chain_select = $('<span class="chain_select">' + ids_selects + '</span>');
			$('#' + ids.name).after($chain_select).remove();

			var $selects = $('select[name="' + ids.name + '"]', $chain_select);
			$chain_select.on('change', 'select[name="' + ids.name + '"]', function ()
			{
				var $me = $(this);
				var id = $me.val();
				chainSelect.display($selects, id);
			});
			chainSelect.display($selects, ids.selected_id);
		}
	},
	display: function ($selects, id)
	{
		$selects.prop('disabled', true).hide();
		var $child = $selects.filter('.parent_' + id);
		if ($child.length)
		{
			var child_val = $child.val();
			$child.prop('disabled', false);
			if (child_val > 0 && child_val != id)
			{
				// chils has value then start chain from displayign child.
				return chainSelect.display($selects, child_val);
			}
			else
			{
				$child.show();
			}
		}
		// start displayign select which contains given value
		chainSelect.displayLoop($selects, id);
	},
	displayLoop: function ($selects, id)
	{
		// display select which contains given value
		var $sel = $('option[value="' + id + '"]:first', $selects.not('.parent_' + id)).parent('select:first');
		var parent_id;
		if ($sel.length)
		{
			$sel.prop('disabled', false).show().val(id)
			parent_id = $sel.prop('class');
		}
		//var parent_id = $selects.has('option[value="' + id + '"]:first').prop('disabled', false).show().val(id).prop('class');
		if (typeof parent_id != 'undefined')
		{
			parent_id = parent_id.replace('parent_', '');
			if (parent_id > 0)
			{
				// move child after parent 
				$selects.filter('.parent_' + parent_id).after($selects.filter('.parent_' + id));
				// display parent select
				chainSelect.displayLoop($selects, parent_id);
			}
		}
	}
};

var cb = {
	version: '1',
	loaded: [],
	init: function ()
	{
		// init all 
		cb.initSearchFilter();
		cb.initToggle();
		cb.initReport();
		cb.initStats();
		cb.initMiniTable();
		cb.initSlider();
		cb.initCarousel();
		cb.initGallery();
		cb.initQRcode();
		cb.initSortableTable();
		cb.phone.init();
		cb.initBack();
		// init PWA
		cb.pwa.init();
	},
	throttle: function (fnc, name, wait_time)
	{
		if (typeof wait_time === 'undefined')
		{
			wait_time = 500;
		}
		if (typeof wait_time === 'undefined')
		{
			name = 'throttle';
		}

		if (typeof window.arr_throttle === 'undefined')
		{
			window.arr_throttle = [];
		}
		clearTimeout(window.arr_throttle[name]);
		window.arr_throttle[name] = setTimeout(fnc, wait_time);
	},
	isV2Enabled: function ()
	{
		return ($('body.e_jqd').length > 0);
	},
	select: {
		initClickHandlerDone: false,
		ind: 0,
		init: function ()
		{
			console.log('cb.select.init');
			// apply to all select and input fields 
			cb.select.convert($('select,input[data-selectalt]'));
		},
		convert: function ($sel)
		{
			if (!cb.isV2Enabled())
			{
				// jqdropdown is not supported
				return false;
			}

			// convert all select boxes to popper version
			console.log('cb.select.convert');
			if ($sel.length)
			{
				// define click handler
				cb.select.initClickHandler();
				// convert selectboxes to popper 
				$sel.each(function (i)
				{
					var $me = $(this);
					var id = cb.select.nextAvailableId();
					// main template
					var $html = $('<div class="select_alt input">'
							+ '<div class="select_alt_text" data-jq-dropdown="#' + id + '"></div>'
							+ '<input type="hidden" class="select_alt_val" name="' + $me.attr('name') + '" id="' + $me.attr('id') + '" />'
							+ '<div id="' + id + '" class="jq-dropdown jq-dropdown-relative"><ul class="jq-dropdown-menu"></ul></div>'
							+ '</div>');
					var $dropdown = $html.find('.jq-dropdown');
					// jq-dropdown-tip


					// if it is input then add clone to html 
					if ($me.is('input'))
					{
						$html.find('input').remove();
						var $me_clone = $me.clone(true);
						$me_clone.addClass('select_alt_val').attr('type', 'hidden');
						$html.append($me_clone);
						// show current text 
						var select_alt_text = $me.data('rootname') || $me.data('currentname') || ' ';
						$html.find('.select_alt_text').text(select_alt_text);
						//load external data then continue 
						var url = $me.data('src');
						// load data then populate ul 
						cb.loadData(url, function (data)
						{
							/*
							 <input name="category_id" value="1" 
							 data-src="/url/to/data.json"
							 data-key="category"
							 data-selectalt="1"
							 data-rootname="All categories"
							 data-currentname="Example category"
							 data-allpattern="All {name}"
							 data-allallow="1"
							 class="display-none" >
							 */
							// populate with data 
							var myData = data[$me.data('key')];
							var opt = cb.select.recursiveList(myData, 0);
							//console.log(myData);
							//console.log(opt);
							if (opt)
							{
								if ($me.data('rootname').length)
								{
									var root_disabled = '';
									if ($me.attr('required'))
									{
										root_disabled = ' class="is-disabled"';
									}
									opt = '<li' + root_disabled + '><a data-v="">' + $me.data('rootname') + '</a></li>' + opt;
								}

								$dropdown.find('.jq-dropdown-menu').append($(opt));
								// set name 
								var val = $me.val();
								var $val_a = $dropdown.find('a[data-v="' + $me.val() + '"]');
								if (val && $val_a.length)
								{
									select_alt_text = $val_a.text();
								}
								$html.find('.select_alt_text').text(select_alt_text);
								// allow all then add all value
								if ($me.data('allallow'))
								{
									$dropdown.find('ul').each(function ()
									{
										var $ul = $(this);
										var $parent = $ul.parents('li:first').find('a:first');
										var parent_text = '';
										if ($parent.length)
										{
											parent_text = $parent.text();
											if ($me.data('allpattern'))
											{
												parent_text = $me.data('allpattern').replace("{name}", parent_text);
											}
											else
											{
												parent_text = '<b>' + parent_text + '</b>';
											}
											$ul.prepend('<li><a data-v="' + $parent.data('v') + '">' + parent_text + '</a></li>');
										}
									});
								}

								// mark disabled values 
								if ($me.attr('data-disable'))
								{
									var disabled_ids = $me.attr('data-disable');
									disabled_ids = disabled_ids.split(',');
									var $a_disabled = $dropdown.find('[data-v="' + disabled_ids.join('"],[data-v="') + '"],'
											+ '[data-n="' + disabled_ids.join('"],[data-v="') + '"]');
									$a_disabled.each(function ()
									{
										var $me = $(this);
										var $li = $me.parents('li:first');
										$li.addClass('is-disabled');
									});
								}


								// add to document 
								$me.after($html);
								$me.remove();
								console.log('select.convert:dynamic:completed');
							}
						});
					}
					else
					{
						// it is select 
						// add current value to hidden text 				
						$html.find('input').attr('name', $me.attr('name')).val($me.val());
						$html.find('.select_alt_text').text($('option:selected', $me).text());
						// populate popup values 
						var opt = '';
						$me.find('option').map(function (index, elem)
						{
							opt += '<li><a data-v="' + $(elem).val() + '">' + $(elem).text() + '</a></li>';
						});
						$dropdown.find('.jq-dropdown-menu').append($(opt));
						// add to document 
						$me.after($html);
						$me.remove();
						console.log('select.convert:static:completed');
					}
				});
			}
		},
		initClickHandler: function ()
		{
			if (cb.select.initClickHandlerDone)
			{
				return;
			}
			cb.select.initClickHandlerDone = true;
			// show active value when opened
			$(document).on('show', '.jq-dropdown', function (event, dropdownData)
			{
				// highlight currect value
				cb.select.showSelected(dropdownData.jqDropdown, dropdownData.trigger);
			}).on('hide', '.jq-dropdown', function (event, dropdownData)
			{
				// blur 
				cb.select.blur();
			});
			// perform select action by setting name and value on click. ONLY for select_alt 
			$(document).on('click', '.select_alt .jq-dropdown a', function (e)
			{
				var $me = $(this);
				if ($me.not('.has_submenu').length)
				{
					var $jqdropdown = $me.parents('.jq-dropdown:first');
					var $select_alt_text = $jqdropdown.data('jq-dropdown-trigger');
					var $select_alt = $select_alt_text.parents('.select_alt:first');
					if ($select_alt.length)
					{
						// set selected value and trigger change event 
						$select_alt.find('input.select_alt_val').val($me.data('v')).change();
						$select_alt_text.text($me.text());
						// check if value required and valid 
						if ($select_alt.find('input.select_alt_val').is('[required]'))
						{
							if (!$me.data('v'))
							{
								$select_alt.addClass('invalid');
							}
							else
							{
								$select_alt.removeClass('invalid');
							}
						}
						// apply active state 
						$jqdropdown.find('.active').removeClass('active');
						$me.addClass('active');
					}
				}
			});
			$(document).on('click active', 'label', function (e)
			{
				console.log('on(click active label)');
				var $me = $(this);
				var label_for = $me.attr('for');
				if (typeof label_for !== 'undefined')
				{
					var $input = $('input[type="hidden"][name="' + label_for + '"]');
					if ($input.length)
					{
						var $select_alt_text = $input.parents('.select_alt:first').find('.select_alt_text');
						$select_alt_text[0].click();
						//$select_alt_text.trigger('click');
						console.log('$select_alt_text');
						console.log($select_alt_text);
					}
				}
			});
		},
		showSelected: function ($jqDropdown, $trigger)
		{

			// find selected value and mark as active 
			var $select_alt = $trigger.parents('.select_alt:first');
			cb.select.blur();
			$select_alt.addClass('focus');
			var $input = $select_alt.find('input.select_alt_val');
			var val = $input.val();
			if (val)
			{
				var $active = $jqDropdown.find('a[data-v="' + val + '"]:last');
				if ($active.length)
				{
					// close all other child and open this one 
					$jqDropdown.find('ul.current').removeClass('current');
					$jqDropdown.find('a.active').removeClass('active');
					$active.addClass('active');
					$active.parentsUntil('ul.jq-dropdown-menu', 'ul').addClass('current');
					$jqDropdown.find('ul').not('.current,.jq-dropdown-menu').slideUp(0);
					$jqDropdown.find('ul.current').slideDown(0, function ()
					{
						// trigger resize to reposition dropdown 
						$(window).trigger('resize');
					});
				}
			}
		},
		blur: function ()
		{
			// blur all focused select_alt
			$('.select_alt.focus').removeClass('focus');
		},
		nextAvailableId: function ()
		{
			// check if index available 
			while ($('#jq-dropdown-' + cb.select.ind).length)
			{
				cb.select.ind++;
			}
			var id = 'jq-dropdown-' + cb.select.ind;
			cb.select.ind++;
			return id;
		},
		recursiveList: function (data, parent_id)
		{
			var r = '', child = '', k = 'p' + parent_id, title = '';
			if (typeof data[k] !== 'undefined')
			{
				for (y in data[k])
				{
					if (data[k][y])
					{
						title = data[k][y];
						r += '<li><a data-v="' + y.replace('i', '') + '">';
						child = cb.select.recursiveList(data, y.replace('i', ''));
						if (child)
						{
							child = '<ul>' + child + '</ul>';
						}

						r += title + '</a>' + child + '</li>';
					}
				}
			}
			return r;
		}
	},
	loadData: function (src, fncDone, fncFail)
	{
		if (cb.loaded[src])
		{
			if (cb.loaded[src].data)
			{
				// use loaded data
				fncDone(cb.loaded[src].data);
			}
			else
			{
				// data is still loading add callbacks to queue
				if (typeof fncDone === 'function')
				{
					cb.loaded[src].fncDone.push(fncDone);
				}
				if (typeof fncFail === 'function')
				{
					cb.loaded[src].fncFail.push(fncFail);
				}
			}
		}
		else
		{
			// prevent multiload same data. so store callbacks and resume once resource finished loading 
			// create callback array so next call will add to this array and wait
			cb.loaded[src] = {fncDone: [], fncFail: []};
			if (typeof fncDone === 'function')
			{
				cb.loaded[src].fncDone.push(fncDone);
			}
			if (typeof fncFail === 'function')
			{
				cb.loaded[src].fncFail.push(fncFail);
			}
			// load data then execute fnc
			$.ajax({
				dataType: "json",
				cache: true,
				url: src
			}).done(function (data)
			{
				console.log('load.done:' + src);
				cb.loaded[src].data = data;
				// call all stacked functions
				for (x in cb.loaded[src]['fncDone'])
				{
					cb.loaded[src]['fncDone'][x](data);
				}
			}).fail(function ()
			{
				console.log('load.fail:' + src);
				// call all stacked functions
				for (x in cb.loaded[src]['fncFail'])
				{
					cb.loaded[src]['fncFail'][x]();
				}
			}).always(function ()
			{
				// remove all callbacks
				cb.loaded[src]['fncFail'] = [];
				cb.loaded[src]['fncDone'] = [];
			});
		}
	},
	cf: {
		/**
		 * 
		 * @param {type} options
		 * @returns {undefined}
		 */
		init: function (options)
		{
			console.log('cf.init');
			// load data first 
			cb.loadData(options.datasrc, function (data)
			{
				options.data = data;
				var parent = '';
				if (typeof options.parent !== 'undefined')
				{
					parent = options.parent + ' ';
				}
				$(document).on('change', parent + options.loc + ',' + parent + options.cat, function ()
				{
					console.log('loc or cat changed');
					// generate custom fields related to trigger only
					// because content might be cloned dynamicly and action called later 
					var $me = $(this);

					if (typeof options.parent !== 'undefined')
					{
						options.$parent = $me.parents(options.parent).first();
					}
					cb.cf.render(options);
				});
				cb.cf.render(options);
			});
		},
		render: function (options)
		{
			console.log('cf.render');
			var $parent = $(document);
			if (typeof options.$parent !== 'undefined')
			{
				$parent = options.$parent;
			}
			else if (typeof options.parent !== 'undefined')
			{
				$parent = $(options.parent);
			}

			var $loc = $parent.find(options.loc);
			var $cat = $parent.find(options.cat);
			var $target = $parent.find(options.target);
			var loc_id = $loc.val() * 1;
			var cat_id = $cat.val() * 1;
			var form_type = options.form_type || '';
			// store existing cf values 
			if (typeof options.existing_values === 'undefined')
			{
				options.existing_values = {};
			}
			$target.find('input').each(function ()
			{
				var $me = $(this);
				var val = $me.val();
				var name = $me.attr('name');
				var type = $me.attr('type');
				switch (type)
				{
					case 'radio':
					case 'checkbox':
						// checked radio value 
						val = $target.find('input[name="' + name + '"]:checked').val();
						console.log('input[name="' + name + '"]:checked -> ' + val);
						if (typeof val === 'undefined')
						{
							val = '';
						}
						break;
				}
				if (val.length || typeof options.existing_values[name] !== 'undefined')
				{
					options.existing_values[name] = val;
				}
			});
			// find defined cf
			var id, af, af_name, af_id, af_val, af_unit, af_label, af_input, af_help, html, x;
			var cf_key = cb.cf.getCFkey(options.data, loc_id, cat_id, 'cf');
			var pm_key = false;
			if (form_type !== 'search')
			{
				pm_key = cb.cf.getCFkey(options.data, loc_id, cat_id, 'pm');
				//console.log('pm_key:' + pm_key);
			}
			console.log('cf_key:' + cf_key);
			// skip if same as last cf
			if (typeof options.last_cf_key !== 'undefined')
			{
				if (options.last_cf_key === {cf_key: cf_key, pm_key: pm_key})
				{
					// same cf do nothing 
					console.log('cf.render.options.SKIP_SAME');
					return;
				}
			}
			options.last_cf_key = {cf_key: cf_key, pm_key: pm_key};
			if (form_type === 'search')
			{
				html = cb.cf.populateFormSearch(cf_key, options);
			}
			else
			{
				html = cb.cf.populateFormInput(cf_key, options);
				html += cb.cf.populateFormInputPayment(pm_key, options);
			}



			// add generated inputs to page
			$target.html(html);
			// convert added selects 
			cb.select.convert($target.find('select'));
			// call onChange callback if set 
			if (typeof options.onChange !== 'undefined')
			{
				options.onChange();
			}

			//console.log($target);
			//console.log('html');
			//console.log(html);
			//console.log('options');
			//console.log(options);
			//console.log('cf.render.options.END');
		},
		populateFormInput: function (cf_key, options)
		{
			console.log('populateFormInput');
			var id, af, af_name, af_id, af_val, af_label, af_input, af_help, html, x;
			// populate cf
			html = '';
			if (cf_key !== false)
			{
				// has cf 
				console.log(options.data.cf[cf_key]);
				for (x in options.data.cf[cf_key])
				{
					if (typeof options.data.af[x] === 'undefined')
					{
						// no af field found, then skip this record.
						// used if cat-loc par has removed all custom fields defined by parent cat-loc pait selection.
						// for example price can be set globally and removed from specific category.
						continue;
					}

					id = x.replace('i', '');

					af = options.data.af[x];
					af_name = 'cf[' + id + ']';
					if (typeof options.id_prefix === 'undefined')
					{
						af_id = af_name;
					}
					else
					{
						af_id = options.id_prefix + af_name;
					}

					af_label = '<label for="' + af_id + '">' + af.n + '</label>';
					if (af.h)
					{
						af_help = '<span class="form-help">' + af.h + '</span>';
					}
					else
					{
						af_help = '';
					}
					if (typeof options.existing_values[af_name] === 'undefined')
					{
						af_val = '';
					}
					else
					{
						af_val = options.existing_values[af_name];
					}
					af_input = '';
					switch (af.t)
					{
						case 'price':
						case 'number':
							if (typeof af.v === 'undefined' || af.v === '')
							{
								af_input = '<input type="number" name="' + af_name + '" id="' + af_id + '" value="' + af_val + '" class="input">';
							}
							else
							{
								af_input = '<span class="input-group">'
										+ '<input type="number" name="' + af_name + '" id="' + af_id + '" value="' + af_val + '" class="input">'
										+ '<span class="button addon">' + af.v + '</span>'
										+ '</span>';
							}
							break;
						case 'checkbox':
							af_input = '<span class="adfield_checkbox">';
							// select first radio by default if no other option selected
							if (af.afv)
							{
								for (y in af.afv)
								{
									var option_id = (y.replace('i', '') * 1);
									var af_name_ = af_name + '[' + option_id + ']';
									var checked = '';
									// if saved value then check
									if (typeof options.existing_values[af_name_] !== 'undefined')
									{
										checked = 'checked="checked"';
									}

									af_input += '<label class="input-checkbox"> '
											+ '<input type="checkbox" name="' + af_name_ + '" value="' + option_id + '" ' + checked + ' />'
											+ '<span class="checkmark"></span> '
											+ af.afv[y] + '</label>';
								}
							}
							af_input += '</span>';
							break;
						case 'radio':
							af_input = '<span class="adfield_radio">';
							// select first radio by default if no other option selected
							var first_radio = 'checked="checked"';
							if (af.afv)
							{
								for (y in af.afv)
								{
									var option_id = (y.replace('i', '') * 1);
									var af_id_ = af_id + '[' + option_id + ']';
									var checked = first_radio;
									// if saved value then check
									if (af_val * 1 === option_id)
									{
										checked = 'checked="checked"';
									}

									af_input += '<label class="input-radio"> '
											+ '<input type="radio" name="' + af_name + '" value="' + option_id + '" ' + checked + ' />'
											+ '<span class="checkmark"></span> '
											+ af.afv[y] + '</label>';
									first_radio = '';
								}
							}
							af_input += '</span>';
							break;
						case 'dropdown':
							// create select
							af_input = '<select name="' + af_name + '" id="' + af_id + '">';
							if (af.afv)
							{
								for (y in af.afv)
								{
									af_input += '<option value="' + (y.replace('i', '') * 1) + '" ' + (af_val === y.replace('i', '') ? 'selected="selected"' : '') + '>' + af.afv[y] + '</option>';
								}
							}
							af_input += '</select>';
							break;
						default:
							var input_types = {
								url: 'url',
								email: 'email',
								video_url: 'url'
							};
							var type = input_types[af.t] || 'text';
							af_input = '<input type="' + type + '" name="' + af_name + '" id="' + af_id + '" value="' + af_val + '" class="input">';
					}
					html += options.template
							.replace('${label}', af_label)
							.replace('${input}', af_input)
							.replace('${help}', af_help);
				}
			}
			return html;
		},
		populateFormInputPayment: function (cf_key, options)
		{
			console.log('populateFormInput');
			var af_label, af_input, af_help, html, x;
			// populate cf
			html = '';
			if (cf_key !== false && typeof options.templatepayment !== 'undefined')
			{
				// has cf 
				console.log(options.data.pm[cf_key]);
				for (x in options.data.pm[cf_key])
				{
					if (options.data.pm[cf_key][x] * 1 > 0)
					{
						// has valid price
						//af_label = '<label>' + options.templatepayment[x].title + '</label>';
						af_label = options.templatepayment[x].title;
						af_input = options.templatepayment[x].input.replace('{price}', options.data.pm[cf_key][x]);
						af_help = '';
						html += options.template
								.replace('${label}', af_label)
								.replace('${input}', af_input)
								.replace('${help}', af_help);
					}
				}
			}
			return html;
		},
		populateFormSearch: function (cf_key, options)
		{
			//console.log('populateFormSearch');
			var id, af, af_name, af_id, af_val, af_label, af_input, af_help, x;
			var lng_from = options.lng.from || 'from';
			var lng_to = options.lng.to || 'to';
			// populate cf
			var html = '';
			if (cf_key !== false)
			{
				// has cf 
				//console.log(options.data.cf[cf_key]);
				for (x in options.data.cf[cf_key])
				{
					if (options.data.cf[cf_key][x] !== '1')
					{
						// field is not visible in search 
						continue;
					}
					id = x.replace('i', '');
					af = options.data.af[x];
					af_name = 'cf[' + id + ']';
					if (typeof options.id_prefix === 'undefined')
					{
						af_id = af_name;
					}
					else
					{
						af_id = options.id_prefix + af_name;
					}

					af_label = '<label for="' + af_id + '">' + af.n + '</label>';
					if (af.h)
					{
						af_help = '<span class="form-help">' + af.h + '</span>';
					}
					else
					{
						af_help = '';
					}
					if (typeof options.existing_values[af_name] === 'undefined')
					{
						af_val = '';
					}
					else
					{
						af_val = options.existing_values[af_name];
					}
					af_input = '';
					switch (af.t)
					{
						case 'price':
						case 'number':
							// fromo to fileds 
							var af_name_from, af_name_to, af_val_from, af_val_to, af_input_addon;
							af_name_from = af_name + '[from]';
							af_name_to = af_name + '[to]';
							af_val_from = '';
							af_val_to = '';
							af_input_addon = '';
							if (typeof options.existing_values[af_name_from] !== 'undefined')
							{
								af_val_from = options.existing_values[af_name_from];
							}

							if (typeof options.existing_values[af_name_to] !== 'undefined')
							{
								af_val_to = options.existing_values[af_name_to];
							}

							if (typeof af.v !== 'undefined' && af.v !== '')
							{
								af_input_addon = '<span class="button addon">' + af.v + '</span>';
							}

							af_input = '<span class="input-group">'
									+ '<input type="number" name="' + af_name_from + '" id="' + af_id + '" value="' + af_val_from + '" placeholder="' + lng_from + '" class="input">'
									+ '<input type="number" name="' + af_name_to + '" id="' + af_name_to + '" value="' + af_val_to + '" placeholder="' + lng_to + '" aria-label="' + lng_to + '" class="input">'
									+ af_input_addon
									+ '</span>';
							break;
						case 'checkbox':
							af_input = '<span class="adfield_checkbox">';
							// select first radio by default if no other option selected
							if (af.afv)
							{
								for (y in af.afv)
								{
									var option_id = (y.replace('i', '') * 1);
									var af_name_ = af_name + '[' + option_id + ']';
									var checked = '';
									// if saved value then check
									if (typeof options.existing_values[af_name_] !== 'undefined')
									{
										checked = 'checked="checked"';
									}

									af_input += '<label class="input-checkbox"> '
											+ '<input type="checkbox" name="' + af_name_ + '" value="' + option_id + '" ' + checked + ' />'
											+ '<span class="checkmark"></span> '
											+ af.afv[y] + '</label>';
								}
							}
							af_input += '</span>';
							break;
						case 'radio':
							af_input = '<span class="adfield_radio">';
							// select first radio by default if no other option selected
							var first_radio = 'checked="checked"';
							if (af.afv)
							{

								// show all field first 
								var checked = first_radio;
								af_input += '<label class="input-radio"> '
										+ '<input type="radio" name="' + af_name + '" value="' + option_id + '" ' + checked + ' />'
										+ '<span class="checkmark"></span> '
										+ options.lng.all + '</label>';
								first_radio = '';


								for (y in af.afv)
								{
									var option_id = (y.replace('i', '') * 1);
									//var af_id_ = af_id + '[' + option_id + ']';
									checked = first_radio;
									// if saved value then check
									if (af_val * 1 === option_id)
									{
										checked = 'checked="checked"';
									}

									af_input += '<label class="input-radio"> '
											+ '<input type="radio" name="' + af_name + '" value="' + option_id + '" ' + checked + ' />'
											+ '<span class="checkmark"></span> '
											+ af.afv[y] + '</label>';
									first_radio = '';
								}
							}
							af_input += '</span>';
							break;
						case 'dropdown':
							// create select
							af_input = '<select name="' + af_name + '" id="' + af_id + '">';
							// add field name as empty value 
							af_label = '';
							af_input += '<option value="">' + af.n + '</option>';
							if (af.afv)
							{
								for (y in af.afv)
								{
									af_input += '<option value="' + (y.replace('i', '') * 1) + '" ' + (af_val === y.replace('i', '') ? 'selected="selected"' : '') + '>' + af.afv[y] + '</option>';
								}
							}
							af_input += '</select>';
							break;
						default:
							var input_types = {
								url: 'url',
								email: 'email',
								video_url: 'url'
							};
							var type = input_types[af.t] || 'text';
							af_input = '<input type="' + type + '" name="' + af_name + '" id="' + af_id + '" value="' + af_val + '" class="input">';
					}
					html += options.template
							.replace('${label}', af_label)
							.replace('${input}', af_input)
							.replace('${help}', af_help);
				}
			}
			return html;
		},
		buldCatLocReverseArr: function (data)
		{
			if (!data.category_reverse)
			{
				data.category_reverse = {};
				for (x in data.category)
				{
					for (y in data.category[x])
					{
						data.category_reverse[y] = x.replace('p', '') * 1;
					}
				}
			}
			if (!data.location_reverse)
			{
				data.location_reverse = {};
				for (x in data.location)
				{
					for (y in data.location[x])
					{
						data.location_reverse[y] = x.replace('p', '') * 1;
					}
				}
			}
		},
		getCFkey: function (data, loc_id, cat_id, data_key)
		{
			cb.cf.buldCatLocReverseArr(data);
			// build parent cat array 
			var cat_parents = [cat_id];
			var cat_id_parent = cat_id;
			while (data.category_reverse['i' + cat_id_parent])
			{
				cat_id_parent = data.category_reverse['i' + cat_id_parent];
				cat_parents.push(cat_id_parent);
			}
			if (cat_id !== 0)
			{
				cat_parents.push(0);
			}

			var loc_parents = [loc_id];
			var loc_id_parent = loc_id;
			while (data.location_reverse['i' + loc_id_parent])
			{
				loc_id_parent = data.location_reverse['i' + loc_id_parent];
				loc_parents.push(loc_id_parent);
			}
			if (loc_id !== 0)
			{
				loc_parents.push(0);
			}
			var cf_id = '';
			for (x in loc_parents)
			{
				for (y in cat_parents)
				{
					cf_id = loc_parents[x] + '_' + cat_parents[y];
					if (data[data_key][cf_id])
					{
						// return matching cf id
						return cf_id;
					}
				}
			}
			// no custom fields found
			return false;
		}

	},
	setupItemDropdown: function ()
	{
		/* dropdown menu for items in admin */
		$items = $('#jq-dropdown-item').not('.setupItemDropdown');
		if ($items.length)
		{
			// save them as setup 
			$('#jq-dropdown-item').addClass('setupItemDropdown');

			// perform form actions
			$items.on('click', 'li a[data-v]', function ()
			{
				var $me = $(this);
				var datav = $me.attr('data-v');
				var confirmed = true;
				if (datav.length)
				{
					var jqDropdown = $me.parents('.jq-dropdown:first');
					var trigger = jqDropdown.data('jq-dropdown-trigger');
					var $item = trigger.parents('.item:first,.bulk_actions:first');
					var $form = $item.parents('form:first');

					// used when selecting for bulk actions 
					var $parent = $item.parents('ul:first');
					var $items = $parent.find('.item');
					var $menus = $items.find('.controls [data-jq-dropdown="#jq-dropdown-item"]');
					var $checks = $items.find('.controls .input-checkbox');


					var countChecked = function ()
					{
						$parent.find('.bulk_actions_count').text($items.find(':checkbox:checked').length);
					};
					var clickSelectAll = function ()
					{
						$items.find(':checkbox[name="ad[]"]').prop('checked', true);
						countChecked();
					};
					var clickSelectNone = function ()
					{
						$items.find(':checkbox[name="ad[]"]').prop('checked', false);
						// hide bulk actions 
						$menus.show();
						$checks.hide();
						$parent.find('.bulk_actions').hide();
						// remove event handler 
						$parent.off('.item_select');
					};
					var clickSelectItem = function ()
					{
						var $me_item = $(this);
						var $check = $me_item.find(':checkbox[name="ad[]"]');

						$check.prop('checked', !$check.prop('checked'));

						countChecked();
						return false;
					};

					switch (datav)
					{
						case 'select':
							// hide all menu buttons 
							$menus.hide();

							// show all checkboxes
							$checks.removeClass('display-none').show();

							// check current element 
							$item.find(':checkbox[name="ad[]"]').prop('checked', true);
							countChecked();

							// make row clickable to check 
							$parent.on('click.item_select', '.item', clickSelectItem);

							// add select all event handlers 
							$parent.on('click.item_select', '.bulk_actions .select_all', clickSelectAll);
							$parent.on('click.item_select', '.bulk_actions .select_none', clickSelectNone);

							// add bulk actions menu to top, make sticky
							$parent.find('.bulk_actions').removeClass('display-none').show();

							break;
						case 'select_all':
							clickSelectAll();
							break;
						case 'select_none':
							clickSelectNone();
							break;
						default:
							// show confirmation if needed
							if ($me.is('[data-confirm]'))
							{
								confirmed = confirm($me.attr('data-confirm'));
							}

							// submit form 
							if (confirmed)
							{
								// check only if non checked 
								if ($items.find(':checkbox:checked').length < 1)
								{
									$item.find(':checkbox[name="ad[]"]').prop('checked', true);
								}
								$form.find('#bulk_actions').val(datav);
								$form.submit();
							}
					}




				}
			});
			// append idividual menu 
			$items.on('show', function ()
			{
				var $me = $(this);
				var trigger = $me.data('jq-dropdown-trigger');
				var $item = trigger.parents('.item:first,.bulk_actions:first');
				var $append_ul = $item.find('.jq-dropdown-item-append,.jq-dropdown-item-prepend');

				// remove all appended items 
				$me.find('.appended').remove();
				// reset all hidden items 
				$me.find('li').removeClass('display-none');

				$append_ul.each(function ()
				{
					var $me_append = $(this);
					var $append = $me_append.find('li').clone();

					if ($append.length)
					{
						$append.addClass('appended');
						if ($me_append.is('.jq-dropdown-item-append'))
						{
							// append
							$me.find('ul:first').append($append);
						}
						else
						{
							// prepend
							$me.find('ul:first').prepend($append);
						}
					}

					// hide not related items 
					if ($me_append.data('hide'))
					{
						var arr_hide = $me_append.data('hide').split(',');

						//$me.find('li').has('a[data-v="' + arr_hide.join('"],a[data-v="') + '"]').addClass('display-none');
						$me.find('a[data-v="' + arr_hide.join('"],a[data-v="') + '"]').each(function ()
						{
							$(this).parents('li:first').addClass('display-none');
						});
						// need to resize menu 
						$(window).resize();
					}
				});

				// if it is bulk actions then hide values appeared in all selected items 
				if ($item.is('.bulk_actions'))
				{
					var $parent = $item.parents('ul:first');
					var $items_checked = $parent.find('.item').has(':checkbox:checked');
					var arr_hide_all = [];
					$items_checked.each(function (i)
					{
						var $me_item = $(this);
						var $append_ul_many = $me_item.find('.jq-dropdown-item-append,.jq-dropdown-item-prepend');
						var arr_hide = [];
						// in case it has multiple append elements 
						$append_ul_many.each(function ()
						{
							var $me_append_ul_many = $(this);
							if ($me_append_ul_many.data('hide'))
							{
								var arr_hide_ = $me_append_ul_many.data('hide').split(',');
								arr_hide = arr_hide.concat(arr_hide_);
							}
						});
						arr_hide_all.push(arr_hide);
					});

					// get interception from all selected items
					arr_hide_all = cb.intersectionArray(arr_hide_all);

					// hide given menu options
					if (arr_hide_all.length > 0)
					{
						$me.find('a[data-v="' + arr_hide_all.join('"],a[data-v="') + '"]').each(function ()
						{
							$(this).parents('li:first').addClass('display-none');
						});
						// need to resize menu 
						$(window).resize();
					}
				}

			});
		}
	},
	intersectionArray: function ()
	{
		var result = [];
		var lists;

		if (arguments.length === 1)
		{
			lists = arguments[0];
		}
		else
		{
			lists = arguments;
		}

		for (var i = 0; i < lists.length; i++)
		{
			var currentList = lists[i];
			for (var y = 0; y < currentList.length; y++)
			{
				var currentValue = currentList[y];
				if (result.indexOf(currentValue) === -1)
				{
					if (lists.filter(function (obj)
					{
						return obj.indexOf(currentValue) == -1
					}).length == 0)
					{
						result.push(currentValue);
					}
				}
			}
		}
		return result;
	},
	editSlug: function (target)
	{
		// if multiple items sent
		if (target.length > 1)
		{
			target.each(function ()
			{
				var $me = $(this);
				cb.editSlug($me);
			});
			return false;
		}

		var listen = $(target.data('listen'));
		var id = target.data('editableslug');
		var generate_url = target.data('url');
		var hideclass = target.data('hideclass');
		target.find('.edit_slug').click(edit);
		target.find('.edit_slug_cancel').click(cancel);
		target.find('.edit_slug_ok').click(generate);
		listen.blur(generate);
		function edit()
		{
			//console.log('editSlug.edit');
			target.find('input').removeProp('readonly').data('cancelval', target.find('input').val()).focus();
			target.find('.edit_slug').addClass(hideclass);
			target.find('.edit_slug_ok,.edit_slug_cancel').removeClass(hideclass).appendTo(target);
			return false;
		}
		function generate()
		{
			//console.log('editSlug.generate');
			// if no permalink entered then generate pemalink
			var name = listen.val();
			var slug = target.find('input').val();
			$.post(BASE_URL + generate_url, {id: id, name: name, slug: slug}, function (data)
			{
				try
				{
					var dataObj = jQuery.parseJSON(data);
					// update slug fields
					set(dataObj.slug);
				}
				catch (error)
				{
					alert(data);
				}
			});
			return false;
		}
		function set(str)
		{
			//console.log('editSlug.set');
			target.find('input').val(str).prop('readonly', 'readonly');
			target.find('.edit_slug').removeClass(hideclass).appendTo(target);
			target.find('.edit_slug_ok,.edit_slug_cancel').addClass(hideclass);
		}
		function cancel()
		{
			//console.log('editSlug.cancel');
			var cancelval = target.find('input').data('cancelval');
			set(cancelval);
			return false;
		}
	},
	buttonSwitch: function (selector, options)
	{
		// define default options 
		options = $.extend({
			values: {'0': {title: 'on', cssClass: 'white'}, '1': {title: 'off', cssClass: 'green'}}
		}, options || {});
		$(document).on('click', selector, function (e)
		{
			//var $me = $(e.target);
			var $me = $(this);
			var id = $me.data('id');
			var action = $me.data('switch');
			var url = options.url;
			//console.log('buttonSwitch:' + action + ':' + id);
			if (typeof action === 'undefined' || typeof id === 'undefined')
			{
				//action or id i snot defined 
				console.log('buttonSwitch:[not-defined]');
				return false;
			}
			$me.addClass('loading');
			$.post(BASE_URL + url, {action: action, id: id}, function (data)
			{
				console.log('buttonSwitch:' + url + ':done');
				var data_found = false;
				// check if data is in defined range 
				var values = options.values;
				if (typeof values[action] !== 'undefined')
				{
					// values differ for action type 
					values = values[action];
				}

				for (x in values)
				{
					if (x == data)
					{
						// value is ok. show it 
						var title = values[x].title;
						var cssClass = values[x].cssClass;
						data_found = true;
						break;
					}
				}
				if (data_found)
				{

					// check select type, if it is switch just check it 
					if ($me.is('label.input-switch'))
					{
						console.log('buttonSwitch:checkbox:' + data + ":" + (data * 1 ? true : false));
						// set checkbox state
						$me.find('input:checkbox').prop('checked', data * 1 ? true : false);
					}
					else
					{
						// regular button 
						// remove all other classes from item 
						for (x in values)
						{
							if (typeof values[x].cssClass !== 'undefined')
							{
								$me.removeClass(values[x].cssClass);
							}
						}

						if (typeof cssClass !== 'undefined')
						{
							$me.addClass(cssClass);
						}
						$me.text(title);
					}
				}
				else
				{
					// data not found show error 
					console.log('buttonSwitch:' + url + ':data_fail:' + data);
					alert(data);
				}
			}).fail(function ()
			{
				// request failed 
				console.log('buttonSwitch:' + url + ':fail');
			}).always(function ()
			{
				// remove loading class
				$me.removeClass('loading');
			});
			return false;
		});
	},
	isInViewport: function ($obj, complete)
	{
		/* check if element is in viewport */
		var elementTop = $obj.offset().top;
		var elementBottom = elementTop + $obj.outerHeight();
		var viewportTop = $(window).scrollTop();
		var viewportBottom = viewportTop + $(window).height();
		var ret = false;
		if (complete === true)
		{
			ret = elementBottom < viewportBottom && elementTop > viewportTop;
		}
		else
		{
			ret = elementBottom > viewportTop && elementTop < viewportBottom;
		}

		//console.log('isInViewport('+complete+'):ret:' + ret + ',elementTop:' + elementTop + ',elementBottom:' + elementBottom + ',viewportTop:' + viewportTop + ',viewportBottom:' + viewportBottom);

		return ret;
	},
	isInViewportRight: function ($obj)
	{
		/* check if right of $obj in viewport */
		var elementLeft = $obj.offset().left;
		var elementRight = elementLeft + $obj.outerWidth();
		var viewportWidth = $(window).width();
		var ret = elementRight < viewportWidth;
		//console.log('isInViewportRight:ret:' + ret + ',elementLeft:' + elementLeft + ',elementRight:' + elementRight + ',viewportWidth:' + viewportWidth);

		return ret;
	},
	initSortableTable: function ()
	{
		/*<tr>
		 <th class="table_sort table_sort_number">TOTAL TIME</th>
		 <th class="table_sort table_sort_number">TIME</th>
		 <th class="table_sort table_sort_number">MEMORY</th>
		 <th class="table_sort">DESCRIPTION</th>
		 </tr>*/
		// sort related table by value asc,desc when clicked on th.table_sort
		$(document).on('click', 'table .table_sort', function ()
		{
			var $me = $(this);
			var $parent = $me.parent();
			var $table = $me.parents('table:first');

			// get col number 
			var n = $parent.find('th,td').index($me);
			var is_number = $me.hasClass('table_sort_number');
			var is_descending = $me.data('is_descending') || false;

			if (n >= 0)
			{
				$me.data('is_descending', !is_descending);
				var $table_clone = $table.clone(true);
				// assign table rows to array
				var tbl_arr = [];
				$table_clone.find('tr:not(:first)').each(function (index)
				{
					var $me = $(this);
					var val = $me.find('td').eq(n).text() || '';
					val = is_number ? Number(val) : val.toLowerCase();
					tbl_arr.push({val: val, tr: $me});
				});

				// sort by value 
				tbl_arr.sort(function (a, b)
				{
					var x = a.val;
					var y = b.val;
					if (x < y)
					{
						return -1;
					}
					if (x > y)
					{
						return 1;
					}
					return 0;
				});

				if (is_descending)
				{
					tbl_arr.reverse();
				}


				// remove old rows and add new rows
				$table_clone.find('tr:not(:first)').remove();

				for (x in tbl_arr)
				{
					$table_clone.append(tbl_arr[x].tr);
				}

				// replace original table with clone
				$table.after($table_clone);
				$table.remove();
			}
		});
	},
	initToggle: function ()
	{
		/* setup toggle operation for [data-toggle] defined html components 
		 * USAGE: <button data-target=".suggested_values" data-toggle="cb_slide"> </button>
		 * */
		$('body').on('click', '[data-toggle]', function ()
		{
			var $me = $(this);
			var $terget = $($me.data('target'));
			var toggle = $me.data('toggle');

			if ($terget.is('.display-none'))
			{
				$terget.hide().removeClass('display-none');
			}
			switch (toggle)
			{
				case 'cb_slide':
					$terget.slideToggle('fast');
					$('.cancel', $terget).click(function ()
					{
						$terget.slideToggle('fast');
						return false;
					});
					break;
				case 'cb_hide':
					$terget.toggle();
					$('.cancel', $terget).click(function ()
					{
						$terget.toggle();
						return false;
					});
					break;
				case 'cb_modal':
					if (cb.isV2Enabled())
					{
						cb.modal.init(function ()
						{
							var $terget_clone = $terget.clone(true);
							$terget_clone.show();
							cb.modal.open({
								$content: $terget_clone,
								classClose: '.cancel'
							});
						});
					}
					else
					{
						setupColorbox(function ()
						{
							$terget.show();
							$.colorbox({inline: true, href: $terget, onCleanup: function ()
								{
									$terget.hide();
								}});
							$terget.on('click', '.cancel', function ()
							{
								$.colorbox.close();
								return false;
							});
						});
					}


					break;
				case 'cb_batch':
					// perform ajax call and set result to target
					// loop until get END message 
					var url = $me.data('url');
					if (url.length > 0)
					{
						// define batch function 
						function cb_batch()
						{
							// prevent double processing 
							if ($me.data('processing') != '1')
							{
								$me.data('processing', '1');
								$me.append(' <span class="loading">...</span>');
								$.post(BASE_URL + url, {nounce: nounce})
										.done(function (data)
										{
											// data={text:"10 items left|completed",continue:0|1};
											if (typeof data === 'object')
											{
												// show message
												$terget.html(data.text);

												// continue
												if (data.continue == '1')
												{
													$me.removeData('processing');
													$me.find('.loading').remove();
													cb_batch();
												}
											}
											else
											{
												alert(data);
												$me.removeData('processing');
												$me.find('.loading').remove();
											}
										})
										.fail(function ()
										{
											alert('Failed, please try again.');
											$me.removeData('processing');
											$me.find('.loading').remove();
										});


							}// if
						}// cb_batch

						// perform first call 
						cb_batch();
					}
					break;
			}
			return false;
		});

		/**
		 * used to load time taking actions as seperate page request 
		 * loading external files like updates, news with curl for example 
		 * 
		 * USAGE: <span data-url="relative/path.html" data-toggle="cb_load_silent"> </span>
		 */
		$('[data-toggle="cb_load_silent"][data-url]').each(function ()
		{
			var $me = $(this);
			var url = $me.data('url');
			if (url.length > 0)
			{
				$.post(BASE_URL + url, {nounce: nounce})
						.done(function (data)
						{
							//console.log('cb_load_silent:done:' + url);
							console.log(data);

						})
						.fail(function ()
						{
							console.log('cb_load_silent:fail:' + url);
						})
						.always(function ()
						{
							// remove element from dom
							$me.remove();
						});
			}
		});

	},
	initReport: function ()
	{
		// reporting ad
		$(document).on('click', 'a.report', function ()
		{
			//console.log('initReport:click');
			var $me = $(this);
			var id = $me.attr('rel');
			var msg_confirm = $me.attr('msg-confirm');
			var msg_input = $me.attr('msg-input');
			var reason = '';
			if (id)
			{
				if (typeof msg_confirm == "undefined")
				{
					return false;
				}

				if (confirm(msg_confirm))
				{
					if (typeof msg_input != "undefined")
					{
						reason = prompt(msg_input, "");
					}
					$.post(BASE_URL + 'post/report/', {
						id: id,
						reason: reason
					}, function (data)
					{
						if (data != '')
						{
							alert(data);
						}
					});
				}
			}
			return false;
		});
	},
	initStats: function ()
	{
		/**
		 * Count ad view 
		 * @returns {undefined}
		 */
		var id = $('.js_stat').data('itemid') || 0;
		// wait after loading page in milliseconds 1s = 1000ms
		var time_wait = 1000;
		// count ad view 
		// or perform regular cron 
		if (id || $('body._cron').length)
		{
			$('body:last').animate({delay: 1}, time_wait, function ()
			{
				$.post(BASE_URL + 'post/cntItem/', {id: id, nounce: nounce}, function (data)
				{
					console.log('initStats:' + id + ':' + data);
				});
			});
		}
	},
	initTabs: function ($obj)
	{
		//console.log('initTabs');
		var tabsClick = function ()
		{
			//console.log('tabsClick');
			var $me = $(this);
			var $tabs = $me.parents('.tabs:first');
			var $container = $('body:first');
			if ($tabs.data('container'))
			{
				$container = $tabs.parents('.' + $tabs.data('container') + ':first');
			}
			var $other_tab_content = $container.find('.' + $me.data('hide'));
			// hide others
			//console.log('$other_tab_content.is(\'.display-none\')' + $other_tab_content.is('.display-none'));
			if ($other_tab_content.is('.display-none'))
			{
				$other_tab_content.hide().removeClass('display-none');
			}
			$other_tab_content.hide();
			$tabs.find('.active').removeClass('active');
			// show selected tab
			$container.find('.' + $me.data('show')).fadeIn('fast');
			$container.find('[keep_hidden]').hide();
			$me.addClass('active');
			return false;
		};
		// assign action to multilingual tabs	
		$obj.on('click', '.tabs a', tabsClick);
		var $tabs = $obj.find('.tabs');
		$tabs.find('a:first').click();
		//console.log('initTabs:END');
	},
	initSidebar: function ()
	{
		/**
		 * main menu opening arrows  *
		 * @returns {undefined}
		 */
		var $sidebar = $('.sidebar');
		if ($sidebar.length)
		{
			//var $caret = $('<span class="right"><i class="fa fa-caret-down"></i></span>');

			$sidebar.find('li').has('ul').find('a:first').addClass('has_submenu');
			$sidebar.on('click', '.has_submenu', function (e)
			{
				e.preventDefault();
				var $me = $(this);
				var $ul = $me.parents('li:first').find('ul');
				var $ul_all = $('ul ul', $sidebar);
				// mark current ul
				$ul_all.removeClass('current');
				$ul.addClass('current');
				// hide all other uls 
				$ul_all.not('.current').slideUp('fast');
				// display current li
				$ul.slideToggle();
				//$me.parents('li:first').find('ul').slideToggle();
			});
			$('.popup_sidebar').click(function ()
			{
				$sidebar.addClass('sidebar_visible');
				$('body').addClass('overflow-hidden');
			});
			$(document).on('click', '.sidebar_visible', function (e)
			{
				if (!$(e.target).parents('.sidebar_content:first').length)
				{
					$sidebar.removeClass('sidebar_visible');
					$('body').removeClass('overflow-hidden');
				}
			});
		}
	},
	initMiniTable: function ()
	{
		/* minitable for mobile view, show main column and expand on click */

		if ($('.tblmin').length)
		{
			// mark first cell as main if not set any 
			$('.tblmin:not(:has(.tblmin-main))').find('tr').find('th:first,td:first').addClass('tblmin-main');
			// append click handler to main cell
			$('table.tblmin tr').find('td.tblmin-main:first').prepend('<span class="button small outline tblmin-expand"></span>');
			// mark empty cells
			$('table.tblmin tr').find('td:not(.tblmin-main):empty').addClass('tblmin-empty');
			$(document).on('click', '.tblmin tr td.tblmin-main', function (e)
			{
				var $me = $(this);
				var $target = $(e.target);
				var $target_all = $target.parents().addBack();
				if (!$target_all.is('a'))
				{
					var $tr = $me.parents('tr:first');
					// hide all other expanded tr
					var $table = $me.parents('.tblmin:first');
					$table.find('tr').not($tr).removeClass('tblmin-show');
					$tr.toggleClass('tblmin-show');
					$table.find('tr').not('.tblmin-show').find('.tblmin-expand').removeClass('focus');
					$table.find('.tblmin-show .tblmin-expand').addClass('focus');
				}
			});
		}
	},
	pwa: {
		init: function ()
		{
			if (typeof pwa_sw !== 'undefined')
			{
				if (pwa_sw.url === 'disable')
				{
					// unregister 
					cb.pwa.unregister();
				}
				else
				{
					// register
					cb.pwa.resister();
				}
			}
			else
			{
				// unregister
			}
		},
		resister: function ()
		{
			if ('serviceWorker' in navigator)
			{
				window.addEventListener('load', function ()
				{
					navigator.serviceWorker.register(pwa_sw.url).then(function (registration)
					{
						console.log('pwa service worker ready');
						registration.update();
					}).catch(function (error)
					{
						console.log('Registration failed with ' + error);
					});
				});
			}
		},
		unregister: function ()
		{
			navigator.serviceWorker.getRegistrations().then(function (registrations)
			{
				for (let registration of registrations)
				{
					registration.unregister();
				}
			});
		}
	},
	initSearchFilter: function ()
	{

		// custom search filter form for simple search widget 
		//if we have modal search 
		if ($('.search_form_toggle').length)
		{
			cb.modal.init(function ()
			{
				$(document).on('click', '.search_form_toggle', function ()
				{
					//console.log('initSearchFilter:modal:open');
					// show hidden search form in modal 
					var $me = $(this);
					var is_literal = $me.is('[data-target-literal]');
					var $form = $me.parents('form:first');
					if ($me.data('target'))
					{
						$form = $($me.data('target')).first();
					}
					if ($form.length)
					{
						if (is_literal)
						{
							// create original form placeholder
							// find unique original name 
							var i = 0;
							var placeholder = '_initSearchFilter_placeholder_' + i;
							while ($('.' + placeholder).length > 0)
							{
								i++;
								placeholder = '_initSearchFilter_placeholder_' + i;
							}
							$form.after('<span class="' + placeholder + '"></span>');

							// use literal same form 
							$form_clone = $form;
						}
						else
						{
							// use clone 
							var $form_clone = $form.clone(true);
						}


						// show form 
						var revert_display_none = false;
						if ($form_clone.is('.display-none'))
						{
							$form_clone.removeClass('display-none');
							revert_display_none = true;
						}

						// prepare content
						$form_clone.addClass('expanded');

						// mark last paragraph as action buttons 
						$form_clone.find('p:last').addClass('action_buttons');

						cb.modal.open({
							$content: $form_clone,
							classClose: '.cancel',
							onClose: function ()
							{
								// if it is literal form then convert form to original  state
								if (is_literal)
								{
									// show form REVERT
									if (revert_display_none)
									{
										$form_clone.addClass('display-none');
									}
									// prepare content REVERT
									$form_clone.removeClass('expanded');

									// mark last paragraph as action buttons REVERT
									$form_clone.find('p.action_buttons').removeClass('action_buttons');

									// place after placeholder
									$('.' + placeholder).after($form_clone);
									$('.' + placeholder).remove();
								}
							}
						});
					}
				});
			});
		}


	},
	modal: {
		_modal: null,
		_modalCurrent: null,
		init: function (fnc)
		{
			if (typeof tingle === 'undefined')
			{
				// load tingle and call again 
				$.getScriptCached(URL_PUBLIC + 'public/js/tingle.min.js').done(function ()
				{
					cb.modal.init(fnc);
				});
				return false;
			}

			if (typeof cb.modal.init_done === 'undefined')
			{
				// It has not... perform the initialization
				cb.modal.init_done = true;
				//console.log('modal.init_done');

				$(window).on('hashchange', function (event)
				{
					//console.log('cb.modal.init:hashchange:' + window.location.hash)
					if (window.location.hash !== "#modal")
					{
						cb.modal.close();
					}
				});
			}

			if (typeof fnc !== 'undefined')
			{
				fnc();
			}
		},
		_onOpen: function ()
		{
			window.location.hash = "modal";
			cb.modal._modalCurrent = cb.modal._modal;
			// remove focus
			document.activeElement.blur();

		},
		_onClose: function ()
		{
			//window.location.hash = "";
			if (cb.modal._modalCurrent !== null)
			{
				cb.modal._modalCurrent.destroy();
			}
			cb.modal._modalCurrent = null;
			if (window.location.hash === '#modal')
			{
				window.history.back();
			}
		},
		open: function (options)
		{
			/* options={
			 * $content:$jquery,
			 * classClose:'.cancel',
			 * classAction:'.action_buttons',
			 * } */

			// check if tingle loaded 
			if (typeof tingle === 'undefined')
			{
				// load then open 
				cb.modal.init(cb.modal.open(options));
				return false;
			}

			// before opening close any exiting modal
			cb.modal.close();


			// append global on open 
			if (typeof options.onOpen !== 'undefined')
			{
				var fnc = options.onOpen;
				options.onOpen = function ()
				{
					fnc();
					cb.modal._onOpen();
				};
			}
			else
			{
				options.onOpen = cb.modal._onOpen;
			}

			// append global onclose
			if (typeof options.onClose !== 'undefined')
			{
				var fnc = options.onClose;
				options.onClose = function ()
				{
					fnc();
					cb.modal._onClose();
				};
			}
			else
			{
				options.onClose = cb.modal._onClose;
			}

			// check close class, and assign close action to it
			if (typeof options.classClose !== 'undefined')
			{
				options.$content.on('click', options.classClose, function ()
				{
					cb.modal.close();
					return false;
				});
			}


			// read content and check for action buttons
			var $actions_buttons = options.$content.find('.action_buttons');
			if ($actions_buttons.length)
			{
				options.footer = true;
				options.stickyFooter = false;
				$actions_buttons.hide();
			}

			// create modal 
			cb.modal._modal = new tingle.modal(options);

			// add content
			cb.modal._modal.setContent(options.$content[0]);

			// add buttons
			if ($actions_buttons.length)
			{
				cb.modal._modal.setFooterContent('');
				$actions_buttons.find('.button').each(function ()
				{
					var $me = $(this);
					var label = $me.html();
					var cssClass = $me.attr('class');
					cb.modal._modal.addFooterBtn(label, cssClass, function ()
					{
						$me[0].click();
					});
				});
			}

			// open modal 
			cb.modal._modal.open();

			return cb.modal._modal;
		},
		close: function ()
		{
			if (cb.modal._modalCurrent !== null)
			{
				cb.modal._modalCurrent.close();
			}
		},
		resize: function ()
		{
			if (cb.modal._modalCurrent !== null)
			{
				cb.modal._modalCurrent.checkOverflow();
			}
		},
		openImage: function ($obj)
		{
			if ($obj.is('[href]'))
			{
				// has linked content
				var src = $obj.attr('href');
				var img = $('<img>').attr('src', src);
				var $content = $('<div class="gallery_slider_modal_content"></div>');

				$content.append('<div class="gallery_slider_item"></div>');
				$content.find('.gallery_slider_item').append(img);

				cb.modal.open({
					$content: $content,
					cssClass: ['gallery_slider_modal']
				});
			}

		}
	},
	initSlider: function ()
	{
		// slick slider 
		if ($('.gallery_slider').length)
		{
			//console.log('cb.initSlider');
			var init_modal = false;
			var init_slider = false;

			// create click modal content before using slick slider
			// append content to gallery_slider item 
			$('.gallery_slider').each(function ()
			{
				var $gallery = $(this);
				/**
				 * single image: slider no, modal yes, modal-slider no;
				 * single video: slider no, modal no, modal-slider no
				 * multi: slider yes, modal yes, modal-slider yes
				 */
				var arr_img = $gallery.find('a[href]');
				var arr_video = $gallery.find('div.gallery_video');


				if (arr_img.length > 0)
				{
					// has image then define modal content 
					// create gallery big images 
					var $big_images = $gallery.find('a[href],div.gallery_video').map(function (index)
					{
						var $me = $(this);
						var replacement;
						if ($me.is('a[href]'))
						{
							// image
							replacement = $("<img>")
									.attr('data-src-afterload', $me.attr('href'))
									.attr('data-src-small', $me.find('img').attr('src'));
						}
						else
						{
							// video pass as is
							replacement = $me.clone(true);
						}
						replacement = $('<div class="gallery_slider_item"></div>').append(replacement);
						return replacement.get(0);
					});
					if ($big_images.length)
					{
						// init modal as well for showing big images 
						init_modal = true;

						// save generated content to related gallery
						var $content = $('<div class="gallery_slider_modal_content"></div>');
						$content.append($big_images);
						$gallery.data('gallery_slider_modal', $content);
					}
				}

				if ((arr_img.length + arr_video.length) > 1)
				{
					// slider yes
					// modal yes
					// modal-slider yes
					$gallery.addClass('slider-yes');
					if (typeof $content !== 'undefined' && $content.length > 0)
					{
						$content.addClass('modal-slider-yes');
					}
					init_slider = true;
				}
			});

			if (init_slider)
			{
				// load carousel javascript
				/*cb.initSlick(function ()
				 {
				 $('.gallery_slider.slider-yes').slick({
				 dots: true,
				 infinite: false,
				 speed: 300,
				 slidesToShow: 1,
				 centerMode: true,
				 variableWidth: true,
				 swipeToSlide: true,
				 responsive: [
				 {
				 breakpoint: 600,
				 settings: {
				 arrows: false
				 }
				 }
				 ]
				 });
				 });*/

				cb.sly.start({obj: $('.gallery_slider.slider-yes'), options: {frameAddClass: 'gallery_slider_frame'}});
			}


			// init modal after slick because slick should be instantly visible to users
			if (init_modal)
			{
				cb.modal.init(function ()
				{
					// on click convert links to gallery and show in modal 
					$(document).on('click', '.gallery_slider a', function ()
					{
						console.log('gallery_slider:click:modal.open');
						var $me = $(this);
						var href = $me.attr('href');
						var $gallery = $me.parents('.gallery_slider:first');
						var $gallery_content = $gallery.data('gallery_slider_modal');
						if (typeof $gallery_content != 'undefined' && $gallery_content)
						{
							// convert all data-src-small to normal 
							$gallery_content.find('[data-src-small]').each(function ()
							{
								var $me = $(this);
								var src_small = $me.attr('data-src-small');
								$me.removeAttr('data-src-small');

								// first set small image 
								$me.attr('src', src_small);
							});

							// clone it 
							var $gallery_clone = $gallery_content.clone();
							// open modal 

							// show modal 
							cb.modal.open({
								$content: $gallery_clone,
								cssClass: ['gallery_slider_modal'],
								onOpen: function ()
								{
									// load afterload images 
									cb.lazy.setAfterLoad($gallery_clone);

									// make modal gallery slider if set
									if ($gallery_clone.is('.modal-slider-yes'))
									{
										// convert content to slider 
										/*$gallery_clone.slick({
										 dots: true,
										 infinite: false,
										 slidesToShow: 1,
										 centerMode: false,
										 centerPadding: '0',
										 variableWidth: false,
										 focusOnSelect: false,
										 swipeToSlide: true,
										 responsive: [
										 {
										 breakpoint: 600,
										 settings: {
										 arrows: false
										 }
										 }
										 ]
										 });
										 
										 
										 
										 // calculate index of clicked item 
										 var gotoSlide = 0;
										 var gotoSlide_index = 0;
										 $gallery_content.find('img').each(function ()
										 {
										 if ($(this).attr('src') === href)
										 {
										 gotoSlide = gotoSlide_index;
										 }
										 gotoSlide_index++;
										 });
										 //console.log('gotoSlide:' + gotoSlide);
										 if (gotoSlide > 0)
										 {
										 $gallery_clone.slick('slickGoTo', gotoSlide);
										 }*/

										// calculate index of clicked item 
										var gotoSlide = 0;
										var gotoSlide_index = 0;
										$gallery_content.find('img').each(function ()
										{
											if ($(this).attr('src') === href || $(this).attr('data-src-afterload') === href)
											{
												gotoSlide = gotoSlide_index;
											}
											gotoSlide_index++;
										});

										// console.log('gotoSlide:' + gotoSlide);
										// load sly with centered item 
										cb.sly.start({obj: $gallery_clone, options: {slideTo: gotoSlide}});


										// get sly frame to reload and to center image after reload 
										cb.sly.reloadIfSly($gallery_clone);
										/*var $frame = cb.sly.getFrame($gallery_clone);
										 if($frame.length)
										 {
										 
										 }*/



									}
								}
							});

						}
						return false;
					});
				});
			}
		}
	},
	initCarousel: function ()
	{
		//console.log('initCarousel');
		// carousel 
		if ($('.list_style_carousel').length)
		{
			if (cb.isV2Enabled())
			{
				// new themes 
				/*cb.initSlick(function ()
				 {
				 //console.log('initCarousel:slick');
				 $('.list_style_carousel').slick({
				 dots: false,
				 infinite: false,
				 
				 variableWidth: true,
				 swipeToSlide: true,
				 responsive: [
				 {
				 breakpoint: 600,
				 settings: {
				 
				 }
				 }
				 ]
				 });
				 });*/
				cb.sly.start({obj: $('.list_style_carousel')});
			}
			else
			{
				// old themes
				// load carouFredSel javascript
				$.getScriptCached(URL_PUBLIC + 'public/js/jquery.carouFredSel-6.2.1-packed.js').done(function ()
				{
					$('.list_style_carousel').carouFredSel({
						scroll: 1,
						responsive: true,
						mousewheel: true,
						swipe: {
							onMouse: true,
							onTouch: true
						},
						items: {
							visible: {
								min: 2,
								max: 20
							}
						}
					});
				});
			}



		}
	},
	initSlick: function (fnc)
	{
		if (typeof cb.initSlick.slick === 'undefined')
		{
			// load slick slider and call again 
			$.getScriptCached(URL_PUBLIC + 'public/js/slick.min.js').done(function ()
			{
				cb.initSlick.slick = true;
				cb.initSlick(fnc);
			});
			return false;
		}

		if (typeof fnc !== 'undefined')
		{
			fnc();
		}
	},
	sly: {
		loaded: false,
		init: function (fnc)
		{
			if (!cb.sly.loaded)
			{
				// load slick slider and call again 
				$.getScriptCached(URL_PUBLIC + 'public/js/sly.min.js').done(function ()
				{
					cb.sly.loaded = true;
					// register resize event 
					$(window).on('resize', function ()
					{
						cb.throttle(function ()
						{
							console.log('sly.reload:resize');
							$('.sly_frame').sly('reload');
						}, 'resize_sly', 500);
					});
					cb.sly.init(fnc);
				});
				return false;
			}
			if (typeof fnc !== 'undefined')
			{
				fnc();
			}
		},
		start: function (params)
		{
			// init first 
			if (!cb.sly.loaded)
			{
				cb.sly.init(function ()
				{
					cb.sly.start(params);
				});
				return false;
			}

			if (typeof params !== 'undefined')
			{
				var $obj = params.obj;

				// fnc is jquery object, apply sly to it 
				$obj.each(function ()
				{
					var $me = $(this);

					// prevent double convertion 
					if ($me.parents('.sly_wrap').length > 0)
					{
						return false;
					}

					var $wrap = $('<div class="sly_wrap">'
							+ '<div class="sly_frame">'
							+ '</div>'
							+ '<button class="sly_prev" aria-label="Previous" type="button" aria-disabled="true">Previous</button>'
							+ '<button class="sly_next" aria-label="Next" type="button" aria-disabled="true">Next</button>'
							+ '<ul class="sly_pages"></ul>'
							+ '</div>');
					var $frame = $wrap.find('.sly_frame');

					$me.after($wrap);
					$frame.append($me);

					var options = {
						horizontal: 1,
						itemNav: 'centered',
						smart: 1,
						activateOn: 'click',
						mouseDragging: 1,
						touchDragging: 1,
						releaseSwing: 1,
						scrollBy: 1,
						speed: 300,
						elasticBounds: 1,

						// Buttons
						prevPage: $wrap.find('.sly_prev'),
						nextPage: $wrap.find('.sly_next'),
						pagesBar: $wrap.find('.sly_pages')
					};

					options = $.extend(options, params.options || {});

					if (typeof options.frameAddClass !== 'undefined')
					{
						$frame.addClass(options.frameAddClass);
					}

					// Call Sly on frame
					$frame.sly(options);
					if (typeof options.slideTo !== 'undefined')
					{
						console.log('slideTo:toCenter:' + options.slideTo);
						$frame.sly('toCenter', options.slideTo);
					}


					// reload with timeout to reposition images 

				});

			}
		},
		getFrame: function (obj)
		{
			return obj.parents('.sly_frame:first');
		},
		reloadIfSly: function (obj)
		{
			// resize parent sly frame
			cb.sly.reloadIfSlyFrame(cb.sly.getFrame(obj));

		},
		reloadIfSlyFrame: function ($frame)
		{
			// check if it is sly frame then reload it 
			if ($frame.length > 0 && $frame.is('.sly_frame') && cb.sly.loaded)
			{
				var src = $frame.find('img:first').attr('src');
				cb.throttle(function ()
				{
					//console.log('reloadIfSlyFrame:throttle:' + src);
					$frame.sly('reload');
				}, 'reloadIfSlyFrame_' + src, 200);
			}
		},
		reloadOnload: function ($img)
		{
			// reload now and after finished loading 
			cb.sly.reloadIfSly($img);

			$img.on("load", function ()
			{
				// do stuff
				cb.sly.reloadIfSly($img);
			}).each(function ()
			{
				if (this.complete)
				{
					$(this).load(); // For jQuery < 3.0 
					// $(this).trigger('load'); // For jQuery >= 3.0 
				}
			});

			/* it is definetly in sly so use tmp image to reload after image load completed * /
			 // trigger onload 
			 var tmpImg = new Image();
			 tmpImg.onload = function ()
			 {
			 // check if we need to reload sly
			 cb.sly.reloadIfSly($img);
			 };
			 tmpImg.src = $img.attr('src');*/
		}


	},

	initGallery: function ()
	{
		// OLD colorbox popup
		// init gallery 
		if ($('.gallery a').length)
		{
			setupColorbox(function ()
			{
				$('.gallery a').colorbox(/*{rel: '.gallery a'}*/);
				$('.gallery a.iframe,.gallery a.vimeo,.gallery a.youtube').colorbox({
					iframe: true,
					width: '800px',
					height: '600px'/*,
					 rel: '.gallery a'*/
				});
			});
		}
	},
	initQRcode: function ()
	{

		// init gallery 
		if ($('a.qr_code').length)
		{
			if (cb.isV2Enabled())
			{
				// init modal here for being ready when clicked
				cb.modal.init(function ()
				{
					// new modal popup 
					$(document).on('click', 'a.qr_code', function ()
					{
						var $me = $(this);
						cb.modal.openImage($me);
						return false;
					})
				});
			}
			else
			{
				// OLD colorbox popup
				setupColorbox(function ()
				{
					$('a.qr_code').colorbox({photo: true});
				});
			}
		}
	},
	initBack: function ()
	{
		var isSame = (document.referrer.indexOf(window.location.host) !== -1);
		var $links = $('.js_back_if_same');
		if (isSame && $links.length > 0)
		{
			$links.each(function ()
			{
				var $me = $(this);
				$me.attr('href', '#' + $me.attr('href'));
			});
			$links.on('click', function ()
			{
				history.go(-1);
				return false;
			});
		}
	},
	phone: {
		init: function ()
		{

			$('[data-phonecall]').each(function ()
			{
				var $me = $(this);
				var tel = $($me.data('phonecall')).text();

				// get valid phone number 
				tel = cb.phone.num(tel, $me.data('min'), $me.data('max'));

				if (!tel.length)
				{
					return false;
				}

				// set tel and show 
				$me.attr('href', 'tel:' + tel);
				$me.data('tel', tel);
				$me.removeClass('display-none');
			});

			// track event
			$('body').on('click', '[data-phonecall]', function (e)
			{
				console.log('phcall:start:' + typeof ga);
				var href = window.location.href;
				var tel = $(this).data('tel');
				var obj = {'hitType': 'event', 'eventCategory': 'phcall', 'eventAction': tel, 'eventLabel': href};
				console.log(obj);
				if (e.isDefaultPrevented() || typeof ga !== "function")
				{
					return;
				}
				ga('send', obj);
				console.log('phcall');
			});

		},
		num: function (tel, min, max, regex)
		{
			//console.log('trim_tel('+tel+')');
			// check ıf it is valid phone number 5 digit 
			tel = tel + '';
			if ((tel.match(/\d/g) + '').length < min)
			{
				return '';
			}
			if (typeof min == 'undefined')
			{
				min = 5;
			}
			if (typeof max == 'undefined')
			{
				max = 12;
			}

			// cleanup tel
			tel = tel.replace(/,|\||\/|\.|\:/gi, ";");
			tel = tel.replace(/ \+/gi, ";+");
			//console.log('cleanup:'+tel);
			tel = cb.trim(tel, " ;");
			var arr_tel = tel.split(";");
			// tm phone pattern 
			// var phoneRe = /^[\+]?(993)?(\d{2,3})?\d{5,6}$/im;			
			for (x in arr_tel)
			{
				// strip chars
				tel = arr_tel[x].replace(/[^\d+]/g, '');
				var tel_ok = tel.length >= min;
				tel_ok = tel_ok && (tel.length <= max);
				if (typeof regex != 'undefined')
				{
					tel_ok = tel_ok && (regex.test(tel));
				}
				if (!tel_ok)
				{
					tel = '';
					continue;
				}
				break;
			}
			return tel + '';
		}
	},
	trim: function (s, c)
	{
		// remove csutom character from edges
		//	c: custom characters as string
		if (c === "]")
		{
			c = "\\]";
		}
		if (c === "\\")
		{
			c = "\\\\";
		}
		return s.replace(new RegExp(
				"^[" + c + "]+|[" + c + "]+$", "g"
				), "");
	},
	lazy: {
		init: function ()
		{
			console.log('loadLazyImg:' + $('.lazy[data-src]').length);
			// init lazy first 
			//$('.lazy[data-src]').Lazy({delay: 0});
			// seperate static content
			$('.lazy[data-src]').each(function ()
			{
				var $me = $(this);
				var src = $me.attr('data-src');
				$me.removeAttr('data-src');
				// check if src static
				if (src.search('.jpg') === -1)
				{
					// process in batches			
					$me.attr('data-src-later', src);
				}
				else
				{
					$me.attr('data-src-now', src);
				}
			});

			var loadLater = function (key, limit)
			{
				$('.lazy[' + key + ']').slice(0, limit).each(function ()
				{
					var $me = $(this);
					var src = $me.attr(key);
					$me.removeAttr(key);
					if ($me.is('img'))
					{
						$me.attr('src', src);
						cb.sly.reloadOnload($me);
						//$me.attr('loading', 'lazy');
					}
					else
					{
						// it is background image 
						$me.css({'background-image': 'url(' + src + ')'});
					}


				});

				// if left continue to load in next batch 
				if ($('.lazy[' + key + ']').length > 0)
				{
					setTimeout(function ()
					{
						loadLater(key, limit);
					}, 500);
				}
			};

			console.log('loadLazyImg:static:' + $('.lazy[data-src-now]').length + ',dyn:' + $('.lazy[data-src-later]').length);

			// load all static first 
			loadLater('data-src-now', 100);

			// load all dynamic by 1  
			loadLater('data-src-later', 1);

			console.log('loadLazyImg:done');

		},
		setAfterLoad: function ($obj)
		{
			/* load big image in temp object and after finished loading show it as image */
			$obj.find('img[data-src-afterload]').each(function ()
			{
				var $me = $(this);
				var src = $me.attr('data-src-afterload');
				$me.removeAttr('data-src-afterload');
				//console.log('setAfterLoad:' + src);

				// trigger onload to tmp
				var tmpImg = new Image();
				tmpImg.onload = function ()
				{
					/* tmp image loaded then set src to main image */
					$me.attr('src', src);
					cb.sly.reloadOnload($me);
				};
				tmpImg.src = src;
			});
		}

	}



};

function initContactForm()
{
	// hide contact form 
	// if no error in form then hide 
	if ($('#contact_form .msg-error-line').length < 1)
	{
		$('#contact_form').hide();
	}
	else
	{
		$('#contact_form').show();
	}
}


function columnizeCats(obj)
{
	// init columnizer with 200 px 
	var $obj = $(obj);
	if ($obj.length)
	{
		// load columnizer javascript
		$.getScriptCached(URL_PUBLIC + 'public/js/jquery.columnizer.js').done(function ()
		{
			$obj.children().css({float: 'none', width: 'auto', margin: '10px 0'}).addClass('dontsplit');
			$obj.columnize({width: 180, lastNeverTallest: true});
		});
	}
}


/**
 * Used to call inside resize event for not running too much 
 * @type Function
 * /
 var waitForFinalEvent = (function () {
 var timers = {};
 return function (callback, ms, uniqueId) {
 if (!uniqueId) {
 uniqueId = "Don't call this twice without a uniqueId";
 }
 if (timers[uniqueId]) {
 clearTimeout(timers[uniqueId]);
 }
 timers[uniqueId] = setTimeout(callback, ms);
 };
 })();
 */




/*
 * jQuery Dropdown: A simple dropdown plugin
 *
 * Contribute: https://github.com/claviska/jquery-dropdown
 *
 * @license: MIT license: http://opensource.org/licenses/MIT
 *
 */
if (jQuery)
	(function ($)
	{

		$.extend($.fn, {
			jqDropdown: function (method, data)
			{

				switch (method)
				{
					case 'show':
						show(null, $(this));
						return $(this);
					case 'hide':
						hide();
						return $(this);
					case 'attach':
						return $(this).attr('data-jq-dropdown', data);
					case 'detach':
						hide();
						return $(this).removeAttr('data-jq-dropdown');
					case 'disable':
						return $(this).addClass('jq-dropdown-disabled');
					case 'enable':
						hide();
						return $(this).removeClass('jq-dropdown-disabled');
				}

			}
		});
		function show(event, object)
		{
			var trigger = event ? $(this) : object,
					jqDropdown = $(trigger.attr('data-jq-dropdown')),
					isOpen = trigger.hasClass('jq-dropdown-open');
			// In some cases we don't want to show it
			if (event)
			{
				if ($(event.target).hasClass('jq-dropdown-ignore'))
					return;
				event.preventDefault();
				event.stopPropagation();
			}
			else
			{
				if (trigger !== object.target && $(object.target).hasClass('jq-dropdown-ignore'))
					return;
			}
			hide();
			if (isOpen || trigger.hasClass('jq-dropdown-disabled'))
				return;
			// check add submenu classes
			jqDropdown.find('.jq-dropdown-menu li').has('ul').find('a:first').not('.has_submenu').addClass('has_submenu');
			jqDropdown.find('.jq-dropdown-menu li').has('.has_submenu').not(jqDropdown.find('.jq-dropdown-menu li').has('ul')).removeClass('has_submenu');
			// Show it
			trigger.addClass('jq-dropdown-open');
			jqDropdown
					.data('jq-dropdown-trigger', trigger)
					.show();
			// Position it
			position();
			// Trigger the show callback
			jqDropdown
					.trigger('show', {
						jqDropdown: jqDropdown,
						trigger: trigger
					});
		}

		function hide(event)
		{
			// In some cases we don't hide them
			var targetGroup = event ? $(event.target).parents().addBack() : null;
			// Are we clicking anywhere in a jq-dropdown?
			if (targetGroup && targetGroup.is('.jq-dropdown'))
			{
				// Is it a jq-dropdown menu?
				if (targetGroup.is('.jq-dropdown-menu'))
				{
					// Did we click on an option? If so close it.
					if (!targetGroup.is('A'))
					{
						return;
					}
					else
					{
						// it is A
						// check for submenu 
						var $me = $(event.target);
						if (!$me.is('a'))
						{
							$me = $me.parents('a:first');
						}
						if ($me.is('.has_submenu'))
						{
							var $li = $me.parents('li:first');
							var $ul = $li.find('ul:first');
							var $jqdropdown = $me.parents('.jq-dropdown-menu:first');
							if ($ul.length)
							{
								event.preventDefault();
								// open child
								// close all other child and open this one 
								$jqdropdown.find('ul.current').removeClass('current');
								$ul.addClass('current');
								$ul.parentsUntil('ul.jq-dropdown-menu', 'ul').addClass('current');
								$jqdropdown.find('ul').not('.current').slideUp('fast');
								$ul.slideToggle(position);
								// prevent closing dropdown
								return false;
							}
						}
					}
				}
				else
				{
					// Nope, it's a panel. Leave it open.
					return;
				}
			}

			// Hide any jq-dropdown that may be showing
			$(document).find('.jq-dropdown:visible').each(function ()
			{
				var jqDropdown = $(this);
				jqDropdown
						.hide()
						.removeData('jq-dropdown-trigger')
						.trigger('hide', {jqDropdown: jqDropdown});
			});
			// Remove all jq-dropdown-open classes
			$(document).find('.jq-dropdown-open').removeClass('jq-dropdown-open');
		}

		function position()
		{
			var jqDropdown = $('.jq-dropdown:visible').eq(0),
					trigger = jqDropdown.data('jq-dropdown-trigger'),
					hOffset = trigger ? parseInt(trigger.attr('data-horizontal-offset') || 0, 10) : null,
					vOffset = trigger ? parseInt(trigger.attr('data-vertical-offset') || 0, 10) : null;
			if (jqDropdown.length === 0 || !trigger)
			{
				return;
			}


			// Position the jq-dropdown relative-to-parent...
			if (jqDropdown.hasClass('jq-dropdown-relative')/* || true*/)
			{

				var left = trigger.position().left + parseInt(trigger.css('margin-left'), 10) + hOffset;
				var left_right = trigger.position().left - (jqDropdown.outerWidth(true) - trigger.outerWidth(true)) - parseInt(trigger.css('margin-right'), 10) + hOffset;
				if (jqDropdown.hasClass('jq-dropdown-anchor-right'))
				{
					left = left_right;
				}
				var top = trigger.position().top + trigger.outerHeight(true) - parseInt(trigger.css('margin-top'), 10) + vOffset;

				jqDropdown.css({
					left: left,
					top: top
				});
			}
			else
			{
				// ...or relative to document
				var left = trigger.offset().left + hOffset;
				var left_right = trigger.offset().left - (jqDropdown.outerWidth() - trigger.outerWidth()) + hOffset;
				if (jqDropdown.hasClass('jq-dropdown-anchor-right'))
				{
					left = left_right;
				}
				var top = trigger.offset().top + trigger.outerHeight() + vOffset;
				jqDropdown.css({
					left: left,
					top: top
				});
			}

			// check if height fits 
			var $content = jqDropdown.find('.jq-dropdown-menu,.jq-dropdown-panel').eq(0);
			jqDropdown.removeClass('jq-dropdown-scroll');
			$content.css({'max-height': ''});

			var contentHeight = $content.outerHeight();
			var vh = $(window).height();
			var jo = jqDropdown.offset();
			var wstop = $(window).scrollTop();
			var th = trigger.outerHeight()
			var edgeoffset = 70;
			var available_space_after = Math.round(vh - (jo.top - wstop) - edgeoffset);
			var available_space_before = Math.round(jo.top - wstop - th - edgeoffset);
			var available_space_max = Math.max(available_space_after, available_space_before);

			if (contentHeight > available_space_max)
			{
				// content is not fully visible resize to fit and update height variable
				jqDropdown.addClass('jq-dropdown-scroll');
				$content.css({'max-height': available_space_max});
				contentHeight = $content.outerHeight();
				//console.log('jqDropdown.position.max-height:' + available_space_max);
			}

			// assume it is down
			if (contentHeight > available_space_after)
			{
				// didnt fit down, move up and mark as up 
				jqDropdown.css({
					top: (top - contentHeight - th)
				});
				//jqDropdown.data('isDropUp', 1);
				console.log('jqDropdown.position.MOVEUP:contentHeight:' + contentHeight);
			}


			// if it is not visible switch to upperside 
			/*if (!cb.isInViewport(jqDropdown, true))
			 {
			 // scroll to control
			 $('html,body').animate({scrollTop: trigger.offset().top}, 'slow');
			 }*/

			// check if right visible 
			if (!cb.isInViewportRight(jqDropdown))
			{
				jqDropdown.css({
					left: left_right
				});
			}
		}

		$(document).on('click.jq-dropdown', '[data-jq-dropdown]', show);
		$(document).on('click.jq-dropdown', hide);
		$(window).on('resize', position);
	})(jQuery);

