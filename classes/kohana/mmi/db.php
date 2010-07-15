<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database helper functions.
 *
 * @package		MMI Data
 * @author		Me Make It
 * @copyright	(c) 2010 Me Make It
 * @license		http://www.memakeit.com/license
 */
class Kohana_MMI_DB
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
	 * If an array key is specified, the results are returned as an associative
	 * array using the specified key.
	 *
	 * The following keys can be used to specify query settings:
	 *	- columns,
	 *	- db,
	 *	- distinct,
	 *	- limit,
	 *	- offset,
	 *	- order_by,
	 *	- where_parms,
	 *	- where_type
	 *
	 * @param	string	the table name
	 * @param	boolean	return the results as an array?
	 * @param	string	the array key
	 * @param	array	an associative array of query settings
	 * @return	mixed
	 */
	public static function select($table, $as_array = TRUE, $array_key = NULL, $query_parms = array())
	{
		// Extract queruy parameters
		$query_parms = MMI_DB::_get_query_parms($query_parms);
		extract($query_parms,  EXTR_OVERWRITE);

		// Configure array key, columns, db, distinct, limit, offset, order by, and where type settings
		$array_key = self::_get_array_key($array_key);
		$columns = self::_get_columns($columns, $array_key);
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
		self::_set_where_parms($query, $where_parms, $where_type);

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
				if ( ! empty($array_key))
				{
					$array_key = $columns[$array_key];
				}
				foreach ($db_result as $item)
				{
					if (empty($array_key))
					{
						$data[] = $item;
					}
					else
					{
						$data[Arr::get($item, $array_key)] = $item;
					}
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

	/**
	 * Validate the array key.
	 *
	 * @param	string	the array key
	 * @return	mixed
	 */
	protected static function _get_array_key($input)
	{
		$array_key = $input;
		if (MMI_Util::not_set($input))
		{
			$array_key = NULL;
		}
		elseif (MMI_Util::is_set($input) AND ! is_string($input))
		{
			$array_key = NULL;
		}
		return $array_key;
	}

	/**
	 * Validate the column names.
	 * If the array key is not in list of column names, add it.
	 *
	 * @param	array	an associative array of column names and aliases
	 * @param	string	the name of the array
	 * @return	mixed
	 */
	protected static function _get_columns($input, $array_key)
	{
		$columns = $input;
		if (MMI_Util::not_set($input))
		{
			$columns = NULL;
		}
		if (MMI_Util::is_set($input) AND ( ! is_array($input) OR (is_array($input) AND count($input) === 0)))
		{
			$columns = NULL;
		}

		if ( ! empty($columns))
		{
			$columns = MMI_Arr::make_associative($columns);
			if ( ! empty($array_key) AND ! array_key_exists($array_key, $columns))
			{
				$columns[$array_key] = $array_key;
			}
		}
		return $columns;
	}

	/**
	 * Validate the select distinct parameter.
	 *
	 * @param	mixed	select distinct data?
	 * @return	boolean
	 */
	protected static function _get_distinct($distinct)
	{
		if (MMI_Util::not_set($distinct))
		{
			$distinct = FALSE;
		}
		elseif (MMI_Util::is_set($distinct) AND ! is_bool($distinct))
		{
			$distinct = FALSE;
		}
		return $distinct;
	}

	/**
	 * Validate the limit.
	 *
	 * @param	integer	the maximum number of results to return
	 * @return	integer
	 */
	protected static function _get_limit($input)
	{
		$limit = $input;
		if (MMI_Util::not_set($input))
		{
			$limit = 999999;
		}
		elseif (MMI_Util::is_set($input) AND ! is_int($input))
		{
			$limit = 999999;
		}
		return $limit;
	}

	/**
	 * Validate the offset.
	 *
	 * @param	integer	the offset
	 * @return	integer
	 */
	protected static function _get_offset($input)
	{
		$offset = $input;
		if (MMI_Util::not_set($input))
		{
			$offset = 0;
		}
		elseif (MMI_Util::is_set($input) AND ! is_int($input))
		{
			$offset = 0;
		}
		return $offset;
	}

	/**
	 * Validate the order by.
	 *
	 * @param	array	an associative array of order by columns and directions
	 * @return	mixed
	 */
	protected static function _get_order_by($input)
	{
		$order_by = $input;
		if (MMI_Util::not_set($input))
		{
			$order_by = NULL;
		}
		elseif (MMI_Util::is_set($input) AND ( ! is_array($input) OR (is_array($input) AND ! Arr::is_assoc($input))))
		{
			$order_by = NULL;
		}
		return $order_by;
	}

	/**
	 * Validate the where type.
	 *
	 * @param	string	the where type (and | or)
	 * @return	string
	 */
	protected static function _get_where_type($input)
	{
		$where_type = $input;
		if (MMI_Util::not_set($input))
		{
			$where_type = 'and';
		}
		elseif (MMI_Util::is_set($input) AND ( ! is_string($input) OR (is_string($input) AND ! in_array(strtolower(trim($input)), array('and', 'or')))))
		{
			$where_type = 'and';
		}
		return $where_type;
	}

	/**
	 * Get the database instance.
	 *
	 * @param	mixed	the database instance
	 * @return	Database
	 */
	protected static function _get_db($db = 'default')
	{
		if (MMI_Util::not_set($db))
		{
			$db = 'default';
		}
		elseif (MMI_Util::is_set($db) AND ( ! is_string($db) OR ! is_object($db)))
		{
			$db = 'default';
		}
		if ( ! is_object($db))
		{
			$db = Database::instance($db);
		}
		return $db;
	}

	/**
	 * Set the columns for a query.
	 *
	 * @param	Database_Query_Builder_Select	the query builder
	 * @param	array							an associative array of columns
	 * 											names and aliases
	 * @return	void
	 */
	protected static function _set_columns($query, $columns)
	{
		if (MMI_Util::is_set($columns) AND is_array($columns) AND count($columns) > 0)
		{
			if (Arr::is_assoc($columns))
			{
				foreach ($columns as $column => $alias)
				{
					if ($column === $alias)
					{
						$query->select($column);
					}
					else
					{
						$query->select(array($column, $alias));
					}
				}
			}
			else
			{
				foreach ($columns as $column)
				{
					$query->select($column);
				}
			}
		}
	}

	/**
	 * Set the order by parameters for a query.
	 *
	 * @param	Database_Query_Builder_Select	the query builder
	 * @param	array							an associative array of order by
	 * 											settings (column => direction)
	 * @return	void
	 */
	protected static function _set_order_by($query, $order_by)
	{
		if (MMI_Util::is_set($order_by) AND is_array($order_by) AND count($order_by) > 0 AND Arr::is_assoc($order_by))
		{
			foreach ($order_by as $column => $direction)
			{
				if (strcasecmp($direction, 'asc') === 0 OR ! in_array(strtolower(trim($direction)), array('desc', NULL)))
				{
					$direction = NULL;
				}
				$query->order_by($column, $direction);
			}
		}
	}

	/**
	 * Set the where paramaters for a query.
	 *
	 * @param	Database_Query_Builder_Select	the query builder
	 * @param	array							an associative array of where parameters
	 * @param	string							the where type (and | or)
	 * @return	void
	 */
	protected static function _set_where_parms($query, $where_parms, $where_type)
	{
		$where_type = strtolower($where_type).'_where';
		if (MMI_Util::is_set($where_parms) AND is_array($where_parms) AND count($where_parms) > 0 AND Arr::is_assoc($where_parms))
		{
			foreach ($where_parms as $name => $values)
			{
				if (is_array($values) AND count($values) === 1)
				{
					$values = $values[0];
				}
				if (is_array($values) AND count($values) > 0)
				{
					$query->$where_type($name, 'IN', $values);
				}
				else
				{
					$query->$where_type($name, '=', $values);
				}
			}
		}
	}

	/**
	 * Merge the parameters specified with the defaults.
	 *
	 * @param	array	an associative array of query settings (columns, db,
	 * 					distinct, limit, offset, order_by, where_parms, where_type)
	 * @return	array
	 */
	protected static function _get_query_parms($query_parms)
	{
		$defaults = array
		(
			'columns'		=> NULL,
			'db'			=> 'default',
			'distinct'		=> FALSE,
			'limit'			=> NULL,
			'offset'		=> NULL,
			'order_by'		=> NULL,
			'where_parms'	=> NULL,
			'where_type'	=> NULL,
		);
		$query_parms = Arr::merge($defaults, $query_parms);
		return array_intersect_key($query_parms, $defaults);
	}
} // End Kohana_MMI_DB
