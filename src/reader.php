<?php
namespace AlexBrandes\SimpleCsv;

/**
 * CSV file parser for simple and flexible csv parsing
 **/
class Reader
{	
	/**
	 * @var  string  Switch to determine what kind of data to use
	 **/
	public $data_format;

	/**
	 * @var  string  Location of source file
	 **/
	public $file_source;

	/**
	 * @var  string  Raw string data
	 **/
	public $string_data;

	/**
	 * @var  string  Escape character
	 **/
	public $escape = '"';

	/**
	 * @var  string  Delimiter character
	 **/
	public $delimiter = ',';

	/**
	 * Set any options you want here
	 * 
	 * @param   array  options
	 **/
	public function __construct($opts)
	{
		foreach( $opts as $k=>$v )
		{
			// call set methods if they exist
			if( method_exists($this, 'set_'.$k) )
			{
				call_user_func_array(array($this, 'set_'.$k), array($v));
			}
		}
	}

	/**
	 * Chainable shortcut for __construct
	 * 
	 * @param   array  options
	 * @return  object
	 **/
	public static function forge($opts=array())
	{
		return new self($opts);
	}

	/**
	 * Chainable shortcut for string init
	 * 
	 * @param   string  string data
	 * @param   array   options
	 * @return  object
	 **/
	public static function create_from_string($string, $opts=array())
	{
		return self::forge($opts)->from_string($string);
	}

	/**
	 * Set csv data from a string
	 * 
	 * @param   string  string data
	 * @return  object
	 **/
	public function from_string($string)
	{
		$this->data_format = 'string';
		$this->string_data = $string;

		return $this;
	}

	/**
	 * Chainable shortcut for file init
	 * 
	 * @param   string  file location
	 * @param   array   options
	 * @return  object
	 **/
	public static function create_from_file($file_loc, $opts=array())
	{
		return self::forge($opts)->from_file($file_loc);
	}

	/**
	 * Set csv data from a file
	 * 
	 * @param   string  file location
	 * @return  object
	 **/
	public function from_file($file_loc)
	{
		if( ! file_exists($file_loc) )
		{
			throw new \FileNotFoundException('Csv file location not found.');
		}

		$this->data_format = 'file';
		$this->file_source = $file_loc;
		$this->string_data = file_get_contents($file_loc);

		return $this;
	}
	
	/**
	 * Choose the escape character
	 * 
	 * @param   string  escape character
	 * @return  object
	 **/
	public function set_escape($esc)
	{
		$this->escape = $esc;

		return $this;
	}

	/**
	 * Choose the delimiter character
	 * 
	 * @param   string  delimiter character
	 * @return  object
	 **/
	public function set_delimiter($delim)
	{
		$this->delimiter = $delim;

		return $this;
	}

	/**
	 * Detect and set delimiter (fairly accurate guess)
	 * 
	 * @param   array   additional characters to check, default is ',' + ';'
	 * @return  object
	 **/
	public function detect_delimiter($additional=null)
	{
		$tests = array(',', ';');
		if( $additional )
		{
			$tests += $additional;
		}

		$lines = $this->get_lines(10, 0);

		// delimiters w/ more fields that fit the pattern most likely to be correct
		$total_fields = array();
		foreach( $tests as $test )
		{
			$this->delimiter = $test;
			foreach( $lines as $line )
			{
				$fields = $this->_parse_line($line);

				if( ! isset($total_fields[$test]) ) $total_fields[$test] = 0;

				$total_fields[$test] += count($fields);
			}
		}
		
		asort($total_fields);
		end($total_fields);

		$this->delimiter = key($total_fields);

		return $this;
	}

	/**
	 * Returns the first n lines
	* 
	 * @param   int  	number of lines to get
	 * @param   int     offset
	 * @return  object
	 **/
	public function get_lines($n, $offset=0)
	{
		$lines = array();
		
		// file
		if( $this->data_format == 'file' )
		{
			$handle = fopen($this->file_source, 'r');

			$ret = array();
			for( $i=0; ($line = fgets($handle, 1000) and $i < 10) !== false; $i++ ) {
			    $lines[] = $line;
			}

			fclose($handle);
		}
		// string 
		else
		{
			$lines = $this->_split_on_lines();
		}

		$ret = array();
		for( $i = $offset; $i < $n + $offset; $i++ )
		{
			$ret[] = $lines[$i];
		}

		return $ret;
	}

