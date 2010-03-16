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
			$file = $this->dir . '/_repo/log.txt';
			$this->mk($file,"\n".$string);
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
				$this->mk($repository_path.'/commits/commits.txt');
				
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
			// Get all files in the repository
			$files = get_filenames($this->dir,TRUE);
			
			// This is what will be removed
			$remove = $this->dir.'/';
			
			$all_files = "";
			
			foreach( $files as $index => $file )
			{
				$file = preg_replace('#^'.$remove.'#','',$file);
				$all_files .= file_get_contents($file);
				$files[$index] = $file;
			}
			
			$commit_name = sha1($all_files);
			
			// Loop through the files
			foreach( $files as $file )
			{
				// Delete dat shit
				$nfile = preg_replace('#^'.$remove.'#','',$file);
				
				// Make sure the _repo files are not archived
				if( preg_match('#^_repo/#',$nfile) )
				{
					continue;
				}
				
				$part_file_name = sha1($nfile);
				
				$this->print_ln($nfile . ': created part-file; '.$part_file_name);
			}
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