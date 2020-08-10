<?php
/**
 * ClassiBase Classifieds Script
 *
 * ClassiBase Classifieds Script by Vepa Halliyev is licensed under a Creative Commons Attribution-Share Alike 3.0 License.
 *
 * @package		ClassiBase Classifieds Script
 * @author		Vepa Halliyev
 * @copyright	Copyright (c) 2009, Vepa Halliyev, veppa.com.
 * @license		http://classibase.com
 * @link		http://classibase.com
 * @since		Version 1.0
 * @filesource
 */

/**
 * class Page
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 * 
 * ------------------------------
 * RELATED CONFIG OPTIONS:
 * 		map_enabled - if displaying map enabled
 * 		map_append_to_description - if there is no map area in theme 
 * 		map_zoom_level - if you exseed daily 25000 request limit you need api key
 * 		map_google_api_key - if you exseed daily 25000 request limit you need api key
 * ------------------------------
 * NOTES: if you wnat to use map then add address custom field to ad. if no then change address field type to regular text. 
 * 
 */
class Map extends Record
{

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	/**
	 * get first address custom field for given ad
	 * 
	 * @param Ad $ad
	 * @return string
	 */
	public static function getAddress($ad, $append_location = true)
	{
		// $address = '';

		// get address value from ad custom field 
		// TODO get this using generic function used to retree price element
		/* if($ad->CategoryFieldRelation)
		  {
		  foreach($ad->CategoryFieldRelation as $cf)
		  {

		  if($cf->AdField->type == AdField::TYPE_ADDRESS)
		  {
		  $af_id = $cf->adfield_id;
		  $val = $ad->AdFieldRelation[$af_id]->val;
		  if(strlen($val))
		  {
		  //$address = Ad::formatCustomValue($cf->AdField, $val);
		  $address = $val;
		  break;
		  }
		  }
		  }
		  } */

		$address = AdFieldRelation::getByType($ad, AdField::TYPE_ADDRESS, false);


		if(strlen($address) && $append_location)
		{
			// append location data to address
			$loc = Location::getFullNameById($ad->location_id, 0, ', ', true);
			if(strlen($loc))
			{
				$address = $address . ', ' . $loc;
			}
		}

		return $address;
	}

	/**
	 * returns 1 if map_append_to_description set to true
	 * this added in order to keep map related options inside this class
	 * 
	 * @return int
	 */
	public static function isAppendToDescription()
	{
		return intval(Config::option('map_append_to_description'));
	}

	/**
	 * returns 1 if map_enabled set to true
	 * this added in order to keep map related options inside this class
	 * 
	 * @return int
	 */
	public static function isEnabled()
	{
		return intval(Config::option('map_enabled'));
	}

	/**
	 * read config value for zoom level
	 * 
	 * @return int
	 */
	public static function zoomLevel()
	{
		$map_zoom_level = Config::option('map_zoom_level');
		if(!strlen($map_zoom_level))
		{
			$map_zoom_level = 14;
		}
		return intval($map_zoom_level);
	}

	public static function removeMapPlaceholderJs($div_id = 'itemMap')
	{
		// remove map placeholder object because there is no address
		// call after jquery loaded
		return '<script> addLoadEvent(function(){$("#' . $div_id . '").remove();});	</script>';
	}

