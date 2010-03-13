<?php
	/***
	 * TODO
	 *  - commit method
	 *  - add method
	 *  - rm method
	 *  - branch method
	 *  - checkout method
	 *  - config method
	 *  - cd method
	 *  - ls method
	 *  - help method
	 */
	class Pvcs_core
	{
		private $version = "PVCS core, version 0.0.1-alpha";
		private $CI; // Instance of codeigniter stuff, kind of. Not sure if this will be necessary
		private $cd; // current directory
		private $allowed_commands = array();
		private $out;
		
		public function __construct()
		{
			// enables me to use the codeigniter stuff in this class
			$this->CI =& get_instance();
			
			//define allowed commands
			$this->allowed_commands = array(
				'help' => array(1,0),
				'init' => array(0,0),
			);
		}
		
		public function help($command=NULL)
		{
			$methods = $this->allowed_commands;
			foreach($methods as $method => $data)
			{
				$this->print_ln($method);
			}
		}
		
		// Write to the logfile in the repository
		public function log($string)
		{
			$dir = trim($this->session->userdata('dir'),'/').'/'.$this->repdirname;
			if( file_exists($dir) && is_dir($dir) )
			{
				$message = date('Y-m-d H:i:s')." - ".$string."\n";
				write_file($dir.'/log.txt',$message,'a');
				return true;
			}
			else
			{
				$this->print_ln('Error: There is no repository in this directory');
				return false;
			}
		}
		
		public function status(){}
		
		// Create a repository
		public function init()
		{
			$dir = trim($this->session->userdata('dir'),'/').'/';
			$path = $dir . $this->repdirname;
			if( file_exists( $path ) && is_dir($path) )
			{
				$this->print_ln('Error: There is already a repository in this directory');
				return false;
			}
			else
			{
				mkdir($path);
				$message = "Repository initiated";
				$this->log($message);
				$this->print_ln($message);
				return true;
			}
		}
		
		public function checkout(){}
		
		public function add(){}
		
		public function rm(){}
		
		public function branch(){}
		
		public function commit()
		{
			$this->print_ln('This method is not yet done');
		}
		
		// Output methods
		private function add_out($string)
		{
			$this->out .= $string;
		}
		
		public function get_output()
		{
			return $this->out;
		}
		
		private function print_ln($string)
		{
			$this->add_out($string."<br/>\n");
		}
	}