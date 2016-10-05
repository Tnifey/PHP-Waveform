<?php

// Make this define first !important
define("__DIRNAME__", __DIR__.DIRECTORY_SEPARATOR);

// Include classes
require_once 'Waveform/Waveform.php';
require_once 'Waveform/PNG.php';

// Create new class
$waveform = new Waveform\Waveform(array(
	"detail"	=> 300, # less is worse quality default 1
	"force"		=> true,
));

// Make path to file
$path = __DIR__.DIRECTORY_SEPARATOR."mp3/merankorii_marbershop.mp3";

if(is_file($path))
{
	// Get filename
	$name = pathinfo($path)['filename'];

	// Load mp3 file
	if($waveform->load($path))
	{
		// Make waveform
		if($waveform->make())
		{
			// Create png class
			$png = $waveform->png();
			if($png != false)
			{
				// Set size of png image
				$png->size(1000, 200); # width, height

				// Set waveform color color(int red, int green, int blue[, bool transparent = false])
				$png->color(212, 104, 37, false);

				// Set background color background(int red, int green, int blue[, bool transparent = false])
				$png->background(255, 255, 255, true);

				// Save image as png: set path save png image || set null to output png image
				$png->save("{$name}.png");

				// if you output png remember to set Content-Type to image/png
				header("Content-Type: image/png");
				$png->save(null);
			}
			else
			{
				echo "Peeks is not set. Try change detail setting value.";
			}
		}
		else
		{
			// error is variable which contains errors null if not errors
			echo $waveform->error;
		}
	}
	else
	{
		echo $waveform->error;
	}
}
else
{
	echo "File is not exist.";
}