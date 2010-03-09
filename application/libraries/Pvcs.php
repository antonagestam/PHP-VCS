<?php
	class Pvcs
	{
		var $CI; // Instance of codeigniter stuff, kind of. Not sure if this will be necessary
		var $cd; // current directory
		
		public function __construct($dir=null)
		{
			$this->CI =& get_instance();
			$this->cd = $dir;
			
			log_message('debug','PVCS Class Initialized');
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
	}