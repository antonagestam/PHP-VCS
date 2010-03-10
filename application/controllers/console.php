<?php
	/***
	 * 	TODO
	 *  X attribute handling
	 *  - suggest command while writing in console
	 *  - Show the user the state of the ajax request
	 *  \ Migrate all repository methods to a library and keep the console methods here
	 *  	- Create handler for the PVCS core library
	 *  - Create a data handler (cache?)
	 *  - Add sha1 and salt to pw, "create user" method
	 *  - Add support for database stored users
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
		private $sessiondata = array(
			'user',
			'dir'
		);
		
		public function __construct()
		{
			parent::Controller();
			
			// Load the core library
			$this->load->library('pvcs_core');
			
			// block all computers but mine, while developing
			if( $this->input->server('REMOTE_ADDR') != '192.168.1.125' )
			{
				die('You lack authority');
			}
			
			// Fetch sessiondata to data cache
			foreach($this->sessiondata as $index)
			{
				$value = $this->session->userdata($index);
				if( $value !== FALSE )
				{
					$this->set_data($index,$value);
				}
			}
		}
		
		public function Index()
		{
			$this->get_query();
			$prompt = $this->parse_query();
			$this->out($prompt);
		}
		
		private function out($prompt=NULL)
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
			
			$data['out'] = $this->out;
			if($prompt!==NULL)
			{
				$data['prompt'] = $prompt;
			}
			else
			{
				$data['prompt'] = 'def.prompt';
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
		
		private function set_data($index,$data,$session=FALSE)
		{
			if( $session===TRUE )
			{
				$this->session->set_userdata($index,$data);
			}
			$this->data[$index] = $data;
			return true;
		}
		
		private function get_data($index)
		{
			if( isset($this->data[$index]) )
			{
				return $this->data[$index];
			}
			else
			{
				return false;
			}
		}
		
		private function get_query()
		{
			$query = $this->input->post('query');
			$this->query = htmlentities($query);
		}
		
		private function parse_query()
		{
			$query = $this->query;
			$user = $this->get_data('user');
			$temp_user = $this->get_data('temp_username');
			$prompt = 'login as:';
			
			if( empty( $user ) && empty( $query ) )
			{
				return $prompt;
			}
			elseif( !empty( $query ) && empty( $user ) )
			{
				if( empty($temp_user) || $temp_user === FALSE )
				{
					$status = $this->check_username($query);
					if($status === TRUE)
					{
						return 'password:';
					}
					else
					{
						$this->print_ln('error: wrong username');
						return $prompt;
					}
				}
				else
				{
					$status = $this->check_password($query);
					if($status === TRUE)
					{
						return 'welcome'; // user @ dir : branch $ ?!?!
					}
					else
					{
						$this->print_ln('error: wrong password for `'.htmlentities($query).'`');
						return $prompt;
					}
				}
			}
		}
		
		private function check_username($username)
		{
			$this->config->load('pvcs_users');
			$users = $this->config->item('users');
			
			if( array_key_exists( $username, $users ) )
			{
				$this->set_data('temp_username',$username,TRUE);
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		
		private function check_password($password)
		{
			$this->config->load('pvcs_users');
			$users = $this->config->item('users');
			$username = $this->get_data('temp_username');
			$this->remove_data('temp_username');
			$password = $this->salt_password($password);
			
			if( $users[$username] == $password )
			{
				$this->set_data('user',$username,TRUE);
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		
		private function salt_password($password)
		{
			return $password;
		}
		
		public function __destruct()
		{
			// can we move the insertion of the data values to the session
			// cookies to here?
			// might be a bad idea ...
		}
	}