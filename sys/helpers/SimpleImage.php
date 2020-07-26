<?php

/*
 * File: SimpleImage.php
 * Author: Simon Jarvis
 * Modified by: Miguel Fermín
 * Based in: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
 * 
 * This program is free software; you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the 
 * GNU General Public License for more details: 
 * http://www.gnu.org/licenses/gpl.html
 * 
 *  
 * 
  // Usage:
 * 
 * New usage 
 * SimpleImage::fromFile('lemon.jpg')->resize(300,200)->save('lemon_300x200.jpg');
 * 
 * 
 * 
  // Load the original image
  $image = new SimpleImage('lemon.jpg');

  // Resize the image to 600px width and the proportional height
  $image->resizeToWidth(600);
  $image->save('lemon_resized.jpg');

  // Create a squared version of the image
  $image->square(200);
  $image->save('lemon_squared.jpg');

  // Scales the image to 75%
  $image->scale(75);
  $image->save('lemon_scaled.jpg');

  // Resize the image to specific width and height
  $image->resize(80, 60);
  $image->save('lemon_resized2.jpg');

  // Resize the canvas and fill the empty space with a color of your choice
  $image->maxareafill(600, 400, 32, 39, 240);
  $image->save('lemon_filled.jpg');

  // Output the image to the browser:
  $image->output();
 * 
 * 
 */

class SimpleImage
{

	var $filename;
	var $image;
	var $image_type;
	var $image_width;
	var $image_height;
	var $error_msg = array();

	function __construct($filename = null)
	{
		if(!empty($filename))
		{
			$this->load($filename);

			$this->filename = $filename;
		}
		else
		{
			$this->setError(__('Image not found.'));
			return false;
		}
	}

	public function __destruct()
	{
		if($this->image)
		{
			imagedestroy($this->image);
		}
	}

	/**
	 *
	 * @param string $filename
	 * @return SimpleImage
	 */
	public static function fromFile($filename = null)
	{
		return new self($filename);
	}

	function load_new($filename)
	{
		$image_info = getimagesize($filename);
		$this->image_type = $image_info[2];

		$content = file_get_contents($filename);
		$this->image = @imagecreatefromstring($content);
		if(!$this->image)
		{
			$this->setError(__('Image type is not supported.'));
			return false;
		}

		return $this;
	}

	function load($filename)
	{
		$image_info = getimagesize($filename);
		$this->image_width = $image_info[0];
		$this->image_height = $image_info[1];
		$this->image_type = $image_info[2];

		switch($this->image_type)
		{
			case IMAGETYPE_JPEG:
			case IMAGETYPE_GIF:
			case IMAGETYPE_PNG:			
				break;
			default:
				if (!$this->image_type || 18 == $this->image_type)
				{
					Benchmark::cp('[SimpleImage:load:webp:' . $this->image_type . ':' . $filename . ']');
					// try webp format because it might be webp image with jpg filename
					// convert webp to jpg first 
					// Load the WebP file
					//$source = @imagecreatefromwebp($filename);
					$img_str = @file_get_contents($filename);
					if ($img_str)
					{
						$source = @imagecreatefromstring($img_str);
					}

					if (!$source)
					{
						Benchmark::cp('[SimpleImage:wepb:imagecreatefromwebp]');

						$source = @imagecreatefromwebp($filename);

						// try to fix yellow color image 
						ob_start();
						imagewebp($source);
						$cont = ob_get_contents();
						ob_end_clean();
						//imagedestroy($image);
						$source2 = @imagecreatefromstring($cont);
						unset($cont);

						if ($source2)
						{
							imagedestroy($source);
							$source = $source2;
						}



						/*
						  $file = 'hnbrnocz.jpg';
						  $image = imagecreatefromjpeg($file);
						  ob_start();
						  imagejpeg($image, NULL, 100);
						  $cont = ob_get_contents();
						  ob_end_clean();
						  imagedestroy($image);
						  $content = imagecreatefromstring($cont);
						  imagewebp($content, 'images/hnbrnocz.webp');
						  imagedestroy($content);
						 */
					}

					// Convert it to a jpeg file with 80% quality
					if ($source)
					{
						Benchmark::cp('[SimpleImage:wepb:loaded]');
						imageinterlace($source, true);
						$converted = imagejpeg($source, $filename, 80);
						imagedestroy($source);
						Benchmark::cp('[SimpleImage:converted:' . intval($converted) . ']');

						// converted 
						// load converted image 
						return $this->load($filename);
					}
					else
					{
						Benchmark::cp('[SimpleImage:source NOT LOADED]');
					}
				}

				$this->setError(__('Image type is not supported.'));
				return false;
		}

		return $this;
	}

