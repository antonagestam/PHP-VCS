<?php
	/***
	 * 	TODO
	 *  X attribute handling
	 *  - suggest command while writing in console
	 *  - Show the user the state of the ajax request
	 *  \ Migrate all repository methods to a library and keep the console methods here
	 *  	- Create handler for the PVCS core library
	 *  - Create a data handler (cache?)
	 */
	
	
	class Console extends Controller
	{
		private $version = "PVCS console, version 0.0.1-alpha";
		private $allowed = array(
				'help',
				'clear',
				'cd',
				'ls',
				'dir',
			);
		private $out = "";
		private $data = array();
		private $dir = "/"; // defaults to root
		
		public function __construct()
		{
			parent::Controller();
			
			$this->load->library('pvcs');
			
			// block all computers but mine, while developing
			if( $this->input->server('REMOTE_ADDR') != '192.168.1.125' )
			{
				die('You lack authority');
			}
		}
		
		public function Index()
		{
			$query = htmlentities($this->input->post('query'));
			$split = explode(' ',$query);
			$command = $split[0];
			$attributes = "";
			$user = $this->session->userdata('user');
			$dir = $this->session->userdata('dir');
			$queryprint = $user['name'] . '@rep:' . $dir . '$ ';
			
			// get query minus command = attributes
			foreach($split as $index => $attr)
			{
				if($index>0)
				{
					$attributes .= $attr . "";
				}
			}
			// clean from blankspaces
			$attributes = trim($attributes);
			
			// check if there is a query
			if( !empty($query) )
			{
				// check if the command exists and is allowed
				if( method_exists($this,$command) && in_array( $command,$this->allowed ) )
				{
					// check if there are attributes
					if( !empty($attributes) )
					{
						// execute command
						$this->$command($attributes);
					}
					else
					{
						// execute command
						$this->$command();
					}
				}
				else
				{
					// tell user that the command doesn't exist
					$this->print_ln($command.': command not found');
				}
				
				// update som values
				$user = $this->session->userdata('user');
				$dir = $this->session->userdata('dir');
				$queryprint = $user['name'] . '@rep:' . $dir . '$ ';
				// print user
				$this->print_pre_ln($queryprint . $query);
			}
			
			$data = array(
				'message' => $this->out,
				'user' => $queryprint,
				'query' => $query,
			);
			
			$this->out($data);
		}
		
		private function out($data)
		{
			// check wether the call was made with ajax or not
			$xmlrequestedwith = $this->input->server('HTTP_X_REQUESTED_WITH');
			if( !empty($xmlrequestedwith) && strtolower($xmlrequestedwith) == 'xmlhttprequest' )
			{
				// If the call was made with ajax,
				// use clean view.
				$view = 'clean';
			}
			else
			{
				// If the call was not made with
				// ajax, use console view.
				$view = 'console';
			}
			
			$this->load->view($view,$data);
		}
		
		private function help()
		{
			// Print all allowed commands
			$methods = $this->allowed;
			sort($methods);
			
			$this->print_ln($this->version);
			$this->print_ln("These are the shell commands");
			$this->print_ln();
			
			foreach($methods as $method)
			{
				$this->print_ln($method);
			}
			
			$this->print_ln();
			
			return true;
		}
		
		// *************** OBSERVE *************************
		// the following to functions has to be considered!!
		private function print_ln($string="")
		{
			// Check if there are variables in the string
			$match = preg_match('#config\.(.+)? #',$string,$variables);
			if($match>0)
			{
				$this->print_ln('vars:'.$variables);
			}
			// Write string to output
			$this->out .= $string . "<br/>\n";
		}
		
		private function print_pre_ln($string)
		{
			// Prepend string to output
			$this->out = $string . "<br/>\n" . $this->out;
		}
		
		private function clear(){}
		
		private function ls()
		{
			$path = trim($this->session->userdata('dir'),'/').'/';
			$files = get_filenames($path);
			foreach($files as $file)
			{
				$this->print_ln(basename($file));
			}
		}
		
		private function cd($dir)
		{
			if( file_exists($dir) )
			{
				$this->session->set_userdata('dir',$dir);
			}
			else
			{
				$this->session->set_userdata('dir','false');
			}
		}
		
		private function dir()
		{
			$this->ls();
		}
	}