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
	 *  - check if you are in a repository before commiting!
	 */
	class Pvcs_core
	{
		private $version = "PVCS core, version 0.0.1-alpha";
		private $CI; // Instance of codeigniter
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
			$file = $this->dir . '/_repo/log.txt';
			if( file_exists($file) )
			{
				$this->mk($file,"\n".$string);
			}
			else
			{
				$this->print_ln('error: cannot log here');
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
				
				// Try to create some files
				$this->mk($repository_path.'/commits/commits.json');
				$this->mk($repository_path.'/log.txt');
				
				$success = 'Successfully initiated repository';
				$this->log($success);
				$this->print_ln($success);
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
		
		private function mk($file,$contents=NULL)
		{
			if( @file_put_contents($file,$contents,FILE_APPEND) === FALSE )
			{
				$this->print_ln('error: mk failed');
			}
			else
			{
				$this->print_ln('mk succeeded');
			}
		}
		
		public function checkout(){}
		
		public function add(){}
		
		public function rm(){}
		
		public function branch(){}
		
		public function commit()
		{
			
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