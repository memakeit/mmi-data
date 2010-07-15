<?php defined('SYSPATH') or die('No direct script access.');

// Test route
if (Kohana::$environment !== Kohana::PRODUCTION)
{
	Route::set('test/data', 'test/data/<controller>(/<action>)')
	->defaults(array
	(
		'directory' => 'test/data',
	));
}
