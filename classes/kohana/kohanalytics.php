<?php defined('SYSPATH') or die('No direct script access.');

abstract class Kohana_Kohanalytics
{
	// Kohanalytics instance
	protected static $_instance;
	
	/**
	 * Singleton pattern
	 *
	 * @return Auth
	 */
	public static function instance()
	{
		if ( ! isset(Kohanalytics::$_instance))
		{
			// Load the configuration for this type
			$config = Kohana::config('kohanalytics');

			// Create a new session instance
			Kohanalytics::$_instance = new Kohanalytics($config);
		}
	
		return Kohanalytics::$_instance;
	}
	
	protected $_config;
	protected $_gapi;
	
	/**
	 * Loads configuration options.
	 *
	 * @return  void
	 */
	public function __construct($config = array())
	{
		// Save the config in the object
		$this->_config = $config;
		
		// Load the GAPI http://code.google.com/p/gapi-google-analytics-php-interface/ library
		require Kohana::find_file('vendor', 'GAPI/gapi.class');
		
		$this->_gapi = new gapi($this->_config['username'], $this->_config['password']);
	}
	
	public function request_account_data()
	{
		return $this->_gapi->requestAccountData();
	}
	
	public function daily_visit_count($start_date = FALSE, $end_date = FALSE)
	{
		if ( ! $start_date)
		{
			$start_date = date('Y-m-d', strtotime('1 month ago'));
		}
		
		if ( ! $end_date)
		{
			$end_date = date('Y-m-d');
		}
	
		$results = $this->_gapi->requestReportData($this->_config['report_id'], array('date'), array('visits'), NULL, NULL, $start_date, $end_date);
	
		$visits = array();
		foreach ($results as $r)
		{	
			$visits[$r->getDate()] = $r->getVisits();
		}
		
		ksort($visits);
	
		return $visits;
	}
}