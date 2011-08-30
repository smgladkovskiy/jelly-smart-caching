<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Jelly Builder extention to enable smart caching
 *
 * @author Sergei Gladkovskiy <smgladkovskiy@gmail.com>
 */
class Caching_Jelly_Builder extends Jelly_Core_Builder {

	public function __construct($model = NULL, $key = NULL)
	{
		Cache::$default = 'sqlite';

		// Get a cache instance
		$cache_file = Cache::instance();

		// Set a GC probability of 15%
		$gc = 15;

		// If the GC probability is a hit
		if (rand(0,99) <= $gc and $cache_file instanceof Kohana_Cache_GarbageCollect)
		{
		  // Garbage Collect
		  $cache_file->garbage_collect();
		}

		parent::__construct($model, $key);
	}
	/**
	 * Executes the query as a SELECT statement
	 *
	 * @param   string  $db
	 * @return  Jelly_Collection | Jelly_Model
	 */
	public function select($db = NULL)
	{
		$db   = $this->_db($db);
		$meta = $this->_meta;

		if ($meta)
		{
			// Select all of the columns for the model if we haven't already
			empty($this->_select) AND $this->select_column('*');
			// Trigger before_select callback
			if(is_object($meta->events())) $meta->events()->trigger('builder.before_select', $this);
		}

		// Ready to leave the builder, we need to figure out what type to return
		$this->_result = $this->_build(Database::SELECT);

		// Return an actual array
		if ($this->_as_object === FALSE OR Jelly::meta($this->_as_object))
		{
			$this->_result->as_assoc();
		}
		else
		{
			$this->_result->as_object($this->_as_object);
		}

	    // Make cache id based on sql query to avoid information crossing
		$id = md5($this->__toString());

		// Extract cached data if it exists
		$result = Cache::instance()->get($id);

		// Make cache routine if result is empty
		if( ! $result)
		{
			$model = ($this->_meta) ? $this->_meta->model() : $this->_model;

			// Pass off to Jelly_Collection, which manages the result
			$result = new Jelly_Collection($this->_result->execute($db), $this->_as_object);

		    // Set cache data
			if(Kohana::$caching)
			{
				Cache::instance()->set_with_tags($id, $result, NULL, array($model));
			}
		}

		// pass cached data to $this->_result
	    $this->_result = $result;

		// Trigger after_query callbacks
		if ($meta)
		{
			if(is_object($meta->events())) $meta->events()->trigger('builder.after_select', $this);
		}

		// If the record was limited to 1, we only return that model
		// Otherwise we return the whole result set.
		if ($this->_limit === 1)
		{
			$this->_result = $this->_result->current();
		}

		return $this->_result;
	}

	/**
	 * Executes the query as an INSERT statement
	 *
	 * @param   string  $db
	 * @return  array
	 */
	public function insert($db = NULL)
	{
		$db   = $this->_db($db);
		$meta = $this->_meta;

		// Trigger callbacks
		$meta AND $meta->events()->trigger('builder.before_insert', $this);

		// Ready to leave the builder
		$result = $this->_build(Database::INSERT)->execute($db);

		$model = ($this->_meta) ? $this->_meta->model() : $this->_model;
	    Cache::instance()->delete_tag($model);

		// Trigger after_query callbacks
		$meta AND $meta->events()->trigger('builder.after_insert', $this);

		return $result;
	}

	/**
	 * Executes the query as an UPDATE statement
	 *
	 * @param   string  $db
	 * @return  int
	 */
	public function update($db = NULL)
	{
		$db   = $this->_db($db);
		$meta = $this->_meta;

		// Trigger callbacks
		$meta AND $meta->events()->trigger('builder.before_update', $this);

		// Ready to leave the builder
		$result = $this->_build(Database::UPDATE)->execute($db);

		$model = ($this->_meta) ? $this->_meta->model() : $this->_model;
	    Cache::instance()->delete_tag($model);

		// Trigger after_query callbacks
		$meta AND $meta->events()->trigger('builder.after_update', $this);

		return $result;
	}

	/**
	 * Executes the query as a DELETE statement
	 *
	 * @param   string  $db
	 * @return  int
	 */
	public function delete($db = NULL)
	{
		$db     = $this->_db($db);
		$meta   = $this->_meta;
		$result = NULL;

		// Trigger callbacks
		if ($meta)
		{
			// Listen for a result to see if we need to actually delete the record
			$result = $meta->events()->trigger('builder.before_delete', $this);
		}

		if ($result === NULL)
		{
			$result = $this->_build(Database::DELETE)->execute($db);

		    $model = ($this->_meta) ? $this->_meta->model() : $this->_model;
	        Cache::instance()->delete_tag($model);
		}

		// Trigger after_query callbacks
		if ($meta)
		{
			// Allow the events to modify the result
			$result = $meta->events()->trigger('builder.after_delete', $this);
		}

		return $result;
	}

	/**
	 * Counts the current query builder
	 *
	 * @param   string  $db
	 * @return  int
	 */
	public function count($db = NULL)
	{
		$db   = $this->_db($db);
		$meta = $this->_meta;

		// Trigger callbacks
		if($meta AND is_object($meta->events())) $meta->events()->trigger('builder.before_select', $this);

		// Start with a basic SELECT
		$query = $this->_build(Database::SELECT)->as_object(FALSE);

		// Dump a few unecessary bits that cause problems
		$query->_select = $query->_order_by = array();

	    // Make cache id based on sql query to avoid information crossing
		$id = md5('count_'.$this->__toString());

	    // Extract cached data if it exists
		$result = Cache::instance()->get($id);

		// Make cache routine if result is empty
		if( ! $result)
		{
			$model = ($this->_meta) ? $this->_meta->model() : $this->_model;

		    // Find the count
			$result = (int) $query
		               ->select(array('COUNT("*")', 'total'))
		               ->execute($db)
		               ->get('total');
		    // Set cache data
			if(Kohana::$caching)
			{
				Cache::instance()->set_with_tags($id, $result, NULL, array($model));
			}
		}

		// Trigger after_query callbacks
		if($meta AND is_object($meta->events())) $meta->events()->trigger('builder.after_select', $this);

		return $result;
	}
} // End Jelly_Builder