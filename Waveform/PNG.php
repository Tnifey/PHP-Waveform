<?php

namespace Waveform;

class PNG
{
	// Size
	public $width 	= 100;
	public $height 	= 100;

	// Color
	public $color = array();
	public $background = array();

	public function __construct($peeks)
	{
		$this->color = (object) array(
			"r" 			=> 0,
			"g" 			=> 0,
			"b" 			=> 0,
			"transparent" 	=> false
		);
		$this->background = (object) array(
			"r" 			=> 255,
			"g" 			=> 255,
			"b" 			=> 255,
			"transparent" 	=> false
		);
		$this->peeks = $peeks;
	}

	public function size($width = 100, $height = 100)
	{
		$this->width = $width;
		$this->height = $height;
		return $this;
	}

	public function color($r, $g, $b, $transparent = false)
	{
		$this->color->r = $r;
		$this->color->g = $g;
		$this->color->b = $b;
		$this->color->transparent = $transparent;
		return $this;
	}

	public function background($r, $g, $b, $transparent = false)
	{
		$this->background->r = $r;
		$this->background->g = $g;
		$this->background->b = $b;
		$this->background->transparent = $transparent;
		return $this;
	}

	public function save($path)
	{
		if(!is_numeric($this->width) OR $this->width < 200)
			$this->width = 100;

		if(!is_numeric($this->height) OR $this->height < 100)
			$this->height = 100;

		$this->wscale = round($this->width / 100, 2);
		$this->hscale = round($this->height / 100, 2);

		$image = imagecreatetruecolor($this->width, $this->height);
		
		$color = imagecolorallocate($image, $this->color->r, $this->color->g, $this->color->b);
		$background = imagecolorallocate($image, $this->background->r, $this->background->g, $this->background->b);

		// Background
		imagefill($image, 0, 0, $background);

		// If background is transparent
		if($this->background->transparent == true)
			imagecolortransparent($image, $background);

		// Generate lines
		foreach($this->peeks as $peek)
		{
			$progress = round($peek->progress, 4) * $this->wscale;
			$y1 = round($peek->y1, 4) * $this->hscale;
			$y2 = round($peek->y2, 4) * $this->hscale;

			//	Draw line
			imageline($image, $progress, $y1, $progress, $y2, $color);
		}

		//	If waveform is transparent
		if($this->color->transparent == true)
			imagecolortransparent($image, $color);

		// Save image
		imagepng($image, $path);

		// Destroy image
		imagedestroy($image);
		return $this;
	}

}