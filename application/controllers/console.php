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
		private $aliases = array(
			'pvcs' => 'pvcs_core',
			'vcs' => 'pvcs_core',
		);
		private $query; // current query
		
		public function __construct()
		{
			parent::Controller();
			
			$this->load->library('pvcs_core');
			
			// block all computers but mine, while developing
			if( $this->input->server('REMOTE_ADDR') != '192.168.1.125' )
			{
				die('You lack authority');
			}
		}
		
		public function Index()
		{
			$this->get_query();
			$this->parse_query();
			
			$data = array(
				'message' => $this->out,
				'user' => 'default',
				'query' => 'default',
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
			$this->add_out($string . "<br/>\n");
		}
		
		private function print_pre_ln($string)
		{
			// Prepend string to output
			$this->add_out($string . "<br/>\n" . $this->out);
		}
		
		private function add_out($string)
		{
			$this->out .= $string;
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
		
		private function set_data($index,$data)
		{
			$this->data[$index] = $data;
			return true;
		}
		
		private function get_data($index)
		{
			return $this->data[$index];
		}
		
		private function get_query()
		{
			$query = $this->input->post('query');
			$this->query = htmlentities($query);
		}
		
		private function parse_query()
		{
			if(){}
			$query = $this->query;
		}
		
		private function login()
		{
			// "login as:[]"
		}
	}