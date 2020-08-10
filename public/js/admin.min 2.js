$(function()
{cb.lazy.init();if($('.layout_backend').length)
{cb.initTabs($('body'));cb.initSidebar()}
chainSelect.autoinit();cb.select.init();initDropzone();initContactForm();cb.init()});function checkCookie()
{if(navigator.cookieEnabled)
{return!0}
document.cookie="cookietest=1";var ret=(document.cookie.indexOf("cookietest=")!=-1);document.cookie="cookietest=1; expires=Thu, 01-Jan-1970 00:00:01 GMT";return ret}
jQuery.fn.extend({insertAtCaret:function(myValue)
{return this.each(function(i)
{if(document.selection)
{this.focus();sel=document.selection.createRange();sel.text=myValue;this.focus()}
else if(this.selectionStart||this.selectionStart=='0')
{var startPos=this.selectionStart;var endPos=this.selectionEnd;var scrollTop=this.scrollTop;this.value=this.value.substring(0,startPos)+myValue+this.value.substring(endPos,this.value.length);this.focus();this.selectionStart=startPos+myValue.length;this.selectionEnd=startPos+myValue.length;this.scrollTop=scrollTop}
else{this.value+=myValue;this.focus()}})}});function insertVar()
{var $me=$(this);var myvar=$me.data('id');var my_target=$me.data('target');var $editpane=$('.'+my_target);$editpane.insertAtCaret(myvar);return!1}
function scrollTop(ancor)
{if($(ancor).length)
{$('html,body').animate({scrollTop:$(ancor).offset().top},'slow')}}
jQuery.getScriptCached=function(url,options)
{options=$.extend(options||{},{dataType:"script",cache:!0,url:url});if(typeof jQuery.getScriptCached.loaded[url]==='undefined')
{jQuery.getScriptCached.loaded[url]=jQuery.ajax(options).done(function()
{}).fail(function(jqXHR,textStatus,errorThrown)
{console.log('getScriptCached.fail:'+textStatus+':'+url)})}
return jQuery.getScriptCached.loaded[url]};jQuery.getScriptCached.loaded={};function setupColorbox(fnc)
{if(typeof $.colorbox==='undefined')
{$.getScriptCached(URL_PUBLIC+'public/js/jquery.colorbox-min.js').done(function()
{setupColorbox(fnc)});return!1}
if(typeof setupColorbox.init==='undefined')
{setupColorbox.init=!0;console.log('setupColorbox.init');$.extend($.colorbox.settings,{maxWidth:'100%',maxHeight:'100%',initialWidth:'60px',initialHeight:'100px',current:"{current} / {total}",previous:"◀",next:"▶",close:"&times;",fixed:!0,imgError:function()
{var url=$(this).attr('href');return'This <a href="'+url+'" target="_blank"><b>image</b></a> failed to load.'}});$(document).bind('cbox_open',function()
{$('body').addClass('cboxModal')});$(document).bind('cbox_cleanup',function()
{$('body').removeClass('cboxModal')})}
if(typeof fnc!=='undefined')
{fnc()}}
function initDropzone()
{if($('input[type="file"]').length&&$('input#image_token').length&&typeof dropzone_settings!=='undefined')
{var _ds=dropzone_settings;var image_token=$('input#image_token').val();var $form=$('input[type="file"]:first').parents('form:first');var $mydropzone=$('.dropzone',$form);var $submit=$('input[type="submit"][name="submit"]:first',$form);var url=$form.prop('action');var mockFiles=_ds.mockFiles;delete _ds.mockFiles;if(mockFiles.length)
{var $mockFileFallbackWrap=$('<span class="dropzone-previews"></span>');var $thumb_grid=$('.dropzone .fallback .thumb_grid');for(x in mockFiles)
{var mockFile=mockFiles[x];var $mockFileFallback=$('<div class="dz-preview dz-complete dz-image-preview"><div class="dz-image"><img data-dz-thumbnail="" alt="a.jpg" src="a.jpg"></div><a class="dz-remove" href="javascript:undefined;" data-dz-remove="">×</a></div>');$('img',$mockFileFallback).attr('alt',mockFile.name);$('img',$mockFileFallback).attr('src',mockFile.imageUrl);$('a.dz-remove',$mockFileFallback).data('id',mockFile.id);$mockFileFallbackWrap.append($mockFileFallback)}
$thumb_grid.after($mockFileFallbackWrap);$thumb_grid.remove();$mockFileFallbackWrap.on('click','a.dz-remove',function(e)
{e.preventDefault();var $me=$(this);var id=$me.data('id');var $img=$me.parents('.dz-preview:first');console.log('a.dz-remove:click:'+id);$.post(url,{id:id,action:'img_remove'},function(data)
{if(data=='ok')
{console.log('a.dz-remove:click:removed:'+id);$img.remove();var $field=$($('.file_upload_template',$form).html());$field.attr('name',$field.attr('name')+id);$('.file_upload_fields',$form).append($field)}})})}
$.getScriptCached(URL_PUBLIC+'public/js/dropzone.min.js').done(function()
{console.log('initDropzone:JS loaded');Dropzone.autoDiscover=!1;var submit_val=$submit.val();var submit_form;var submit_form_after_upload=!1;var preventFormSubmit=function(dz)
{if(dz.getUploadingFiles().length===0&&dz.getQueuedFiles().length===0)
{setFormSubmit(!0)}};var setFormSubmit=function(val)
{if(submit_form!==val)
{submit_form=val;if(val===!1)
{$submit.val(submit_val+' ...')}
else{$submit.val(submit_val);if(submit_form_after_upload)
{$submit.click()}}}};var logCount=function(dz)
{console.log('files:'+dz.files.length);console.log('getAcceptedFiles:'+dz.getAcceptedFiles().length);console.log('getRejectedFiles:'+dz.getRejectedFiles().length);console.log('getQueuedFiles:'+dz.getQueuedFiles().length);console.log('getUploadingFiles:'+dz.getUploadingFiles().length)};setFormSubmit(!0);$form.submit(function(e)
{submit_form_after_upload=!0;return submit_form});if(typeof _ds.addIcon==="undefined")
{_ds.addIcon='+'}
$mydropzone.prepend('<span class="dropzone-enabled"><span class="dropzone-previews"></span><span class="dz-message"><span class="dz-message-upload">'+_ds.addIcon+'</span></span></span>');_ds.url=url;_ds.addRemoveLinks=!0;_ds.clickable='.dz-message';_ds.previewsContainer='.dropzone-previews';_ds.dictCancelUpload='×';_ds.dictRemoveFile='×';_ds.timeout=180000;_ds.fallback=function()
{$('.dropzone-enabled').remove()};_ds.init=function()
{var dz=this;this.on("complete",function(file)
{preventFormSubmit(this)});this.on("success",function(file,name_tmp)
{if(typeof name_tmp!=='undefined'&&name_tmp.length<100)
{file.name_tmp=name_tmp}
preventFormSubmit(this)});this.on("error",function(file,msg)
{this.removeFile(file);preventFormSubmit(this)});this.on("canceled",function(file)
{this.removeFile(file);preventFormSubmit(this);submit_form_after_upload=!1});this.on("addedfile",function(file)
{setFormSubmit(!1)});this.on("sending",function(file,xhr,formData)
{formData.append('image_token',image_token);formData.append('action','img')});this.on("removedfile",function(file)
{var name_tmp=(typeof file.name_tmp!=='undefined')?file.name_tmp:file.name;var id=(typeof file.id!=='undefined')?file.id:0;$.ajax({type:'POST',url:url,data:{action:"img_remove",image_token:image_token,file:name_tmp,id:id},dataType:'html'});preventFormSubmit(this);submit_form_after_upload=!1});for(x in mockFiles)
{var mockFile=mockFiles[x];mockFile.accepted=!0;this.files.push(mockFile);this.emit("addedfile",mockFile);this.emit("thumbnail",mockFile,mockFile.imageUrl);this.emit("complete",mockFile)}
if(this.options.maxFiles<=this.getAcceptedFiles().length)
{$mydropzone.addClass('dz-max-files-reached')}};$mydropzone.dropzone(_ds);console.log('initDropzone:set')})}}
var chainSelect={version:'1.0.1',autoinit:function()
{if(typeof chain_location_id!='undefined')
{chainSelect.init(chain_location_id)}
if(typeof chain_category_id!='undefined')
{chainSelect.init(chain_category_id)}},init:function(ids)
{if(typeof ids!='undefined')
{ids_selects='';for(x in ids.arr)
{ids_select='';for(y in ids.arr[x])
{ids_select+='<option value="'+y.replace('id_','')+'">'+ids.arr[x][y]+'</option>'}
ids_selects+='<select name="'+ids.name+'" id="'+ids.name+'" class="'+x+'"><option value="'+x.replace('parent_','')+'">'+ids.root_title+'</option>'+ids_select+'</select> '}
var $chain_select=$('<span class="chain_select">'+ids_selects+'</span>');$('#'+ids.name).after($chain_select).remove();var $selects=$('select[name="'+ids.name+'"]',$chain_select);$chain_select.on('change','select[name="'+ids.name+'"]',function()
{var $me=$(this);var id=$me.val();chainSelect.display($selects,id)});chainSelect.display($selects,ids.selected_id)}},display:function($selects,id)
{$selects.prop('disabled',!0).hide();var $child=$selects.filter('.parent_'+id);if($child.length)
{var child_val=$child.val();$child.prop('disabled',!1);if(child_val>0&&child_val!=id)
{return chainSelect.display($selects,child_val)}
else{$child.show()}}
chainSelect.displayLoop($selects,id)},displayLoop:function($selects,id)
{var $sel=$('option[value="'+id+'"]:first',$selects.not('.parent_'+id)).parent('select:first');var parent_id;if($sel.length)
{$sel.prop('disabled',!1).show().val(id)
parent_id=$sel.prop('class')}
if(typeof parent_id!='undefined')
{parent_id=parent_id.replace('parent_','');if(parent_id>0)
{$selects.filter('.parent_'+parent_id).after($selects.filter('.parent_'+id));chainSelect.displayLoop($selects,parent_id)}}}};var cb={version:'1',loaded:[],init:function()
{cb.initSearchFilter();cb.initToggle();cb.initReport();cb.initStats();cb.initMiniTable();cb.initSlider();cb.initCarousel();cb.initGallery();cb.initQRcode();cb.initSortableTable();cb.phone.init();cb.initBack();cb.pwa.init()},throttle:function(fnc,name,wait_time)
{if(typeof wait_time==='undefined')
{wait_time=500}
if(typeof wait_time==='undefined')
{name='throttle'}
if(typeof window.arr_throttle==='undefined')
{window.arr_throttle=[]}
clearTimeout(window.arr_throttle[name]);window.arr_throttle[name]=setTimeout(fnc,wait_time)},isV2Enabled:function()
{return($('body.e_jqd').length>0)},select:{initClickHandlerDone:!1,ind:0,init:function()
{console.log('cb.select.init');cb.select.convert($('select,input[data-selectalt]'))},convert:function($sel)
{if(!cb.isV2Enabled())
{return!1}
console.log('cb.select.convert');if($sel.length)
{cb.select.initClickHandler();$sel.each(function(i)
{var $me=$(this);var id=cb.select.nextAvailableId();var $html=$('<div class="select_alt input">'+'<div class="select_alt_text" data-jq-dropdown="#'+id+'"></div>'+'<input type="hidden" class="select_alt_val" name="'+$me.attr('name')+'" id="'+$me.attr('id')+'" />'+'<div id="'+id+'" class="jq-dropdown jq-dropdown-relative"><ul class="jq-dropdown-menu"></ul></div>'+'</div>');var $dropdown=$html.find('.jq-dropdown');if($me.is('input'))
{$html.find('input').remove();var $me_clone=$me.clone(!0);$me_clone.addClass('select_alt_val').attr('type','hidden');$html.append($me_clone);var select_alt_text=$me.data('rootname')||$me.data('currentname')||' ';$html.find('.select_alt_text').text(select_alt_text);var url=$me.data('src');cb.loadData(url,function(data)
{var myData=data[$me.data('key')];var opt=cb.select.recursiveList(myData,0);if(opt)
{if($me.data('rootname').length)
{var root_disabled='';if($me.attr('required'))
{root_disabled=' class="is-disabled"'}
opt='<li'+root_disabled+'><a data-v="">'+$me.data('rootname')+'</a></li>'+opt}
$dropdown.find('.jq-dropdown-menu').append($(opt));var val=$me.val();var $val_a=$dropdown.find('a[data-v="'+$me.val()+'"]');if(val&&$val_a.length)
{select_alt_text=$val_a.text()}
$html.find('.select_alt_text').text(select_alt_text);if($me.data('allallow'))
{$dropdown.find('ul').each(function()
{var $ul=$(this);var $parent=$ul.parents('li:first').find('a:first');var parent_text='';if($parent.length)
{parent_text=$parent.text();if($me.data('allpattern'))
{parent_text=$me.data('allpattern').replace("{name}",parent_text)}
else{parent_text='<b>'+parent_text+'</b>'}
$ul.prepend('<li><a data-v="'+$parent.data('v')+'">'+parent_text+'</a></li>')}})}
if($me.attr('data-disable'))
{var disabled_ids=$me.attr('data-disable');disabled_ids=disabled_ids.split(',');var $a_disabled=$dropdown.find('[data-v="'+disabled_ids.join('"],[data-v="')+'"],'+'[data-n="'+disabled_ids.join('"],[data-v="')+'"]');$a_disabled.each(function()
{var $me=$(this);var $li=$me.parents('li:first');$li.addClass('is-disabled')})}
$me.after($html);$me.remove();console.log('select.convert:dynamic:completed')}})}
else{$html.find('input').attr('name',$me.attr('name')).val($me.val());$html.find('.select_alt_text').text($('option:selected',$me).text());var opt='';$me.find('option').map(function(index,elem)
{opt+='<li><a data-v="'+$(elem).val()+'">'+$(elem).text()+'</a></li>'});$dropdown.find('.jq-dropdown-menu').append($(opt));$me.after($html);$me.remove();console.log('select.convert:static:completed')}})}},initClickHandler:function()
{if(cb.select.initClickHandlerDone)
{return}
cb.select.initClickHandlerDone=!0;$(document).on('show','.jq-dropdown',function(event,dropdownData)
{cb.select.showSelected(dropdownData.jqDropdown,dropdownData.trigger)}).on('hide','.jq-dropdown',function(event,dropdownData)
{cb.select.blur()});$(document).on('click','.select_alt .jq-dropdown a',function(e)
{var $me=$(this);if($me.not('.has_submenu').length)
{var $jqdropdown=$me.parents('.jq-dropdown:first');var $select_alt_text=$jqdropdown.data('jq-dropdown-trigger');var $select_alt=$select_alt_text.parents('.select_alt:first');if($select_alt.length)
{$select_alt.find('input.select_alt_val').val($me.data('v')).change();$select_alt_text.text($me.text());if($select_alt.find('input.select_alt_val').is('[required]'))
{if(!$me.data('v'))
{$select_alt.addClass('invalid')}
else{$select_alt.removeClass('invalid')}}
$jqdropdown.find('.active').removeClass('active');$me.addClass('active')}}});$(document).on('click active','label',function(e)
{console.log('on(click active label)');var $me=$(this);var label_for=$me.attr('for');if(typeof label_for!=='undefined')
{var $input=$('input[type="hidden"][name="'+label_for+'"]');if($input.length)
{var $select_alt_text=$input.parents('.select_alt:first').find('.select_alt_text');$select_alt_text[0].click();console.log('$select_alt_text');console.log($select_alt_text)}}})},showSelected:function($jqDropdown,$trigger)
{var $select_alt=$trigger.parents('.select_alt:first');cb.select.blur();$select_alt.addClass('focus');var $input=$select_alt.find('input.select_alt_val');var val=$input.val();if(val)
{var $active=$jqDropdown.find('a[data-v="'+val+'"]:last');if($active.length)
{$jqDropdown.find('ul.current').removeClass('current');$jqDropdown.find('a.active').removeClass('active');$active.addClass('active');$active.parentsUntil('ul.jq-dropdown-menu','ul').addClass('current');$jqDropdown.find('ul').not('.current,.jq-dropdown-menu').slideUp(0);$jqDropdown.find('ul.current').slideDown(0,function()
{$(window).trigger('resize')})}}},blur:function()
{$('.select_alt.focus').removeClass('focus')},nextAvailableId:function()
{while($('#jq-dropdown-'+cb.select.ind).length)
{cb.select.ind++}
var id='jq-dropdown-'+cb.select.ind;cb.select.ind++;return id},recursiveList:function(data,parent_id)
{var r='',child='',k='p'+parent_id,title='';if(typeof data[k]!=='undefined')
{for(y in data[k])
{if(data[k][y])
{title=data[k][y];r+='<li><a data-v="'+y.replace('i','')+'">';child=cb.select.recursiveList(data,y.replace('i',''));if(child)
{child='<ul>'+child+'</ul>'}
r+=title+'</a>'+child+'</li>'}}}
return r}},loadData:function(src,fncDone,fncFail)
{if(cb.loaded[src])
{if(cb.loaded[src].data)
{fncDone(cb.loaded[src].data)}
else{if(typeof fncDone==='function')
{cb.loaded[src].fncDone.push(fncDone)}
if(typeof fncFail==='function')
{cb.loaded[src].fncFail.push(fncFail)}}}
else{cb.loaded[src]={fncDone:[],fncFail:[]};if(typeof fncDone==='function')
{cb.loaded[src].fncDone.push(fncDone)}
if(typeof fncFail==='function')
{cb.loaded[src].fncFail.push(fncFail)}
$.ajax({dataType:"json",cache:!0,url:src}).done(function(data)
{console.log('load.done:'+src);cb.loaded[src].data=data;for(x in cb.loaded[src].fncDone)
{cb.loaded[src].fncDone[x](data)}}).fail(function()
{console.log('load.fail:'+src);for(x in cb.loaded[src].fncFail)
{cb.loaded[src].fncFail[x]()}}).always(function()
{cb.loaded[src].fncFail=[];cb.loaded[src].fncDone=[]})}},cf:{init:function(options)
{console.log('cf.init');cb.loadData(options.datasrc,function(data)
{options.data=data;var parent='';if(typeof options.parent!=='undefined')
{parent=options.parent+' '}
$(document).on('change',parent+options.loc+','+parent+options.cat,function()
{console.log('loc or cat changed');var $me=$(this);if(typeof options.parent!=='undefined')
{options.$parent=$me.parents(options.parent).first()}
cb.cf.render(options)});cb.cf.render(options)})},render:function(options)
{console.log('cf.render');var $parent=$(document);if(typeof options.$parent!=='undefined')
{$parent=options.$parent}
else if(typeof options.parent!=='undefined')
{$parent=$(options.parent)}
var $loc=$parent.find(options.loc);var $cat=$parent.find(options.cat);var $target=$parent.find(options.target);var loc_id=$loc.val()*1;var cat_id=$cat.val()*1;var form_type=options.form_type||'';if(typeof options.existing_values==='undefined')
{options.existing_values={}}
$target.find('input').each(function()
{var $me=$(this);var val=$me.val();var name=$me.attr('name');var type=$me.attr('type');switch(type)
{case 'radio':case 'checkbox':val=$target.find('input[name="'+name+'"]:checked').val();console.log('input[name="'+name+'"]:checked -> '+val);if(typeof val==='undefined')
{val=''}
break}
if(val.length||typeof options.existing_values[name]!=='undefined')
{options.existing_values[name]=val}});var id,af,af_name,af_id,af_val,af_unit,af_label,af_input,af_help,html,x;var cf_key=cb.cf.getCFkey(options.data,loc_id,cat_id,'cf');var pm_key=!1;if(form_type!=='search')
{pm_key=cb.cf.getCFkey(options.data,loc_id,cat_id,'pm')}
console.log('cf_key:'+cf_key);if(typeof options.last_cf_key!=='undefined')
{if(options.last_cf_key==={cf_key:cf_key,pm_key:pm_key})
{console.log('cf.render.options.SKIP_SAME');return}}
options.last_cf_key={cf_key:cf_key,pm_key:pm_key};if(form_type==='search')
{html=cb.cf.populateFormSearch(cf_key,options)}
else{html=cb.cf.populateFormInput(cf_key,options);html+=cb.cf.populateFormInputPayment(pm_key,options)}
$target.html(html);cb.select.convert($target.find('select'));if(typeof options.onChange!=='undefined')
{options.onChange()}},populateFormInput:function(cf_key,options)
{console.log('populateFormInput');var id,af,af_name,af_id,af_val,af_label,af_input,af_help,html,x;html='';if(cf_key!==!1)
{console.log(options.data.cf[cf_key]);for(x in options.data.cf[cf_key])
{if(typeof options.data.af[x]==='undefined')
{continue}
id=x.replace('i','');af=options.data.af[x];af_name='cf['+id+']';if(typeof options.id_prefix==='undefined')
{af_id=af_name}
else{af_id=options.id_prefix+af_name}
af_label='<label for="'+af_id+'">'+af.n+'</label>';if(af.h)
{af_help='<span class="form-help">'+af.h+'</span>'}
else{af_help=''}
if(typeof options.existing_values[af_name]==='undefined')
{af_val=''}
else{af_val=options.existing_values[af_name]}
af_input='';switch(af.t)
{case 'price':case 'number':if(typeof af.v==='undefined'||af.v==='')
{af_input='<input type="number" name="'+af_name+'" id="'+af_id+'" value="'+af_val+'" class="input">'}
else{af_input='<span class="input-group">'+'<input type="number" name="'+af_name+'" id="'+af_id+'" value="'+af_val+'" class="input">'+'<span class="button addon">'+af.v+'</span>'+'</span>'}
break;case 'checkbox':af_input='<span class="adfield_checkbox">';if(af.afv)
{for(y in af.afv)
{var option_id=(y.replace('i','')*1);var af_name_=af_name+'['+option_id+']';var checked='';if(typeof options.existing_values[af_name_]!=='undefined')
{checked='checked="checked"'}
af_input+='<label class="input-checkbox"> '+'<input type="checkbox" name="'+af_name_+'" value="'+option_id+'" '+checked+' />'+'<span class="checkmark"></span> '+af.afv[y]+'</label>'}}
af_input+='</span>';break;case 'radio':af_input='<span class="adfield_radio">';var first_radio='checked="checked"';if(af.afv)
{for(y in af.afv)
{var option_id=(y.replace('i','')*1);var af_id_=af_id+'['+option_id+']';var checked=first_radio;if(af_val*1===option_id)
{checked='checked="checked"'}
af_input+='<label class="input-radio"> '+'<input type="radio" name="'+af_name+'" value="'+option_id+'" '+checked+' />'+'<span class="checkmark"></span> '+af.afv[y]+'</label>';first_radio=''}}
af_input+='</span>';break;case 'dropdown':af_input='<select name="'+af_name+'" id="'+af_id+'">';if(af.afv)
{for(y in af.afv)
{af_input+='<option value="'+(y.replace('i','')*1)+'" '+(af_val===y.replace('i','')?'selected="selected"':'')+'>'+af.afv[y]+'</option>'}}
af_input+='</select>';break;default:var input_types={url:'url',email:'email',video_url:'url'};var type=input_types[af.t]||'text';af_input='<input type="'+type+'" name="'+af_name+'" id="'+af_id+'" value="'+af_val+'" class="input">'}
html+=options.template.replace('${label}',af_label).replace('${input}',af_input).replace('${help}',af_help)}}
return html},populateFormInputPayment:function(cf_key,options)
{console.log('populateFormInput');var af_label,af_input,af_help,html,x;html='';if(cf_key!==!1&&typeof options.templatepayment!=='undefined')
{console.log(options.data.pm[cf_key]);for(x in options.data.pm[cf_key])
{if(options.data.pm[cf_key][x]*1>0)
{af_label=options.templatepayment[x].title;af_input=options.templatepayment[x].input.replace('{price}',options.data.pm[cf_key][x]);af_help='';html+=options.template.replace('${label}',af_label).replace('${input}',af_input).replace('${help}',af_help)}}}
return html},populateFormSearch:function(cf_key,options)
{var id,af,af_name,af_id,af_val,af_label,af_input,af_help,x;var lng_from=options.lng.from||'from';var lng_to=options.lng.to||'to';var html='';if(cf_key!==!1)
{for(x in options.data.cf[cf_key])
{if(options.data.cf[cf_key][x]!=='1')
{continue}
id=x.replace('i','');af=options.data.af[x];af_name='cf['+id+']';if(typeof options.id_prefix==='undefined')
{af_id=af_name}
else{af_id=options.id_prefix+af_name}
af_label='<label for="'+af_id+'">'+af.n+'</label>';if(af.h)
{af_help='<span class="form-help">'+af.h+'</span>'}
else{af_help=''}
if(typeof options.existing_values[af_name]==='undefined')
{af_val=''}
else{af_val=options.existing_values[af_name]}
af_input='';switch(af.t)
{case 'price':case 'number':var af_name_from,af_name_to,af_val_from,af_val_to,af_input_addon;af_name_from=af_name+'[from]';af_name_to=af_name+'[to]';af_val_from='';af_val_to='';af_input_addon='';if(typeof options.existing_values[af_name_from]!=='undefined')
{af_val_from=options.existing_values[af_name_from]}
if(typeof options.existing_values[af_name_to]!=='undefined')
{af_val_to=options.existing_values[af_name_to]}
if(typeof af.v!=='undefined'&&af.v!=='')
{af_input_addon='<span class="button addon">'+af.v+'</span>'}
af_input='<span class="input-group">'+'<input type="number" name="'+af_name_from+'" id="'+af_id+'" value="'+af_val_from+'" placeholder="'+lng_from+'" class="input">'+'<input type="number" name="'+af_name_to+'" id="'+af_name_to+'" value="'+af_val_to+'" placeholder="'+lng_to+'" aria-label="'+lng_to+'" class="input">'+af_input_addon+'</span>';break;case 'checkbox':af_input='<span class="adfield_checkbox">';if(af.afv)
{for(y in af.afv)
{var option_id=(y.replace('i','')*1);var af_name_=af_name+'['+option_id+']';var checked='';if(typeof options.existing_values[af_name_]!=='undefined')
{checked='checked="checked"'}
af_input+='<label class="input-checkbox"> '+'<input type="checkbox" name="'+af_name_+'" value="'+option_id+'" '+checked+' />'+'<span class="checkmark"></span> '+af.afv[y]+'</label>'}}
af_input+='</span>';break;case 'radio':af_input='<span class="adfield_radio">';var first_radio='checked="checked"';if(af.afv)
{var checked=first_radio;af_input+='<label class="input-radio"> '+'<input type="radio" name="'+af_name+'" value="'+option_id+'" '+checked+' />'+'<span class="checkmark"></span> '+options.lng.all+'</label>';first_radio='';for(y in af.afv)
{var option_id=(y.replace('i','')*1);checked=first_radio;if(af_val*1===option_id)
{checked='checked="checked"'}
af_input+='<label class="input-radio"> '+'<input type="radio" name="'+af_name+'" value="'+option_id+'" '+checked+' />'+'<span class="checkmark"></span> '+af.afv[y]+'</label>';first_radio=''}}
af_input+='</span>';break;case 'dropdown':af_input='<select name="'+af_name+'" id="'+af_id+'">';af_label='';af_input+='<option value="">'+af.n+'</option>';if(af.afv)
{for(y in af.afv)
{af_input+='<option value="'+(y.replace('i','')*1)+'" '+(af_val===y.replace('i','')?'selected="selected"':'')+'>'+af.afv[y]+'</option>'}}
af_input+='</select>';break;default:var input_types={url:'url',email:'email',video_url:'url'};var type=input_types[af.t]||'text';af_input='<input type="'+type+'" name="'+af_name+'" id="'+af_id+'" value="'+af_val+'" class="input">'}
html+=options.template.replace('${label}',af_label).replace('${input}',af_input).replace('${help}',af_help)}}
return html},buldCatLocReverseArr:function(data)
{if(!data.category_reverse)
{data.category_reverse={};for(x in data.category)
{for(y in data.category[x])
{data.category_reverse[y]=x.replace('p','')*1}}}
if(!data.location_reverse)
{data.location_reverse={};for(x in data.location)
{for(y in data.location[x])
{data.location_reverse[y]=x.replace('p','')*1}}}},getCFkey:function(data,loc_id,cat_id,data_key)
{cb.cf.buldCatLocReverseArr(data);var cat_parents=[cat_id];var cat_id_parent=cat_id;while(data.category_reverse['i'+cat_id_parent])
{cat_id_parent=data.category_reverse['i'+cat_id_parent];cat_parents.push(cat_id_parent)}
if(cat_id!==0)
{cat_parents.push(0)}
var loc_parents=[loc_id];var loc_id_parent=loc_id;while(data.location_reverse['i'+loc_id_parent])
{loc_id_parent=data.location_reverse['i'+loc_id_parent];loc_parents.push(loc_id_parent)}
if(loc_id!==0)
{loc_parents.push(0)}
var cf_id='';for(x in loc_parents)
{for(y in cat_parents)
{cf_id=loc_parents[x]+'_'+cat_parents[y];if(data[data_key][cf_id])
{return cf_id}}}
return!1}},setupItemDropdown:function()
{$items=$('#jq-dropdown-item').not('.setupItemDropdown');if($items.length)
{$('#jq-dropdown-item').addClass('setupItemDropdown');$items.on('click','li a[data-v]',function()
{var $me=$(this);var datav=$me.attr('data-v');var confirmed=!0;if(datav.length)
{var jqDropdown=$me.parents('.jq-dropdown:first');var trigger=jqDropdown.data('jq-dropdown-trigger');var $item=trigger.parents('.item:first,.bulk_actions:first');var $form=$item.parents('form:first');var $parent=$item.parents('ul:first');var $items=$parent.find('.item');var $menus=$items.find('.controls [data-jq-dropdown="#jq-dropdown-item"]');var $checks=$items.find('.controls .input-checkbox');var countChecked=function()
{$parent.find('.bulk_actions_count').text($items.find(':checkbox:checked').length)};var clickSelectAll=function()
{$items.find(':checkbox[name="ad[]"]').prop('checked',!0);countChecked()};var clickSelectNone=function()
{$items.find(':checkbox[name="ad[]"]').prop('checked',!1);$menus.show();$checks.hide();$parent.find('.bulk_actions').hide();$parent.off('.item_select')};var clickSelectItem=function()
{var $me_item=$(this);var $check=$me_item.find(':checkbox[name="ad[]"]');$check.prop('checked',!$check.prop('checked'));countChecked();return!1};switch(datav)
{case 'select':$menus.hide();$checks.removeClass('display-none').show();$item.find(':checkbox[name="ad[]"]').prop('checked',!0);countChecked();$parent.on('click.item_select','.item',clickSelectItem);$parent.on('click.item_select','.bulk_actions .select_all',clickSelectAll);$parent.on('click.item_select','.bulk_actions .select_none',clickSelectNone);$parent.find('.bulk_actions').removeClass('display-none').show();break;case 'select_all':clickSelectAll();break;case 'select_none':clickSelectNone();break;default:if($me.is('[data-confirm]'))
{confirmed=confirm($me.attr('data-confirm'))}
if(confirmed)
{if($items.find(':checkbox:checked').length<1)
{$item.find(':checkbox[name="ad[]"]').prop('checked',!0)}
$form.find('#bulk_actions').val(datav);$form.submit()}}}});$items.on('show',function()
{var $me=$(this);var trigger=$me.data('jq-dropdown-trigger');var $item=trigger.parents('.item:first,.bulk_actions:first');var $append_ul=$item.find('.jq-dropdown-item-append,.jq-dropdown-item-prepend');$me.find('.appended').remove();$me.find('li').removeClass('display-none');$append_ul.each(function()
{var $me_append=$(this);var $append=$me_append.find('li').clone();if($append.length)
{$append.addClass('appended');if($me_append.is('.jq-dropdown-item-append'))
{$me.find('ul:first').append($append)}
else{$me.find('ul:first').prepend($append)}}
if($me_append.data('hide'))
{var arr_hide=$me_append.data('hide').split(',');$me.find('a[data-v="'+arr_hide.join('"],a[data-v="')+'"]').each(function()
{$(this).parents('li:first').addClass('display-none')});$(window).resize()}});if($item.is('.bulk_actions'))
{var $parent=$item.parents('ul:first');var $items_checked=$parent.find('.item').has(':checkbox:checked');var arr_hide_all=[];$items_checked.each(function(i)
{var $me_item=$(this);var $append_ul_many=$me_item.find('.jq-dropdown-item-append,.jq-dropdown-item-prepend');var arr_hide=[];$append_ul_many.each(function()
{var $me_append_ul_many=$(this);if($me_append_ul_many.data('hide'))
{var arr_hide_=$me_append_ul_many.data('hide').split(',');arr_hide=arr_hide.concat(arr_hide_)}});arr_hide_all.push(arr_hide)});arr_hide_all=cb.intersectionArray(arr_hide_all);if(arr_hide_all.length>0)
{$me.find('a[data-v="'+arr_hide_all.join('"],a[data-v="')+'"]').each(function()
{$(this).parents('li:first').addClass('display-none')});$(window).resize()}}})}},intersectionArray:function()
{var result=[];var lists;if(arguments.length===1)
{lists=arguments[0]}
else{lists=arguments}
for(var i=0;i<lists.length;i++)
{var currentList=lists[i];for(var y=0;y<currentList.length;y++)
{var currentValue=currentList[y];if(result.indexOf(currentValue)===-1)
{if(lists.filter(function(obj)
{return obj.indexOf(currentValue)==-1}).length==0)
{result.push(currentValue)}}}}
return result},editSlug:function(target)
{if(target.length>1)
{target.each(function()
{var $me=$(this);cb.editSlug($me)});return!1}
var listen=$(target.data('listen'));var id=target.data('editableslug');var generate_url=target.data('url');var hideclass=target.data('hideclass');target.find('.edit_slug').click(edit);target.find('.edit_slug_cancel').click(cancel);target.find('.edit_slug_ok').click(generate);listen.blur(generate);function edit()
{target.find('input').removeProp('readonly').data('cancelval',target.find('input').val()).focus();target.find('.edit_slug').addClass(hideclass);target.find('.edit_slug_ok,.edit_slug_cancel').removeClass(hideclass).appendTo(target);return!1}
function generate()
{var name=listen.val();var slug=target.find('input').val();$.post(BASE_URL+generate_url,{id:id,name:name,slug:slug},function(data)
{try
{var dataObj=jQuery.parseJSON(data);set(dataObj.slug)}
catch(error)
{alert(data)}});return!1}
function set(str)
{target.find('input').val(str).prop('readonly','readonly');target.find('.edit_slug').removeClass(hideclass).appendTo(target);target.find('.edit_slug_ok,.edit_slug_cancel').addClass(hideclass)}
function cancel()
{var cancelval=target.find('input').data('cancelval');set(cancelval);return!1}},buttonSwitch:function(selector,options)
{options=$.extend({values:{'0':{title:'on',cssClass:'white'},'1':{title:'off',cssClass:'green'}}},options||{});$(document).on('click',selector,function(e)
{var $me=$(this);var id=$me.data('id');var action=$me.data('switch');var url=options.url;if(typeof action==='undefined'||typeof id==='undefined')
{console.log('buttonSwitch:[not-defined]');return!1}
$me.addClass('loading');$.post(BASE_URL+url,{action:action,id:id},function(data)
{console.log('buttonSwitch:'+url+':done');var data_found=!1;var values=options.values;if(typeof values[action]!=='undefined')
{values=values[action]}
for(x in values)
{if(x==data)
{var title=values[x].title;var cssClass=values[x].cssClass;data_found=!0;break}}
if(data_found)
{if($me.is('label.input-switch'))
{console.log('buttonSwitch:checkbox:'+data+":"+(data*1?!0:!1));$me.find('input:checkbox').prop('checked',data*1?!0:!1)}
else{for(x in values)
{if(typeof values[x].cssClass!=='undefined')
{$me.removeClass(values[x].cssClass)}}
if(typeof cssClass!=='undefined')
{$me.addClass(cssClass)}
$me.text(title)}}
else{console.log('buttonSwitch:'+url+':data_fail:'+data);alert(data)}}).fail(function()
{console.log('buttonSwitch:'+url+':fail')}).always(function()
{$me.removeClass('loading')});return!1})},isInViewport:function($obj,complete)
{var elementTop=$obj.offset().top;var elementBottom=elementTop+$obj.outerHeight();var viewportTop=$(window).scrollTop();var viewportBottom=viewportTop+$(window).height();var ret=!1;if(complete===!0)
{ret=elementBottom<viewportBottom&&elementTop>viewportTop}
else{ret=elementBottom>viewportTop&&elementTop<viewportBottom}
return ret},isInViewportRight:function($obj)
{var elementLeft=$obj.offset().left;var elementRight=elementLeft+$obj.outerWidth();var viewportWidth=$(window).width();var ret=elementRight<viewportWidth;return ret},initSortableTable:function()
{$(document).on('click','table .table_sort',function()
{var $me=$(this);var $parent=$me.parent();var $table=$me.parents('table:first');var n=$parent.find('th,td').index($me);var is_number=$me.hasClass('table_sort_number');var is_descending=$me.data('is_descending')||!1;if(n>=0)
{$me.data('is_descending',!is_descending);var $table_clone=$table.clone(!0);var tbl_arr=[];$table_clone.find('tr:not(:first)').each(function(index)
{var $me=$(this);var val=$me.find('td').eq(n).text()||'';val=is_number?Number(val):val.toLowerCase();tbl_arr.push({val:val,tr:$me})});tbl_arr.sort(function(a,b)
{var x=a.val;var y=b.val;if(x<y)
{return-1}
if(x>y)
{return 1}
return 0});if(is_descending)
{tbl_arr.reverse()}
$table_clone.find('tr:not(:first)').remove();for(x in tbl_arr)
{$table_clone.append(tbl_arr[x].tr)}
$table.after($table_clone);$table.remove()}})},initToggle:function()
{$('body').on('click','[data-toggle]',function()
{var $me=$(this);var $terget=$($me.data('target'));var toggle=$me.data('toggle');if($terget.is('.display-none'))
{$terget.hide().removeClass('display-none')}
switch(toggle)
{case 'cb_slide':$terget.slideToggle('fast');$('.cancel',$terget).click(function()
{$terget.slideToggle('fast');return!1});break;case 'cb_hide':$terget.toggle();$('.cancel',$terget).click(function()
{$terget.toggle();return!1});break;case 'cb_modal':if(cb.isV2Enabled())
{cb.modal.init(function()
{var $terget_clone=$terget.clone(!0);$terget_clone.show();cb.modal.open({$content:$terget_clone,classClose:'.cancel'})})}
else{setupColorbox(function()
{$terget.show();$.colorbox({inline:!0,href:$terget,onCleanup:function()
{$terget.hide()}});$terget.on('click','.cancel',function()
{$.colorbox.close();return!1})})}
break;case 'cb_batch':var url=$me.data('url');if(url.length>0)
{function cb_batch()
{if($me.data('processing')!='1')
{$me.data('processing','1');$.post(BASE_URL+url,{nounce:nounce}).done(function(data)
{if(typeof data==='object')
{$terget.html(data.text);if(data.continue=='1')
{$me.removeData('processing');cb_batch()}}
else{alert(data);$me.removeData('processing')}}).fail(function()
{alert('Failed, please try again.');$me.removeData('processing')})}}
cb_batch()}
break}
return!1});$('[data-toggle="cb_load_silent"][data-url]').each(function()
{var $me=$(this);var url=$me.data('url');if(url.length>0)
{$.post(BASE_URL+url,{nounce:nounce}).done(function(data)
{console.log(data)}).fail(function()
{console.log('cb_load_silent:fail:'+url)}).always(function()
{$me.remove()})}})},initReport:function()
{$(document).on('click','a.report',function()
{var $me=$(this);var id=$me.attr('rel');var msg_confirm=$me.attr('msg-confirm');var msg_input=$me.attr('msg-input');var reason='';if(id)
{if(typeof msg_confirm=="undefined")
{return!1}
if(confirm(msg_confirm))
{if(typeof msg_input!="undefined")
{reason=prompt(msg_input,"")}
$.post(BASE_URL+'post/report/',{id:id,reason:reason},function(data)
{if(data!='')
{alert(data)}})}}
return!1})},initStats:function()
{var id=$('.js_stat').data('itemid')||0;var time_wait=1000;if(id||$('body._cron').length)
{$('body:last').animate({delay:1},time_wait,function()
{$.post(BASE_URL+'post/cntItem/',{id:id,nounce:nounce},function(data)
{console.log('initStats:'+id+':'+data)})})}},initTabs:function($obj)
{var tabsClick=function()
{var $me=$(this);var $tabs=$me.parents('.tabs:first');var $container=$('body:first');if($tabs.data('container'))
{$container=$tabs.parents('.'+$tabs.data('container')+':first')}
var $other_tab_content=$container.find('.'+$me.data('hide'));if($other_tab_content.is('.display-none'))
{$other_tab_content.hide().removeClass('display-none')}
$other_tab_content.hide();$tabs.find('.active').removeClass('active');$container.find('.'+$me.data('show')).fadeIn('fast');$container.find('[keep_hidden]').hide();$me.addClass('active');return!1};$obj.on('click','.tabs a',tabsClick);var $tabs=$obj.find('.tabs');$tabs.find('a:first').click()},initSidebar:function()
{var $sidebar=$('.sidebar');if($sidebar.length)
{$sidebar.find('li').has('ul').find('a:first').addClass('has_submenu');$sidebar.on('click','.has_submenu',function(e)
{e.preventDefault();var $me=$(this);var $ul=$me.parents('li:first').find('ul');var $ul_all=$('ul ul',$sidebar);$ul_all.removeClass('current');$ul.addClass('current');$ul_all.not('.current').slideUp('fast');$ul.slideToggle()});$('.popup_sidebar').click(function()
{$sidebar.addClass('sidebar_visible');$('body').addClass('overflow-hidden')});$(document).on('click','.sidebar_visible',function(e)
{if(!$(e.target).parents('.sidebar_content:first').length)
{$sidebar.removeClass('sidebar_visible');$('body').removeClass('overflow-hidden')}})}},initMiniTable:function()
{if($('.tblmin').length)
{$('.tblmin:not(:has(.tblmin-main))').find('tr').find('th:first,td:first').addClass('tblmin-main');$('table.tblmin tr').find('td.tblmin-main:first').prepend('<span class="button small outline tblmin-expand"></span>');$('table.tblmin tr').find('td:not(.tblmin-main):empty').addClass('tblmin-empty');$(document).on('click','.tblmin tr td.tblmin-main',function(e)
{var $me=$(this);var $target=$(e.target);var $target_all=$target.parents().addBack();if(!$target_all.is('a'))
{var $tr=$me.parents('tr:first');var $table=$me.parents('.tblmin:first');$table.find('tr').not($tr).removeClass('tblmin-show');$tr.toggleClass('tblmin-show');$table.find('tr').not('.tblmin-show').find('.tblmin-expand').removeClass('focus');$table.find('.tblmin-show .tblmin-expand').addClass('focus')}})}},pwa:{init:function()
{if(typeof pwa_sw!=='undefined')
{if(pwa_sw.url==='disable')
{cb.pwa.unregister()}
else{cb.pwa.resister()}}
else{}},resister:function()
{if('serviceWorker' in navigator)
{window.addEventListener('load',function()
{navigator.serviceWorker.register(pwa_sw.url).then(function(registration)
{console.log('pwa service worker ready');registration.update()}).catch(function(error)
{console.log('Registration failed with '+error)})})}},unregister:function()
{navigator.serviceWorker.getRegistrations().then(function(registrations)
{for(let registration of registrations)
{registration.unregister()}})}},initSearchFilter:function()
{if($('.search_form_toggle').length)
{cb.modal.init(function()
{$(document).on('click','.search_form_toggle',function()
{var $me=$(this);var is_literal=$me.is('[data-target-literal]');var $form=$me.parents('form:first');if($me.data('target'))
{$form=$($me.data('target')).first()}
if($form.length)
{if(is_literal)
{var i=0;var placeholder='_initSearchFilter_placeholder_'+i;while($('.'+placeholder).length>0)
{i++;placeholder='_initSearchFilter_placeholder_'+i}
$form.after('<span class="'+placeholder+'"></span>');$form_clone=$form}
else{var $form_clone=$form.clone(!0)}
var revert_display_none=!1;if($form_clone.is('.display-none'))
{$form_clone.removeClass('display-none');revert_display_none=!0}
$form_clone.addClass('expanded');$form_clone.find('p:last').addClass('action_buttons');cb.modal.open({$content:$form_clone,classClose:'.cancel',onClose:function()
{if(is_literal)
{if(revert_display_none)
{$form_clone.addClass('display-none')}
$form_clone.removeClass('expanded');$form_clone.find('p.action_buttons').removeClass('action_buttons');$('.'+placeholder).after($form_clone);$('.'+placeholder).remove()}}})}})})}},modal:{_modal:null,_modalCurrent:null,init:function(fnc)
{if(typeof tingle==='undefined')
{$.getScriptCached(URL_PUBLIC+'public/js/tingle.min.js').done(function()
{cb.modal.init(fnc)});return!1}
if(typeof cb.modal.init_done==='undefined')
{cb.modal.init_done=!0;$(window).on('hashchange',function(event)
{if(window.location.hash!=="#modal")
{cb.modal.close()}})}
if(typeof fnc!=='undefined')
{fnc()}},_onOpen:function()
{window.location.hash="modal";cb.modal._modalCurrent=cb.modal._modal;document.activeElement.blur()},_onClose:function()
{if(cb.modal._modalCurrent!==null)
{cb.modal._modalCurrent.destroy()}
cb.modal._modalCurrent=null;if(window.location.hash==='#modal')
{window.history.back()}},open:function(options)
{if(typeof tingle==='undefined')
{cb.modal.init(cb.modal.open(options));return!1}
cb.modal.close();if(typeof options.onOpen!=='undefined')
{var fnc=options.onOpen;options.onOpen=function()
{fnc();cb.modal._onOpen()}}
else{options.onOpen=cb.modal._onOpen}
if(typeof options.onClose!=='undefined')
{var fnc=options.onClose;options.onClose=function()
{fnc();cb.modal._onClose()}}
else{options.onClose=cb.modal._onClose}
if(typeof options.classClose!=='undefined')
{options.$content.on('click',options.classClose,function()
{cb.modal.close();return!1})}
var $actions_buttons=options.$content.find('.action_buttons');if($actions_buttons.length)
{options.footer=!0;options.stickyFooter=!0;$actions_buttons.hide()}
cb.modal._modal=new tingle.modal(options);cb.modal._modal.setContent(options.$content[0]);if($actions_buttons.length)
{cb.modal._modal.setFooterContent('');$actions_buttons.find('.button').each(function()
{var $me=$(this);var label=$me.html();var cssClass=$me.attr('class');cb.modal._modal.addFooterBtn(label,cssClass,function()
{$me[0].click()})})}
cb.modal._modal.open();return cb.modal._modal},close:function()
{if(cb.modal._modalCurrent!==null)
{cb.modal._modalCurrent.close()}},resize:function()
{if(cb.modal._modalCurrent!==null)
{cb.modal._modalCurrent.checkOverflow()}},openImage:function($obj)
{if($obj.is('[href]'))
{var src=$obj.attr('href');var img=$('<img>').attr('src',src);var $content=$('<div class="gallery_slider_modal_content"></div>');$content.append('<div class="gallery_slider_item"></div>');$content.find('.gallery_slider_item').append(img);cb.modal.open({$content:$content,cssClass:['gallery_slider_modal']})}}},initSlider:function()
{if($('.gallery_slider').length)
{var init_modal=!1;var init_slider=!1;$('.gallery_slider').each(function()
{var $gallery=$(this);var arr_img=$gallery.find('a[href]');var arr_video=$gallery.find('div.gallery_video');if(arr_img.length>0)
{var $big_images=$gallery.find('a[href],div.gallery_video').map(function(index)
{var $me=$(this);var replacement;if($me.is('a[href]'))
{replacement=$("<img>").attr('data-src-afterload',$me.attr('href')).attr('data-src-small',$me.find('img').attr('src'))}
else{replacement=$me.clone(!0)}
replacement=$('<div class="gallery_slider_item"></div>').append(replacement);return replacement.get(0)});if($big_images.length)
{init_modal=!0;var $content=$('<div class="gallery_slider_modal_content"></div>');$content.append($big_images);$gallery.data('gallery_slider_modal',$content)}}
if((arr_img.length+arr_video.length)>1)
{$gallery.addClass('slider-yes');if(typeof $content!=='undefined'&&$content.length>0)
{$content.addClass('modal-slider-yes')}
init_slider=!0}});if(init_slider)
{cb.sly.start({obj:$('.gallery_slider.slider-yes'),options:{frameAddClass:'gallery_slider_frame'}})}
if(init_modal)
{cb.modal.init(function()
{$(document).on('click','.gallery_slider a',function()
{console.log('gallery_slider:click:modal.open');var $me=$(this);var href=$me.attr('href');var $gallery=$me.parents('.gallery_slider:first');var $gallery_content=$gallery.data('gallery_slider_modal');if(typeof $gallery_content!='undefined'&&$gallery_content)
{$gallery_content.find('[data-src-small]').each(function()
{var $me=$(this);var src_small=$me.attr('data-src-small');$me.removeAttr('data-src-small');$me.attr('src',src_small)});var $gallery_clone=$gallery_content.clone();cb.modal.open({$content:$gallery_clone,cssClass:['gallery_slider_modal'],onOpen:function()
{cb.lazy.setAfterLoad($gallery_clone);if($gallery_clone.is('.modal-slider-yes'))
{var gotoSlide=0;var gotoSlide_index=0;$gallery_content.find('img').each(function()
{if($(this).attr('src')===href||$(this).attr('data-src-afterload')===href)
{gotoSlide=gotoSlide_index}
gotoSlide_index++});cb.sly.start({obj:$gallery_clone,options:{slideTo:gotoSlide}});cb.sly.reloadIfSly($gallery_clone)}}})}
return!1})})}}},initCarousel:function()
{if($('.list_style_carousel').length)
{if(cb.isV2Enabled())
{cb.sly.start({obj:$('.list_style_carousel')})}
else{$.getScriptCached(URL_PUBLIC+'public/js/jquery.carouFredSel-6.2.1-packed.js').done(function()
{$('.list_style_carousel').carouFredSel({scroll:1,responsive:!0,mousewheel:!0,swipe:{onMouse:!0,onTouch:!0},items:{visible:{min:2,max:20}}})})}}},initSlick:function(fnc)
{if(typeof cb.initSlick.slick==='undefined')
{$.getScriptCached(URL_PUBLIC+'public/js/slick.min.js').done(function()
{cb.initSlick.slick=!0;cb.initSlick(fnc)});return!1}
if(typeof fnc!=='undefined')
{fnc()}},sly:{loaded:!1,init:function(fnc)
{if(!cb.sly.loaded)
{$.getScriptCached(URL_PUBLIC+'public/js/sly.min.js').done(function()
{cb.sly.loaded=!0;$(window).on('resize',function()
{cb.throttle(function()
{console.log('sly.reload:resize');$('.sly_frame').sly('reload')},'resize_sly',500)});cb.sly.init(fnc)});return!1}
if(typeof fnc!=='undefined')
{fnc()}},start:function(params)
{if(!cb.sly.loaded)
{cb.sly.init(function()
{cb.sly.start(params)});return!1}
if(typeof params!=='undefined')
{var $obj=params.obj;$obj.each(function()
{var $me=$(this);if($me.parents('.sly_wrap').length>0)
{return!1}
var $wrap=$('<div class="sly_wrap">'+'<div class="sly_frame">'+'</div>'+'<button class="sly_prev" aria-label="Previous" type="button" aria-disabled="true">Previous</button>'+'<button class="sly_next" aria-label="Next" type="button" aria-disabled="true">Next</button>'+'<ul class="sly_pages"></ul>'+'</div>');var $frame=$wrap.find('.sly_frame');$me.after($wrap);$frame.append($me);var options={horizontal:1,itemNav:'centered',smart:1,activateOn:'click',mouseDragging:1,touchDragging:1,releaseSwing:1,scrollBy:1,speed:300,elasticBounds:1,prevPage:$wrap.find('.sly_prev'),nextPage:$wrap.find('.sly_next'),pagesBar:$wrap.find('.sly_pages')};options=$.extend(options,params.options||{});if(typeof options.frameAddClass!=='undefined')
{$frame.addClass(options.frameAddClass)}
$frame.sly(options);if(typeof options.slideTo!=='undefined')
{console.log('slideTo:toCenter:'+options.slideTo);$frame.sly('toCenter',options.slideTo)}})}},getFrame:function(obj)
{return obj.parents('.sly_frame:first')},reloadIfSly:function(obj)
{cb.sly.reloadIfSlyFrame(cb.sly.getFrame(obj))},reloadIfSlyFrame:function($frame)
{if($frame.length>0&&$frame.is('.sly_frame')&&cb.sly.loaded)
{var src=$frame.find('img:first').attr('src');cb.throttle(function()
{$frame.sly('reload')},'reloadIfSlyFrame_'+src,200)}},reloadOnload:function($img)
{cb.sly.reloadIfSly($img);$img.on("load",function()
{cb.sly.reloadIfSly($img)}).each(function()
{if(this.complete)
{$(this).load()}})}},initGallery:function()
{if($('.gallery a').length)
{setupColorbox(function()
{$('.gallery a').colorbox();$('.gallery a.iframe,.gallery a.vimeo,.gallery a.youtube').colorbox({iframe:!0,width:'800px',height:'600px'})})}},initQRcode:function()
{if($('a.qr_code').length)
{if(cb.isV2Enabled())
{cb.modal.init(function()
{$(document).on('click','a.qr_code',function()
{var $me=$(this);cb.modal.openImage($me);return!1})})}
else{setupColorbox(function()
{$('a.qr_code').colorbox({photo:!0})})}}},initBack:function()
{var isSame=(document.referrer.indexOf(window.location.host)!==-1);var $links=$('.js_back_if_same');if(isSame&&$links.length>0)
{$links.each(function()
{var $me=$(this);$me.attr('href','#'+$me.attr('href'))});$links.on('click',function()
{history.go(-1);return!1})}},phone:{init:function()
{$('[data-phonecall]').each(function()
{var $me=$(this);var tel=$($me.data('phonecall')).text();tel=cb.phone.num(tel,$me.data('min'),$me.data('max'));if(!tel.length)
{return!1}
$me.attr('href','tel:'+tel);$me.data('tel',tel);$me.removeClass('display-none')});$('body').on('click','[data-phonecall]',function(e)
{console.log('phcall:start:'+typeof ga);var href=window.location.href;var tel=$(this).data('tel');var obj={'hitType':'event','eventCategory':'phcall','eventAction':tel,'eventLabel':href};console.log(obj);if(e.isDefaultPrevented()||typeof ga!=="function")
{return}
ga('send',obj);console.log('phcall')})},num:function(tel,min,max,regex)
{tel=tel+'';if((tel.match(/\d/g)+'').length<min)
{return''}
if(typeof min=='undefined')
{min=5}
if(typeof max=='undefined')
{max=12}
tel=tel.replace(/,|\||\/|\.|\:/gi,";");tel=tel.replace(/ \+/gi,";+");tel=cb.trim(tel," ;");var arr_tel=tel.split(";");for(x in arr_tel)
{tel=arr_tel[x].replace(/[^\d+]/g,'');var tel_ok=tel.length>=min;tel_ok=tel_ok&&(tel.length<=max);if(typeof regex!='undefined')
{tel_ok=tel_ok&&(regex.test(tel))}
if(!tel_ok)
{tel='';continue}
break}
return tel+''}},trim:function(s,c)
{if(c==="]")
{c="\\]"}
if(c==="\\")
{c="\\\\"}
return s.replace(new RegExp("^["+c+"]+|["+c+"]+$","g"),"")},lazy:{init:function()
{console.log('loadLazyImg:'+$('.lazy[data-src]').length);$('.lazy[data-src]').each(function()
{var $me=$(this);var src=$me.attr('data-src');$me.removeAttr('data-src');if(src.search('.jpg')===-1)
{$me.attr('data-src-later',src)}
else{$me.attr('data-src-now',src)}});var loadLater=function(key,limit)
{$('.lazy['+key+']').slice(0,limit).each(function()
{var $me=$(this);var src=$me.attr(key);$me.removeAttr(key);if($me.is('img'))
{$me.attr('src',src);cb.sly.reloadOnload($me)}
else{$me.css({'background-image':'url('+src+')'})}});if($('.lazy['+key+']').length>0)
{setTimeout(function()
{loadLater(key,limit)},500)}};console.log('loadLazyImg:static:'+$('.lazy[data-src-now]').length+',dyn:'+$('.lazy[data-src-later]').length);loadLater('data-src-now',100);loadLater('data-src-later',1);console.log('loadLazyImg:done')},setAfterLoad:function($obj)
{$obj.find('img[data-src-afterload]').each(function()
{var $me=$(this);var src=$me.attr('data-src-afterload');$me.removeAttr('data-src-afterload');var tmpImg=new Image();tmpImg.onload=function()
{$me.attr('src',src);cb.sly.reloadOnload($me)};tmpImg.src=src})}}};function initContactForm()
{if($('#contact_form .msg-error-line').length<1)
{$('#contact_form').hide()}
else{$('#contact_form').show()}}
function columnizeCats(obj)
{var $obj=$(obj);if($obj.length)
{$.getScriptCached(URL_PUBLIC+'public/js/jquery.columnizer.js').done(function()
{$obj.children().css({float:'none',width:'auto',margin:'10px 0'}).addClass('dontsplit');$obj.columnize({width:180,lastNeverTallest:!0})})}}
if(jQuery)(function($)
{$.extend($.fn,{jqDropdown:function(method,data)
{switch(method)
{case 'show':show(null,$(this));return $(this);case 'hide':hide();return $(this);case 'attach':return $(this).attr('data-jq-dropdown',data);case 'detach':hide();return $(this).removeAttr('data-jq-dropdown');case 'disable':return $(this).addClass('jq-dropdown-disabled');case 'enable':hide();return $(this).removeClass('jq-dropdown-disabled')}}});function show(event,object)
{var trigger=event?$(this):object,jqDropdown=$(trigger.attr('data-jq-dropdown')),isOpen=trigger.hasClass('jq-dropdown-open');if(event)
{if($(event.target).hasClass('jq-dropdown-ignore'))
return;event.preventDefault();event.stopPropagation()}
else{if(trigger!==object.target&&$(object.target).hasClass('jq-dropdown-ignore'))
return}
hide();if(isOpen||trigger.hasClass('jq-dropdown-disabled'))
return;jqDropdown.find('.jq-dropdown-menu li').has('ul').find('a:first').not('.has_submenu').addClass('has_submenu');jqDropdown.find('.jq-dropdown-menu li').has('.has_submenu').not(jqDropdown.find('.jq-dropdown-menu li').has('ul')).removeClass('has_submenu');trigger.addClass('jq-dropdown-open');jqDropdown.data('jq-dropdown-trigger',trigger).show();position();jqDropdown.trigger('show',{jqDropdown:jqDropdown,trigger:trigger})}
function hide(event)
{var targetGroup=event?$(event.target).parents().addBack():null;if(targetGroup&&targetGroup.is('.jq-dropdown'))
{if(targetGroup.is('.jq-dropdown-menu'))
{if(!targetGroup.is('A'))
{return}
else{var $me=$(event.target);if(!$me.is('a'))
{$me=$me.parents('a:first')}
if($me.is('.has_submenu'))
{var $li=$me.parents('li:first');var $ul=$li.find('ul:first');var $jqdropdown=$me.parents('.jq-dropdown-menu:first');if($ul.length)
{event.preventDefault();$jqdropdown.find('ul.current').removeClass('current');$ul.addClass('current');$ul.parentsUntil('ul.jq-dropdown-menu','ul').addClass('current');$jqdropdown.find('ul').not('.current').slideUp('fast');$ul.slideToggle(position);return!1}}}}
else{return}}
$(document).find('.jq-dropdown:visible').each(function()
{var jqDropdown=$(this);jqDropdown.hide().removeData('jq-dropdown-trigger').trigger('hide',{jqDropdown:jqDropdown})});$(document).find('.jq-dropdown-open').removeClass('jq-dropdown-open')}
function position()
{var jqDropdown=$('.jq-dropdown:visible').eq(0),trigger=jqDropdown.data('jq-dropdown-trigger'),hOffset=trigger?parseInt(trigger.attr('data-horizontal-offset')||0,10):null,vOffset=trigger?parseInt(trigger.attr('data-vertical-offset')||0,10):null;if(jqDropdown.length===0||!trigger)
{return}
if(jqDropdown.hasClass('jq-dropdown-relative'))
{var left=trigger.position().left+parseInt(trigger.css('margin-left'),10)+hOffset;var left_right=trigger.position().left-(jqDropdown.outerWidth(!0)-trigger.outerWidth(!0))-parseInt(trigger.css('margin-right'),10)+hOffset;if(jqDropdown.hasClass('jq-dropdown-anchor-right'))
{left=left_right}
var top=trigger.position().top+trigger.outerHeight(!0)-parseInt(trigger.css('margin-top'),10)+vOffset;jqDropdown.css({left:left,top:top})}
else{var left=trigger.offset().left+hOffset;var left_right=trigger.offset().left-(jqDropdown.outerWidth()-trigger.outerWidth())+hOffset;if(jqDropdown.hasClass('jq-dropdown-anchor-right'))
{left=left_right}
var top=trigger.offset().top+trigger.outerHeight()+vOffset;jqDropdown.css({left:left,top:top})}
var $content=jqDropdown.find('.jq-dropdown-menu,.jq-dropdown-panel').eq(0);jqDropdown.removeClass('jq-dropdown-scroll');$content.css({'max-height':''});var contentHeight=$content.outerHeight();var vh=$(window).height();var jo=jqDropdown.offset();var wstop=$(window).scrollTop();var th=trigger.outerHeight()
var edgeoffset=70;var available_space_after=Math.round(vh-(jo.top-wstop)-edgeoffset);var available_space_before=Math.round(jo.top-wstop-th-edgeoffset);var available_space_max=Math.max(available_space_after,available_space_before);if(contentHeight>available_space_max)
{jqDropdown.addClass('jq-dropdown-scroll');$content.css({'max-height':available_space_max});contentHeight=$content.outerHeight()}
if(contentHeight>available_space_after)
{jqDropdown.css({top:(top-contentHeight-th)});console.log('jqDropdown.position.MOVEUP:contentHeight:'+contentHeight)}
if(!cb.isInViewportRight(jqDropdown))
{jqDropdown.css({left:left_right})}}
$(document).on('click.jq-dropdown','[data-jq-dropdown]',show);$(document).on('click.jq-dropdown',hide);$(window).on('resize',position)})(jQuery)