	/**
	 * searches for address custom field and displays google maps if found
	 * uses javascript and works after page loaded	 * 
	 * 
	 * @param Ad $ad
	 * @param string $div_id div where to display map
	 * @return string
	 */
	public static function showAddress($ad, $div_id = 'itemMap')
	{
		if(!self::isEnabled())
		{
			return self::removeMapPlaceholderJs();
		}

		$map_google_api_key = Config::option('map_google_api_key');
		$address = self::getAddress($ad);

		if(!strlen($address))
		{
			// remove map placeholder object because there is no address
			return self::removeMapPlaceholderJs();
		}

		// get contact details
		$contact_details = '';
		if($ad->phone)
		{
			$contact_details .= '<p><b>' . __('Phone') . ' :</b> <span>' . View::escape($ad->phone) . '</span></p>';
		}
		// email
		switch($ad->showemail)
		{
			case Ad::SHOWEMAIL_YES:
				$contact_details .= '<p><b>' . __('Email') . ' :</b> <a href="mailto:' . View::escape($ad->email) . '">' . View::escape($ad->email) . '</a></p>';
				break;
		}

		if(Config::option('view_contact_registered_only') && !AuthUser::isLoggedIn(false))
		{
			// display login link
			$contact_details = '';
		}

		// load google maps and display map
		ob_start();
		?>
		<script>
			var google_maps_api_key = '<?php echo $map_google_api_key ?>';
			var map_zoom_level = <?php echo self::zoomLevel() ?>;
			var address = '<?php echo View::escape($address) ?>';
			var geocoder = false;
			var map = false;



			function initMap()
			{
				//gm_loadScript();
				//script.src = "http://maps.googleapis.com/maps/api/js?key=API_KEY&sensor=TRUE_OR_FALSE&callback=initializeMap";
				var script_src = "//maps.googleapis.com/maps/api/js?sensor=false&callback=gm_initialize";
				if (google_maps_api_key.length > 0)
				{
					script_src += "&key=" + google_maps_api_key;
				}
				$.getScript(script_src);
			}

			function gm_initialize() {
				geocoder = new google.maps.Geocoder();

				// init map with preset latlng
				//var latlng = new google.maps.LatLng(-34.397, 150.644);
				//gm_map_init_center(results[0].geometry.location);

				var $itemMap = $("#<?php echo $div_id ?>");
				// check make map location 
				if ($itemMap.length < 1)
				{
					$itemMap = $('<div id="<?php echo $div_id ?>"></div>');
					$itemMap.css({'height': '250px', 'width': 'auto'});
					$('.description:first').after($itemMap);
				}

				// hide map placeholder for now
				$itemMap.hide();

				// display given address
				gm_showAddress(address);
			}

			function gm_map_init_center(latlng)
			{
				var mapOptions = {
					zoom: map_zoom_level,
					center: latlng,
					mapTypeId: google.maps.MapTypeId.ROADMAP
				};

				// display map placeholder
				$("#<?php echo $div_id ?>").show();

				// load map to placeholder
				map = new google.maps.Map(document.getElementById("<?php echo $div_id ?>"), mapOptions);
			}

			function gm_showAddress(address) {
				if (geocoder) {
					geocoder.geocode({'address': address}, function(results, status) {
						if (status == google.maps.GeocoderStatus.OK) {
							// init map cenetered to location
							if (map)
							{
								// map already creted move to new center
								map.setCenter(results[0].geometry.location);
							}
							else
							{
								// create map centered
								gm_map_init_center(results[0].geometry.location);
							}

							// display arker on found address
							var marker = new google.maps.Marker({
								map: map,
								position: results[0].geometry.location,
								title: "<?php echo View::escape(Ad::getTitle($ad)); ?>"
							});
							var contentString = '<div id="content">' +
									'<div id="siteNotice">' +
									'</div>' +
									'<h1 id="firstHeading" class="firstHeading"><?php echo View::escape(Ad::getTitle($ad)); ?></h1>' +
									'<div id="bodyContent">' +
									'<p><b>Address:</b> ' + address + '</p>' +
									'<?php echo $contact_details ?>' +
									'</div>' +
									'</div>';

							var infowindow = new google.maps.InfoWindow({
								content: contentString
							});
							google.maps.event.addListener(marker, 'click', function() {
								infowindow.open(map, marker);
							});
							// keep marker in center on resize
							/*google.maps.event.addListener(map, 'bounds_changed', function() {
							 map.setCenter(results[0].geometry.location);
							 });*/
							google.maps.event.addDomListener(window, 'resize', function() {
								map.setCenter(results[0].geometry.location);
							});

						} else {
							$("#<?php echo $div_id ?>").remove();
						}
					});
				}
				else
				{
					$("#<?php echo $div_id ?>").remove();
				}
			}

			function gm_loadScript() {
				var script = document.createElement("script");
				script.type = "text/javascript";
				//script.src = "http://maps.googleapis.com/maps/api/js?key=API_KEY&sensor=TRUE_OR_FALSE&callback=initializeMap";
				script.src = "http://maps.googleapis.com/maps/api/js?sensor=false&callback=gm_initialize";
				if (google_maps_api_key.length > 0)
				{
					script.src += "&key=" + google_maps_api_key;
				}
				document.body.appendChild(script);
			}

			// start after jquery loaded
			addLoadEvent(initMap);

		</script>
		<?php
		$return = ob_get_contents();
		ob_clean();

		return $return;
	}

	public static function renderMap_old()
	{

		/*
		 * TODO: render map for given address or long lat 
		 * 
		 */
		?>
		<div id="itemMap" style="width: 100%; height: 240px;"></div>
		<?php
		if($item['d_coord_lat'] != '' && $item['d_coord_long'] != '')
		{
			?>
			<script type="text/javascript">
				var latlng = new google.maps.LatLng(<?php echo $item['d_coord_lat']; ?>, <?php echo $item['d_coord_long']; ?>);
				var myOptions = {
					zoom: 13,
					center: latlng,
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					size: new google.maps.Size(480, 240)
				}

				map = new google.maps.Map(document.getElementById("itemMap"), myOptions);
				var marker = new google.maps.Marker({
					map: map,
					position: latlng
				});
			</script>
			<?php
		}
		else
		{
			?>
			<script type="text/javascript">
				var map = null;
				var geocoder = null;

				var myOptions = {
					zoom: 13,
					center: new google.maps.LatLng(37.4419, -122.1419),
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					size: new google.maps.Size(480, 240)
				}

				map = new google.maps.Map(document.getElementById("itemMap"), myOptions);
				geocoder = new google.maps.Geocoder();

				function showAddress(address) {
					if (geocoder) {
						geocoder.geocode({'address': address}, function(results, status) {
							if (status == google.maps.GeocoderStatus.OK) {
								map.setCenter(results[0].geometry.location);
								var marker = new google.maps.Marker({
									map: map,
									position: results[0].geometry.location
								});
								marker.setMap(map);
							} else {
								$("#itemMap").remove();
							}
						});
					}
				}

			<?php
			$addr = array();
			if(( $item['s_address'] != '' ) && ( $item['s_address'] != null ))
			{
				$addr[] = $item['s_address'];
			}
			if(( $item['s_city'] != '' ) && ( $item['s_city'] != null ))
			{
				$addr[] = $item['s_city'];
			}
			if(( $item['s_zip'] != '' ) && ( $item['s_zip'] != null ))
			{
				$addr[] = $item['s_zip'];
			}
			if(( $item['s_region'] != '' ) && ( $item['s_region'] != null ))
			{
				$addr[] = $item['s_region'];
			}
			if(( $item['s_country'] != '' ) && ( $item['s_country'] != null ))
			{
				$addr[] = $item['s_country'];
			}
			$address = implode(", ", $addr);
			?>

				$(document).ready(function() {
					showAddress('<?php echo osc_esc_js($address); ?>');
				});

			</script>
			<?php
		}
	}

}
