<?php

/*
  oembed-php - A simple and lightweight PHP library to implement oEmbed support
  Copyright (C) 2010 Fabian Pimminger
  This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
  You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 * 
 * 
 * Usage: 
 * $videoUrl = new VideoUrl($url);
 * $videoUrl = new VideoUrl($url,array('class' => 'custom_css_class', 'width' => 400, 'height' => 300));
 * if($videoUrl->is_valid)
 * {
 * 		echo $videoUrl->html;
 * }
 * 
 * Test urls: 
 * https://www.youtube.com/watch?v=QjKO10hKtYw
 * https://www.youtube.com/watch?v=Iw8idyw_N6Q
 * https://www.youtube.com/watch?v=9UcR9iKArd0
 * 
 * 
 * 
 */

class VideoUrl
{

	static protected $providers;
	static $_instance;
	public $is_valid = false;
	public $url;
	public $html;
	public $thumb;
	public $id;
	public $iframe;
	private $provider;
	private $args = array(
		'width'	 => 560,
		'height' => 315,
		'class'	 => ''
	);

	function __construct($url = '', $args = "")
	{
		self::initProviders();
		$this->_run($url, $args);
	}

	/**
	 * Render the content and return it
	 * ex: echo new View('blog', array('title' => 'My title'));
	 *
	 * @return string content of the view
	 */
	public function __toString()
	{
		return $this->html;
	}

	static function initProviders()
	{
		if (isset(self::$providers))
		{
			return true;
		}

		/*
		 * providers. 
		  $this->providers["|http://(www\.)?youtube.com/watch.*|i"] = "http://www.youtube.com/oembed";
		  $this->providers["|http://(www\.)?flickr.com/.*|i"] = "http://www.flickr.com/services/oembed/";
		  $this->providers["|http://(www\.)?vimeo.com/.*|i"] = "http://vimeo.com/api/oembed.json";
		  $this->providers["|http://(www\.)?viddler.com/.*|i"] = "http://lab.viddler.com/services/oembed/";
		 * 
		 */

		// PROVIDER youtube 
		$obj = new stdClass();
		// URL to find provider
		$obj->url = "%http(s)?://(www\.)?youtube.com/*|http://(www\.)?youtu.be/*%i";
		// patternt to find ideo ID
		$obj->pattern = '%^# Match any youtube URL
        (?:https?://)?  # Optional scheme. Either http or https
        (?:www\.)?      # Optional www subdomain
        (?:             # Group host alternatives
          youtu\.be/    # Either youtu.be,
        | youtube\.com  # or youtube.com
          (?:           # Group path alternatives
            /embed/     # Either /embed/
          | /v/         # or /v/
          | /watch\?v=  # or /watch\?v=
          )             # End path alternatives.
        )               # End host alternatives.
        ([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
        $%x';
		// Index from previous patternt to get video ID
		$obj->index = 1;
		//$obj->oembed = 'http://www.youtube.com/oembed';
		// Patternt to display video as html
		$obj->pattern_html = '<div class="fluidMedia"><iframe width="{WIDTH}" height="{HEIGHT}" src="{URL}" class="video_url{CLASS}" frameborder="0" allowfullscreen></iframe></div>';
		$obj->pattern_iframe = '//www.youtube.com/embed/{ID}';
		$obj->pattern_thumb = '//img.youtube.com/vi/{ID}/1.jpg';
		$obj->name = 'youtube';

		self::$providers[] = $obj;
	}

	/**
	 * check given url and retrieve all variables 
	 * @param type $url
	 * @return type
	 */
	private function _run($url = '', $args = '')
	{
		if (strlen($url))
		{
			$this->url = trim($url);
			$this->html = $this->url;
		}

		if (is_array($args))
		{
			$this->args = array_merge($this->args, $args);
		}

		$this->getProvider();

		//TODO: DISCOVER
		if ($this->provider)
		{
			// use regex method fast no server connection requred
			$result = preg_match($this->provider->pattern, $url, $matches);
			if (false !== $result)
			{
				$this->id = $matches[$this->provider->index];
				if (strlen($this->id))
				{
					$this->iframe = str_replace('{ID}', $this->id, $this->provider->pattern_iframe);
					$this->thumb = str_replace('{ID}', $this->id, $this->provider->pattern_thumb);
					$this->html = str_replace(
							array(
								'{ID}',
								'{URL}',
								'{WIDTH}',
								'{HEIGHT}',
								'{CLASS}',
							), array(
						$this->id,
						$this->iframe,
						$this->args['width'],
						$this->args['height'],
						$this->args['class'] ? ' ' . $this->args['class'] : ''
							), $this->provider->pattern_html);
					$this->is_valid = true;
					$this->provider_name = $this->provider->name;
				}
			}
		}
		return $this;
	}

	/**
	 * Check defined url and return matching provider 
	 * 
	 * @return Object
	 */
	function getProvider()
	{
		if (!isset($this->provider))
		{
			$this->provider = false;
			foreach (self::$providers as $obj)
			{
				$regex = $obj->url;
				if (preg_match($regex, $this->url))
				{
					$this->provider = $obj;
					break;
				}
			}
		}
		return $this->provider;
	}

	/**
	 * convert urls to video to video embed htmls where possible 
	 * 
	 * @param type $content
	 * @param type $args
	 * @return type
	 */
	function autoEmbed($content, $args = "")
	{

		if (is_array($args))
		{
			$this->autoEmbedArgs = $args;
		}
		else
		{
			$this->autoEmbedArgs = array();
		}

		return preg_replace_callback('|^[ \t]*(https?://[^\s"]+)\s*$|im', array(&$this, 'autoEmbedCallback'), $content);
	}

	/**
	 * returns html of video for goven url if possible if not returns url itself
	 * 
	 * @param type $match
	 * @return type
	 */
	function autoEmbedCallback($match)
	{
		$videoUrl = new self($match[0], $this->autoEmbedArgs);
		return $videoUrl->html;
	}

	public static function validation_isValid($url)
	{
		$videoUrl = new self($url);
		if (!$videoUrl->is_valid)
		{
			// this email is registered and used 
			Validation::getInstance()->set_message('VideoUrl::validation_isValid', __('%s is not valid.'));
		}
		return $videoUrl->is_valid;
	}

	public static function imgThumb($size = '')
	{
		// define size
		if (!strlen($size))
		{
			$width = Config::option('ad_thumbnail_width');
			$height = Config::option('ad_thumbnail_height');
			$crop = 1;
		}
		else
		{
			list($width, $height, $crop) = explode('x', $size);
		}

		if (!is_file(UPLOAD_ROOT . '/video.gif'))
		{
			copy(FROG_ROOT . '/public/images/video.gif', UPLOAD_ROOT . '/video.gif');
		}
		$_img = 'video.gif';

		if ($width || $height)
		{
			return Adpics::resizeImageCache($_img, $width, $height, 2);
		}

		return UPLOAD_URL . '/video.gif';
	}

}