	private function _load()
	{
		// load image to memory only when really needed
		if(!$this->image)
		{
			switch($this->image_type)
			{
				case IMAGETYPE_JPEG:
					$this->image = imagecreatefromjpeg($this->filename);
					break;
				case IMAGETYPE_GIF:
					$this->image = imagecreatefromgif($this->filename);
					break;
				case IMAGETYPE_PNG:
					$this->image = imagecreatefrompng($this->filename);
					break;
				default:
					$this->setError(__('Image type is not supported.'));
					return false;
			}
		}
	}

	/**
	 * Set an error message
	 *
	 * @access  public
	 * @param   string
	 * @return  void
	 */
	function setError($msg)
	{
		$this->error_msg[] = $msg;
	}

	/**
	 * Display the error message
	 *
	 * @access  public
	 * @param   string
	 * @param   string
	 * @return  string
	 */
	function displayErrors($open = '<p>', $close = '</p>')
	{
		$str = '';
		foreach($this->error_msg as $val)
		{
			$str .= $open . $val . $close;
		}

		return $str;
	}

	function save($filename, $image_type = null, $compression = 75, $permissions = 0777)
	{
		$return = false;

		if(is_null($image_type))
		{
			$image_type = $this->image_type;
		}

		if($this->image)
		{

			if($image_type == IMAGETYPE_JPEG)
			{
				// Enable interlancing
				@imageinterlace($this->image, true);
				$return = @imagejpeg($this->image, $filename, $compression);
			}
			elseif($image_type == IMAGETYPE_GIF)
			{
				$return = @imagegif($this->image, $filename);
			}
			elseif($image_type == IMAGETYPE_PNG)
			{
				$return = @imagepng($this->image, $filename);
			}
		}
		else
		{
			if(strcmp($this->filename, $filename) != 0)
			{
				$return = @copy($this->filename, $filename);
			}
			else
			{
				// same file dont do anything
				$return = true;
			}
		}

		if($return && $permissions != null)
		{
			@chmod($filename, $permissions);
		}

		return $return;
	}

	function output($image_type = IMAGETYPE_JPEG, $quality = 80)
	{
		// load image to memory 
		$this->_load();

		@imageinterlace($this->image, true);

		if($image_type == IMAGETYPE_JPEG)
		{
			header("Content-type: image/jpeg");
			imagejpeg($this->image, null, $quality);
		}
		elseif($image_type == IMAGETYPE_GIF)
		{
			header("Content-type: image/gif");
			imagegif($this->image);
		}
		elseif($image_type == IMAGETYPE_PNG)
		{
			header("Content-type: image/png");
			imagepng($this->image);
		}
	}

	function getWidth()
	{
		if($this->image)
		{
			return imagesx($this->image);
		}
		return $this->image_width;
	}

	function getHeight()
	{
		if($this->image)
		{
			return imagesy($this->image);
		}
		return $this->image_height;
	}

	function resizeToHeight($height)
	{
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		return $this->resize($width, $height);
	}

	function resizeToWidth($width)
	{
		$ratio = $width / $this->getWidth();
		$height = $this->getHeight() * $ratio;
		return $this->resize($width, $height);
	}

	function square($size)
	{
		$new_image = imagecreatetruecolor($size, $size);
		$this->applyTransparentColor($new_image);

		if($this->getWidth() > $this->getHeight())
		{
			$this->resizeToHeight($size);
			imagecopy($new_image, $this->image, 0, 0, ($this->getWidth() - $size) / 2, 0, $size, $size);
		}
		else
		{
			$this->resizeToWidth($size);
			imagecopy($new_image, $this->image, 0, 0, 0, ($this->getHeight() - $size) / 2, $size, $size);
		}

		$this->image = $new_image;

		return $this;
	}

	function touch()
	{
		if(!$this->image)
		{
			return $this->scale(100);
		}
	}

	function scale($scale)
	{
		$width = $this->getWidth() * $scale / 100;
		$height = $this->getHeight() * $scale / 100;
		return $this->resize($width, $height);
	}

	function resize($width, $height)
	{
		// load image to memory 
		$this->_load();

		$new_image = imagecreatetruecolor($width, $height);
		$this->applyTransparentColor($new_image);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
		$this->image = $new_image;

		return $this;
	}

	function cut($x, $y, $width, $height)
	{
		// load image to memory 
		$this->_load();

		$new_image = imagecreatetruecolor($width, $height);
		$this->applyTransparentColor($new_image);
		imagecopy($new_image, $this->image, 0, 0, $x, $y, $width, $height);

		$this->image = $new_image;

		return $this;
	}

