<?php defined('SYSPATH') or die('No direct script access.');

// Test routes
if (Kohana::$environment !== Kohana::PRODUCTION)
{
	Route::set('mmi/data/test', 'mmi/data/test/<controller>(/<action>)')
	->defaults(array
	(
		'directory' => 'mmi/data/test',
	));
}
