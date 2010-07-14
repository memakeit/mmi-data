<?php defined('SYSPATH') or die('No direct script access.');
/**
* Jelly URL field.
*
* @package		Jelly
* @author		Me Make It
* @copyright	(c) 2009 Me Make It
* @license		http://www.memakeit.com/license
*/
class Field_Url extends Field_String
{
 	/**
	 * Add a URL validation rule if it doesn't already exist.
	 *
	 * @param	string	$model
	 * @param	string	$column
	 * @return	void
	 **/
	public function initialize($model, $column)
	{
		parent::initialize($model, $column);
		$this->filters += array('trim' => NULL);
		$this->rules += array('url' => NULL);
	}
}