	function maxarea($width = null, $height = null)
	{
		//$height = $height ? $height : $width;
		if($width && $this->getWidth() > $width)
		{
			$this->resizeToWidth($width);
		}
		if($height && $this->getHeight() > $height)
		{
			$this->resizeToHeight($height);
		}

		return $this;
	}

	function crop($width, $height)
	{
		$final_ratio = $width / $height;
		$current_ration = $this->getWidth() / $this->getHeight();

		if($final_ratio >= $current_ration)
		{
			$this->resizeToWidth($width);
		}
		else
		{
			$this->resizeToHeight($height);
		}

		$x = ($this->getWidth() / 2) - ($width / 2);
		$y = ($this->getHeight() / 2) - ($height / 2);

		return $this->cut($x, $y, $width, $height);
	}

	function maxareafill($width, $height, $color = null)
	{
		$this->maxarea($width, $height);

		// resize if different size
		if(($width && $this->getWidth() != $width) || ($height && $this->getHeight() != $height))
		{
			// load image to memory 
			$this->_load();

			$new_image = imagecreatetruecolor($width, $height);

			if(is_null($color))
			{
				// apply transparent if png or gif
				$this->applyTransparentColor($new_image);
			}
			else
			{
				$rgb = $this->html2rgb($color);
				$color_fill = imagecolorallocate($new_image, $rgb[0], $rgb[1], $rgb[2]);
				imagefill($new_image, 0, 0, $color_fill);
			}

			imagecopyresampled($new_image, $this->image, floor(($width - $this->getWidth()) / 2), floor(($height - $this->getHeight()) / 2), 0, 0, $this->getWidth(), $this->getHeight(), $this->getWidth(), $this->getHeight());
			$this->image = $new_image;
		}

		return $this;
	}

	private function applyTransparentColor(& $new_image)
	{
		if($this->image_type == IMAGETYPE_GIF || $this->image_type == IMAGETYPE_PNG)
		{
			$current_transparent = imagecolortransparent($this->image);
			if($current_transparent != -1)
			{
				$transparent_color = @imagecolorsforindex($this->image, $current_transparent);
				$current_transparent = imagecolorallocate($new_image, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
				imagefill($new_image, 0, 0, $current_transparent);
				imagecolortransparent($new_image, $current_transparent);
			}
			elseif($this->image_type == IMAGETYPE_PNG)
			{
				imagealphablending($new_image, false);
				$color = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
				imagefill($new_image, 0, 0, $color);
				imagesavealpha($new_image, true);
			}
		}
	}

	private function html2rgb($color)
	{
		if($color[0] == '#')
		{
			$color = substr($color, 1);
		}

		if(strlen($color) == 6)
		{
			list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
		}
		elseif(strlen($color) == 3)
		{
			list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
		}
		else
		{
			return false;
		}

		$r = hexdec($r);
		$g = hexdec($g);
		$b = hexdec($b);

		return array($r, $g, $b);
	}

	function text($text, $position = 'bottomright', $size = 5, $color = '000000', $bg_color = 'ffffff')
	{
		$padding = 10;
		$fontwidth = imagefontwidth($size);
		$watermark_width = $fontwidth * strlen($text);
		$watermark_height = imagefontheight($size);

		switch($position)
		{
			case 'topright':
				$watermark_pos_x = $this->getWidth() - $watermark_width - $padding;
				$watermark_pos_y = $padding;
				break;
			case 'bottomleft':
				$watermark_pos_x = $padding;
				$watermark_pos_y = $this->getHeight() - $watermark_height - $padding;
				break;
			case 'bottomright':
				$watermark_pos_x = $this->getWidth() - $watermark_width - $padding;
				$watermark_pos_y = $this->getHeight() - $watermark_height - $padding;
				break;
			case 'topleft':
			default:
				$watermark_pos_x = $padding;
				$watermark_pos_y = $padding;
				break;
		}


		if(strlen($bg_color))
		{
			$this->_text($text, $watermark_pos_x + 1, $watermark_pos_y + 1, $size, $bg_color);
		}

		return $this->_text($text, $watermark_pos_x, $watermark_pos_y, $size, $color);
	}

	private function _text($text, $x = 0, $y = 0, $size = 5, $color = '000000')
	{
		// load image to memory 
		$this->_load();

		$rgb = $this->html2rgb($color);

		imagestring($this->image, $size, $x, $y, $text, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));

		return $this;
	}

	function rotate($degree, $color = 'FFFFFF')
	{
		// load image to memory 
		$this->_load();

		$rgb = $this->html2rgb($color);

		$this->image = imagerotate($this->image, $degree, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));

		return $this;
	}

}
