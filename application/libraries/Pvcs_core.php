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
	 *  - Migrate a lot of configuration to /application/config/pvcs_core.php
	 */
	class Pvcs_core
	{
		private $version = "PVCS core, version 0.0.1-alpha";
		private $CI; // Instance of codeigniter stuff, kind of. Not sure if this will be necessary
		private $dir; // current directory
		public $allowed_commands = array();
		private $out;
		
		public function __construct()
		{
			// enables me to use the codeigniter stuff in this class
			$this->CI =& get_instance();
			
			//define allowed commands
			$this->allowed_commands = array(
				'help' => array(1,0),
				'init' => array(0,0),
				'commit' => array(0,0),
				'status' => array(0,0),
				'rm' => array(0,0),
				'add' => array(0,0),
				'branch' => array(0,0),
				'checkout' => array(0,0),
			);
		}
		
		public function help($command=NULL)
		{
			$methods = $this->allowed_commands;
			
			if( $command == NULL )
			{
				$version = $this->version;
				
				$this->print_ln($version);
				$this->print_ln('These are the methods of the PVCS Core library');
				$this->print_ln();
				foreach($methods as $method => $data)
				{
					$this->print_ln($method);
				}
				$this->print_ln();
			}
			elseif( method_exists($this,$command) && array_key_exists($command,$methods) )
			{
				print_ln('*method specific help*');
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
			$repository_path = $this->dir . '/_repo';
			// Check if there is already an installed repository in current directory
			if( is_dir( $repository_path ) )
			{
				$this->print_ln('error: there is already a repository in current directory');
			}
			else
			{
				// Try to create some folders
				$this->mkdir($repository_path);
				$this->mkdir($repository_path.'/parts');
				$this->mkdir($repository_path.'/commits');
			}
		}
		
		private function mkdir($path)
		{
			if( mkdir($path) )
			{
				$this->print_ln('mkdir succeeded');
			}
			else
			{
				$this->print_ln('error: mkdir failed');
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
		
		private function print_ln($string=NULL)
		{
			$this->add_out($string."<br/>\n");
		}
		
		public function set_dir($dir)
		{
			$this->dir = $dir;
		}
	}