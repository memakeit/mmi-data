<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Jelly helper functions.
 *
 * @package		MMI Data
 * @author		Me Make It
 * @copyright	(c) 2010 Me Make It
 * @license		http://www.memakeit.com/license
 */
class Kohana_MMI_Jelly extends MMI_DB
{
	/**
	 * Do a database select using Jelly.
	 * Array data is unserialized and formatted according to the model's meta data.
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
	 * @param	string	the model name
	 * @param	boolean	return the results as an array?
	 * @param	array	an associative array of query settings
	 * @return	mixed
	 */
	public static function select($model, $as_array = TRUE, $query_parms = array())
	{
		// Extract query parameters
		$query_parms = MMI_DB::_get_query_parms($query_parms);
		extract($query_parms,  EXTR_OVERWRITE);

		// Configure array key, columns, db, distinct, limit, offset, order by, and where type parameters
		$columns = self::_get_columns($columns);
		$db = self::_get_db($db);
		$distinct = self::_get_distinct($distinct);
		$limit = self::_get_limit($limit);
		$offset = self::_get_offset($offset);
		$order_by = self::_get_order_by($order_by);
		$where_type = self::_get_where_type($where_type);

		// Create the query builder
		$builder = Jelly::select($model);
		$builder->distinct($distinct)->limit($limit)->offset($offset);
		if ($as_array)
		{
			$builder->as_assoc();
		}

		// Set select columns
		if ($limit === 1 AND ! empty($columns))
		{
			self::_set_columns($builder, array_keys($columns));
		}
		else
		{
			self::_set_columns($builder, $columns);
		}

		 // Set where parameters
		self::_set_where_parms($builder, $where_parms, $where_type);

		// Set order by
		self::_set_order_by($builder, $order_by);

		// Execute query and format results
		$result = $builder->execute($db);
		unset($builder);

		if ($as_array)
		{
			$result = self::as_array($result, $columns, TRUE);
		}
		return $result;
	}

	/**
	 * Save a Jelly model.
	 *
	 * @param 	Jelly_Model	the model object
	 * @param	array		the error messages if the save fails
	 * @return	boolean
	 */
	public static function save(Jelly_Model $model, & $errors = array())
	{
		$success = TRUE;
		if (count($model->changed()) > 0)
		{
			try
			{
				$model->save();
				$success = TRUE;
			}
			catch (Validate_Exception $e)
			{
				$success = FALSE;
				$errors = $e->array->errors();
				MMI_Log::log_error(__METHOD__, __LINE__, 'Validation exception: '.Kohana::exception_text($e).'. Validation errors: '.implode(', ', $errors));
			}
			catch (Exception $e)
			{
				$success = FALSE;
				$errors[] = Kohana::exception_text($e);
				MMI_Log::log_error(__METHOD__, __LINE__, Kohana::exception_text($e));
			}
		}
		return $success;
	}

	/**
	 * Return Jelly data as an array.
	 *
	 * @param	mixed		the data
	 * @param	array		the columns names
	 * @param	boolean		decode the results? (unserialize, format dates, etc.)
	 * @param	Jelly_meta	meta information about the model
	 * @return	array
	 */
	public static function as_array($data, $columns = NULL, $decode = TRUE, Jelly_Meta $meta = NULL)
	{
		$is_model = FALSE;
		$columns_specified = (is_array($columns) AND count($columns) > 0);

		// Process Jelly collection
		if ($data instanceof Jelly_Collection)
		{
			$temp = $data->as_array();
			if (count($temp) > 0)
			{
				$meta = $data->current()->meta();
			}
			$data = $temp;
		}

		// Process Jelly model
		if ($data instanceof Jelly_Model)
		{
			$meta = $data->meta();
			$is_model = TRUE;
			$temp = $data->as_array();
			if ($columns_specified and Arr::is_assoc($columns))
			{
				// Map column names to aliases
				foreach ($temp as $name => $value)
				{
					$key = Arr::get($columns, $name);
					if ( ! empty($key))
					{
						$temp[$key] = $value;
					}
				}
				$temp = array_intersect_key($temp, array_flip($columns));
			}
			$data = array($temp);
		}

		// Decode data
		if ($decode AND count($data) > 0 AND $meta instanceof Jelly_Meta)
		{
			$decodings = self::_get_decodings($meta, $columns);
			$data = self::_decode($data, $decodings);
		}

		if ($is_model AND count($data) > 0)
		{
			$data = $data[0];
		}
		return $data;
	}

	/**
	 * Get decoding methods and parameters.
	 *
	 * @param	Jelly_Meta	the meta information for the model
	 * @param	array		an associative array of columns names
	 * @return	array
	 */
	protected static function _get_decodings(Jelly_Meta $meta, $columns = NULL)
	{
		$columns_specified = (is_array($columns) AND count($columns) > 0);
		$ip_function = (function_exists('inet_ntop')) ? 'inet_ntop' : 'long2ip';

		$decodings = array();
		$fields = $meta->fields();
		foreach ($fields as $name => $field)
		{
			$actions = array();
			if ($field instanceof Field_IPAddress)
			{
				$actions[$ip_function] = NULL;
			}
			elseif ($field instanceof Field_Serialized)
			{
				$actions['unserialize'] = NULL;
			}
			elseif ($field instanceof Field_Timestamp)
			{
				$actions['date'] = array('format' => $field->pretty_format);
			}
			if ($columns_specified)
			{
				$name = Arr::get($columns, $name, $name);
			}
			if (count($actions) > 0)
			{
				$decodings[$name] = $actions;
			}
		}
		unset($fields);
		return $decodings;
	}

	/**
	 * Decode values.
	 *
	 * @param	array	the data to decode
	 * @param	array	the decoding rules and parameters
	 * @return	array
	 */
	protected static function _decode($data, $decodings)
	{
		if (count($decodings) > 0)
		{
			foreach ($data as $idx => $item)
			{
				foreach ($item as $name => $value)
				{
					$decode_methods = Arr::get($decodings, $name, array());
					foreach ($decode_methods as $method => $parms)
					{
						switch ($method)
						{
							case 'date':
								$item[$name] = $method($parms['format'], $value);
								break;

							case 'unserialize':
								$temp = $value;
								if (is_string($value))
								{
									$temp = $method($value);
									if ($temp === FALSE)
									{
										$temp = $value;
									}
								}
								$item[$name] = $temp;
								break;

							default:
								$item[$name] = $method($value);
								break;
						}
					}
					$data[$idx] = $item;
				}
			}
		}
		return $data;
	}
} // End Kohana_MMI_Jelly
