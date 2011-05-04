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
	
	protected $start_date;
	protected $end_date;
	
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
		
		// Set the default start and end dates. Maybe take this into config?
		$this->start_date = date('Y-m-d', strtotime('1 month ago'));
		$this->end_date = date('Y-m-d');
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
	
		// Work out the size for the container needed to hold the results, else we get results missed!
    $days = floor((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24)) + 2;
	
		$results = $this->_gapi->requestReportData($this->_config['report_id'], array('date'), array('visits'), NULL, NULL, $start_date, $end_date, 1, $days);
	
		$visits = array();
		foreach ($results as $r)
		{	
			$visits[$r->getDate()] = $r->getVisits();
		}
		
		ksort($visits);
	
		return $visits;
	}
	
	public function monthly_visit_count($start_date = FALSE, $end_date = FALSE)
	{
		if ( ! $start_date)
		{
			$start_date = date('Y-m-d', strtotime('first day of 6 months ago'));
		}
		
		if ( ! $end_date)
		{
			$end_date = date('Y-m-d', strtotime('last day of last month'));
		}
	
		// Work out the size for the container needed to hold the results, else we get results missed!
    $months = floor((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24 * 30)) + 2;
	
		$results = $this->_gapi->requestReportData($this->_config['report_id'], array('month'), array('visits'), array('-month'), NULL, $start_date, $end_date, 1, $months);
	
		$visits = array();
		foreach ($results as $r)
		{	
			if ($r->getVisits() > 0)
			{
				$visits[$r->getMonth()] = $r->getVisits();
			}
		}
	
		return $visits;
	}
	
	public function query($dimension, $metric, $sort = NULL, $max_results = NULL)
	{
		if ( ! is_null($sort))
		{
			$sort = array($sort);
		}
		
		$results = $this->_gapi->requestReportData($this->_config['report_id'], array($dimension), array($metric), $sort, NULL, $this->start_date, $this->end_date, 1, $max_results);
		
		$data = array();
		foreach ($results as $result)
		{
			$data[$result->{'get'.ucwords($dimension)}()] = $result->{'get'.ucwords($metric)}();
		}
		
		return $data;
	}
}