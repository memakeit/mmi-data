<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database helper functions.
 *
 * @package		MMI Data
 * @author		Me Make It
 * @copyright	(c) 2010 Me Make It
 * @license		http://www.memakeit.com/license
 */
class Kohana_MMI_DB extends MMI_Data
{
	/**
	 * Execute a SQL select statement.
	 *
	 * @param	string	the SQL statement
	 * @param	boolean	return the results as an array?
	 * @param	mixed	the database instance or the name of the instance
	 * @return	mixed
	 */
	public static function sql_select($sql, $as_array = TRUE, $db = 'default')
	{
		$data = NULL;
		if (MMI_Util::is_set($sql))
		{
			$db = self::_get_db($db);
			$db_result = $db->query(Database::SELECT, $sql, ! $as_array);
			unset($db);

			if ($as_array)
			{
				$data = array();
				if ($db_result->count() > 0)
				{
					foreach ($db_result as $item)
					{
						$data[] = $item;
					}
				}
				unset($db_result);
			}
			else
			{
				$data = $db_result;
			}
		}
		return $data;
	}

	/**
	 * Do a database select using the Database_Query_Builder_Select.
	 * The following keys can be used to specify query settings:
	 *	- columns,
	 *	- db,
	 *	- distinct,
	 *	- limit,
	 *	- offset,
	 *	- order_by,
	 *	- where_params,
	 *	- where_type
	 *
	 * @param	string	the table name
	 * @param	boolean	return the results as an array?
	 * @param	array	an associative array of query settings
	 * @return	mixed
	 */
	public static function select($table, $as_array = TRUE, $query_params = array())
	{
		// Extract queruy parameters
		$query_params = MMI_DB::_get_query_params($query_params);
		extract($query_params, EXTR_OVERWRITE);

		// Configure array key, columns, db, distinct, limit, offset, order by, and where type settings
		$columns = self::_get_columns($columns);
		$db = self::_get_db($db);
		$distinct = self::_get_distinct($distinct);
		$limit = self::_get_limit($limit);
		$offset = self::_get_offset($offset);
		$order_by = self::_get_order_by($order_by);
		$where_type = self::_get_where_type($where_type);

		// Create and configure query
		$query = new Database_Query_Builder_Select;
		$query->from($table)->distinct($distinct)->limit($limit)->offset($offset);

		// Set columns
		self::_set_columns($query, $columns);

		// Set where parameters
		self::_set_where_params($query, $where_params, $where_type);

		// Set order by
		self::_set_order_by($query, $order_by);

		// Execute query and format results
		$db = self::_get_db($db);
		$db_result = $query->execute($db);
		unset($query);

		if ($as_array)
		{
			$data = array();
			if ($db_result->count() > 0)
			{
				foreach ($db_result as $item)
				{
					$data[] = $item;
				}
			}
			unset($db_result);
		}
		else
		{
			$data = $db_result;
		}
		return $data;
	}
} // End Kohana_MMI_DB