	/**
	 * Parses string data into an indexed array
	 *
	 * @return  array  indexed array
	 **/
	public function to_array()
	{
		if( $this->data_format == 'file' )
		{
			return $this->_lines_from_file();
		}

		$lines = $this->_split_on_lines();


		$arr = array();
		$headers = array();
		foreach( $lines as $line )
		{
			// throw away empty lines
			$trimmed = trim($line);
			if( empty($trimmed) ) continue;

			// parse out the fields
			$fields = $this->_parse_line($line);

			// returned some garbage, throw away
			if( ! count($fields) ) continue;
			
			$arr[] = $fields;
		}

		return $arr;
	}

	/**
	 * Parses string data into an associative array of headers/values
	 *
	 * @return  array  associative array
	 **/
	public function to_assoc()
	{

		$lines = $this->data_format == 'file' ? $this->lines_from_file() : $this->_split_on_lines();

		$i=0;
		$assoc = array();
		$headers = array();
		foreach( $lines as $line )
		{
			if( $this->data_format == 'string' )
			{
				// throw away empty lines
				$trimmed = trim($line);
				if( empty($trimmed) ) continue;

				// parse out the fields
				$fields = $this->_parse_line($line);
			}

			// returned some garbage, throw away
			if( ! count($fields) ) continue;

			// grab headers
			if( $i == 0 ) 
			{
				$headers = $fields;
				$i++;
				continue;
			} 

			// can't combine, invalid csv
			if( count($fields) != count($headers) ) 
			{
				throw new \Exception('Invalid CSV format.');
			}
			
			$assoc[] = array_combine($headers, $fields);
		}

		return $assoc;
	}

	/**
	 * Reads lines from the set file
	 *
	 * @param   int    number of lines to get
	 * @param   int    offset
	 * @return  array  
	 **/
	protected function _lines_from_file($lines=99999999999, $offset=0)
	{
		$handle = fopen($this->file_source, 'r');

		$ret = array();
		for( $i=$offset; ($line = fgetcsv($handle, 1000, ",")) and $i < $lines+$offset !== false; $i++ ) {
		    $ret[] = $line;
		}

		fclose($handle);

		return $ret;
	}

	/**
	 * Regex to pull lines out of string
	 *
	 * @return  array  array of csv lines (as strings)
	 **/
	protected function _split_on_lines()
	{
		$esc = $this->escape;

		// first we need to split on line endings
		//^([^\n\r"]*("[^"]*"[^"\n\r]*)*)(\r\n?|\n?|$)
		preg_match_all('/([^'.$esc.'\r\n]*('.$esc.'[^'.$esc.']*'.$esc.'[^'.$esc.'\r\n]*)*)[\r\n?|\n|$]/', $this->string_data, $matches);
		
		return $matches[0];
	}

	/**
	 * Regex to pull fields out of a single line
	 * 
	 * @param   string  single line 
	 * @return  array   indexed array
	 **/
	protected function _parse_line($line)
	{
		$delim = $this->delimiter;
		$esc = $this->escape;

		// match everything that starts with escape char or not, 
		// that has an even number (or zero) escape chars 
		preg_match_all('/[^'.$delim.$esc.']*('.$esc.'[^'.$esc.']*'.$esc.'[^'.$esc.$delim.']*)*('.$delim.'|$)/', $line, $field_matches);

		$fields = $field_matches[0];

		// returns an empty value as an extra field at the end, get rid of it
		array_pop($fields);

		// clean up
		array_walk($fields, function(&$val) use ($esc, $delim) {
			$val = trim(rtrim(rtrim($val, $delim)), $esc);

			// get rid of escape characters
			$val = str_replace($esc.$esc, $esc, $val);
		});

		return $fields;
	}
}