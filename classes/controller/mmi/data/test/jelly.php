<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Jelly helper test controller.
 *
 * @package		MMI Data
 * @author		Me Make It
 * @copyright	(c) 2010 Me Make It
 * @license		http://www.memakeit.com/license
 */
class Controller_MMI_Data_Test_Jelly extends Controller
{
	/**
	 * @var boolean turn debugging on?
	 **/
	public $debug = TRUE;

	/**
	 * Test Jelly helper functions.
	 *
	 * @return	void
	 */
	public function action_index()
	{
		$model = 'MMI_Emails';
		$as_array = TRUE;
		$query_parms = array
		(
			'where_parms' => array('id' => array(1, 2))
		);
		$data = MMI_Jelly::select($model, $as_array, $query_parms);
		MMI_Debug::dump($data, 'data');
	}
} // End Controller_MMI_Data_Test_Jelly
