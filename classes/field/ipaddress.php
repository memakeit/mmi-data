<?php defined('SYSPATH') or die('No direct script access.');
/**
* Jelly IP address field.
*
* @package		Jelly
* @author		Me Make It
* @copyright	(c) 2010 Me Make It
* @license		http://www.memakeit.com/license
*/
class Field_IPAddress extends Field_Integer
{
	/**
	 * Convert the integer to an IP address.
	 *
	 * @param	mixed	$value
	 * @return	mixed
	 **/
	public function set($value)
	{
		if ($value === NULL OR ($this->null AND empty($value)))
		{
			return NULL;
		}

		if ( ! empty($value) AND is_int($value))
		{
			if (function_exists('inet_ntop'))
			{
				$value = inet_ntop($value);
			}
			else
			{
				$value = long2ip($value);
			}
		}
		return $value;
	}

	/**
	 * Saves the IP address as an integer.
	 *
	 * @param	Jelly	$model
	 * @param	mixed	$value
	 * @return	integer
	 */
	public function save($model, $value, $loaded)
	{
		if ( ! empty($value) AND is_string($value) AND Validate::ip($value))
		{
			if (function_exists('inet_pton'))
			{
				$value = inet_pton($value);
			}
			else
			{
				$value = ip2long($value);
			}
		}
		return $value;
	}
}
