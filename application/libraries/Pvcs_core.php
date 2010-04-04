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
	 *  - rename commits.json -- it's not json! commits.serialized?
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
		
		public function help($attr)
		{
			$command = empty($attr['main']) ? NULL : $attr['main'];
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
		
		public function status($attr)
		{
			if( isset($attr['dump']) )
			{
				$this->print_ln('Attempting to dump commits.json');
				$dir = $this->dir;
				$file = $this->arrget( $dir . '/_repo/commits/commits.json' );
				$this->print_r($file);
			}
		}
		
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
		
		private function arrput($file,$array)
		{
			$content = read_file($file);
			if(!empty($content))
			{
				$content = unserialize($content);
				$array = array_merge($content,$array);
			}
			$string = serialize($array);
			write_file($file,$string);
			return true;
		}
		
		private function arrget($file)
		{
			$content = read_file($file);
			return unserialize($content);
		}
		
		public function checkout(){}
		
		public function add(){}
		
		public function rm(){}
		
		public function branch(){}
		
		public function commit()
		{
			$repo_dir = $this->dir.'/_repo';
			if( !file_exists($repo_dir) )
			{
				$this->print_ln('error: could not find a repository');
			}
			else
			{
				$files = glob($this->dir.'/*');
				$commits = json_decode(read_file($repo_dir.'commits/commits.json'));
				$commitname = "";
				
				// remove repository files and create a commit name
				foreach($files as $index => $file)
				{
					if( preg_match('#_repo#',$file) )
					{
						unset($files[$index]);
						continue;
					}
					
					$commitname = sha1( $commitname . sha1_file($file) );
				}
				
				$this->print_ln('Created commit-name: '.$commitname);
				$this->log('Rendered commit name: '.$commitname);
				
				// if there is sumethin to commit
				foreach($files as $file)
				{
					$foldername = sha1($file);
					$folderpath = $repo_dir.'/parts/'.$foldername;
					if( !file_exists($folderpath) )
					{
						$this->mkdir($folderpath);
					}
					// add zip here
					// write the part-file
					$this->mk($folderpath.'/'.$commitname,read_file($file));
					// add the file to commits.json
					$commits = $repo_dir.'/commits/commits.json';
					$array = array();
					$array[$foldername] = $file;
					$this->arrput($commits,$array);
				}
				
				$this->print_ln('successfully commited');
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
		
		private function print_r($array)
		{
			ob_start();
			print_r($array);
			$ob = ob_get_contents();
			ob_end_clean();
			$this->print_ln('<pre>'.$ob.'</pre>');
		}
		
		public function set_dir($dir)
		{
			$this->dir = $dir;
		}
	}