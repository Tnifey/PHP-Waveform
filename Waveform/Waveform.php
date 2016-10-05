<?php

namespace Waveform;

class Waveform
{

	public $error = null;

	// Settings

	protected $settings = array(
		"use" 		=> "lame",
		"detail"	=> 1,
		"temp"		=> __DIRNAME__."temp",
		"force"		=> false,
	);

	public function __construct($settings = array())
	{
		$this->settings($settings);
		if(!is_dir($this->settings['temp']))
			mkdir($this->settings['temp']);
	}

	public function __destruct()
	{
		if(isset($this->file))
		{
			fclose($this->file);
			unset($this->file);
		}

		if(isset($this->wav) AND is_file($this->wav))
		{
			unlink($this->wav);
		}
	}

	public function settings($settings = array())
	{
		if(is_array($settings) AND count($settings) > 0)
			$this->settings = array_merge($this->settings, $settings);
		elseif(!is_array($settings))
			$this->error = "Settings must be an array.";
	}

	public function load($file_path)
	{
		$this->error = null;
		if(is_file($file_path))
		{
			$this->mime = mime_content_type($file_path);

			if($this->settings['force'] OR $this->mime == 'audio/mpeg' OR $this->mime == 'audio/mpeg3' OR $this->mime == 'audio/x-mpeg-3' OR $this->mime == 'video/mpeg' OR $this->mime == 'video/x-mpeg')
			{
				$this->name 	= pathinfo($file_path)['filename'];
				$this->mp3		= $file_path;
				$this->wav 		= $this->settings['temp'].DIRECTORY_SEPARATOR.substr(hash("sha256", $this->name.time()), 0, 9) . ".wav";
				return true;
			}
			else
			{
				$this->error = "This is not audio/mp3 file. It's {$this->mime}.";
				return false;
			}
		}
		else
		{
			$this->error = "File is not exist.";
			return false;	
		}
	}

	public function make()
	{
		if(isset($this->settings['use']))
		{
			if($this->settings['use'] == "lame")
				$this->_lameWav();
			elseif($this->settings['use'] == "ffmpeg")
				$this->_ffmpegWav();
		}
		else
		{
			$this->error = "Please set up codec. Use settings() method to set use variable to lame or ffmpeg";
			return false;
		}

		if($this->_open())
		{
			if($this->_heading())
			{
				if($this->_info())
				{
					if($this->_peeks())
						return true;
					else
						return false;
				}
				else
					return false;
			}
			else
				return false;
		}
		else
			return false;
	}

	private function _values($first, $second)
	{
		$first = hexdec(bin2hex($first));
		$second = hexdec(bin2hex($second)) * 256;
		return $first + $second;
	}

	private function _lameWav()
	{
		exec("lame {$this->mp3} --decode --resample 8 {$this->wav}");
	}

	private function _open()
	{
		if(is_file($this->wav))
		{
			$this->file = fopen($this->wav, "rb");
			return true;
		}
		else
		{
			$this->error = "There is not wav file. At first use load() method or check for http://lame.sourceforge.net/ for lame.exe and lame_enc.dll files.";
			return false;
		}
	}

	private function _heading()
	{
		if(isset($this->file) AND is_file($this->wav))
		{
			$this->heading = array();
			
			// Skip
			fread($this->file, 8);
			// Format
			$this->heading['format'] = fread($this->file, 4);
			// Skip
			fread($this->file, 10);
			// Channel
			$this->heading['channel'] = bin2hex(fread($this->file, 2));
			// Skip
			fread($this->file, 10);

			// Peek
			$this->heading['peek'] = bin2hex(fread($this->file, 2));
			// Skip
			fread($this->file, 6);

			// Format
			$this->format = $this->heading['format'];
			return true;
		}
		else
		{
			$this->error = "File is not open or there is no file.";
			return false;
		}
	}

	private function _info()
	{
		if(is_file($this->wav) AND isset($this->file))
		{
			// Get peek
			$this->peek = hexdec(substr($this->heading['peek'], 0, 2));
			// Count byte
			$this->byte = $this->peek / 8;
			// Get channel
			$this->channel = hexdec(substr($this->heading['channel'], 0, 2));
			// Set ratio
			$this->ratio = ($this->channel == 2)?40:80;
			// Count size
			$this->size = floor((filesize($this->wav) - 44) / ($this->ratio + $this->byte) + 1);
			// Set point
			$this->point = 0;
			// Set detail
			if(isset($this->settings["detail"]) OR $this->settings["detail"] < 1)
				$this->detail = $this->settings["detail"];
			else
				$this->detail = 1;

			return true;
		}
		else
		{
			$this->error = "File is not open or there is no file.";
			return false;
		}
	}

	private function _peeks()
	{
		if(is_file($this->wav) AND isset($this->file))
		{
			// Detail
			$detail = ceil($this->size / ($this->detail * 100));

			// Create peeks array
			$this->peeks = array();
			while(!feof($this->file) AND $this->point < $this->size)
			{
				// Skip bytes
				if($this->point++ % $detail == 0)
				{
					$bits = array();

					// Get current bits
					for($i = 0; $i < $this->byte; $i++)
						$bits[$i] = fgetc($this->file);

					// Get current peek value
					switch($this->byte)
					{
						case 1:
							$data = $this->_values($bits[0], $bits[1]);
							break;

						case 2:
							if(ord($bits[1]) & 128)
								$temp = 8;
							else
								$temp = 128;

							$temp = chr((ord($bits[1]) & 127) + $temp);
							$data = floor($this->_values($bits[0], $temp) / 256);
							unset($temp);
							break;
					}

					//	Move cursor to next byte
					fseek($this->file, $this->ratio, SEEK_CUR);

					// Get current progress
					$progress = number_format($this->point / $this->size * 100, 2);
					// Get Ys
					$y1 = number_format($data / 255 * 100, 2);
					$y2 = 100 - $y1;

					// Make new peek in peeks
					$this->peeks[] = (object) array(
						"progress"		=> number_format($this->point / $this->size * 100, 2),
						"y1"			=> $y1,
						"y2"			=> $y2,
					);
				}
				else
				{
					// Skip this byte
					fseek($this->file, $this->ratio + $this->byte, SEEK_CUR);
				}
			}
			return true;
		}
		else
		{
			$this->error = "File is not open or there is no file.";
			return false;
		}
	}

	public function png()
	{
		if(isset($this->peeks) AND count($this->peeks > 0))
			return (new PNG($this->peeks));
		else
			return false;
	}

}