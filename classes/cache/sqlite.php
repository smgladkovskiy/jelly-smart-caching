<?php defined('SYSPATH') or die('No direct script access.');

class Cache_Sqlite extends Kohana_Cache_Sqlite {

	/**
	 * Sets up the PDO SQLite table and
	 * initialises the PDO connection
	 *
	 * @param  array     configuration
	 * @throws  Cache_Exception
	 */
	protected function __construct(array $config)
	{
		parent::__construct($config);

		$database = Arr::get($this->_config, 'database', NULL);

		if ($database === NULL)
		{
			throw new Cache_Exception('Database path not available in Kohana Cache configuration');
		}

		// Load new Sqlite DB
		$this->_db = new PDO('sqlite:'.$database,'', '', array(
	        PDO::ATTR_PERSISTENT => TRUE
		));

		// Test for existing DB
		$result = $this->_db->query("SELECT * FROM sqlite_master WHERE name = 'caches' AND type = 'table'")->fetchAll();

		// If there is no table, create a new one
		if (0 == count($result))
		{
			$database_schema = Arr::get($this->_config, 'schema', NULL);

			if ($database_schema === NULL)
			{
				throw new Cache_Exception('Database schema not found in Kohana Cache configuration');
			}

			try
			{
				// Create the caches table
				$this->_db->query(Arr::get($this->_config, 'schema', NULL));
			}
			catch (PDOException $e)
			{
				throw new Cache_Exception('Failed to create new SQLite caches table with the following error : :error', array(':error' => $e->getMessage()));
			}
		}
	}
}