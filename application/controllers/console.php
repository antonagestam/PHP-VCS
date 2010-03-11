<?php
	/***
	 * Legend:
	 * - To be done
	 * \ Initiated
	 * X Done
	 * 
	 * 	TODO
	 *  - attribute handling
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
		private $allowed_commands = array(
				// The allowed commands is stored according to this pattern:
				// '_method name_' => array(_max paramaters_,_min parameters_),
				'help' => array(1,0),
				'clear' => array(0,0),
				'cd' => array(1,1),
				'ls' => array(1,0),
				'dir' => array(1,0),
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
			'dir',
			'temp_username',
			'default_prompt',
			'branch'
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
			
			// Set default branch to master
			$branch = $this->get_data('branch');
			if( empty($branch) || $branch === FALSE )
			{
				$this->set_data('branch','master',TRUE);
			}
			
			// Set default dir to root
			$dir = $this->get_data('dir');
			if( empty($dir) || $dir === FALSE )
			{
				$this->set_data('dir','/',TRUE);
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
				$data['prompt'] = $this->get_data('default_prompt');
			}
			
			$this->load->view($view,$data);
		}
		
		private function help($command=NULL)
		{
			// Get all allowed commands
			$methods = $this->allowed_commands;
			
			if( $command == NULL )
			{
				// Print version etc
				$this->print_ln($this->version);
				$this->print_ln("These are the shell commands");
				$this->print_ln();
				
				// Print allowed commands
				foreach($methods as $key => $method)
				{
					$this->print_ln($key);
				}
				
				$this->print_ln();
			}
			else
			{
				if( array_key_exists($command,$methods) && method_exists($this,$command) )
				{
					$this->print_ln('The `'.$command.'` command takes '.$methods[$command][0].' parameters');
				}
				else
				{
					$this->print_ln('error: command `'.$command.'` does not exist');
				}
			}
			
			return true;
		}
		
		// *************** OBSERVE *************************
		// the following to functions has to be considered!!
		private function print_ln($string="")
		{
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
		
		private function remove_data($index)
		{
			unset($this->data[$index]);
			$this->session->unset_userdata($index);
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
			$prompt = 'login as:'; // default prompt
			
			if( empty( $user ) && empty( $query ) )
			{
				// If the query is empty and no user session is active
				// return the default prompt
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
						$this->set_prompt();
						return $this->get_data('default_prompt');
					}
					else
					{
						$this->print_ln('error: wrong password for `'.htmlentities($query).'`');
						return $prompt;
					}
				}
			}
			elseif( !empty( $query ) && !empty( $user ) )
			{
				// Print the query
				$this->print_ln($this->get_data('default_prompt').' '.$query);
				
				// Fetch the the command (method) from the query
				preg_match('#^(\w+)[[ \w*]|$]#',$query,$matches);
				$command = $matches[0];
				$strlen = strlen($command);
				$command = trim($matches[0]);
				$attributes = explode(" ",substr($query,$strlen));
				
				// Get all allowed commands
				$commands = $this->allowed_commands;
				
				// Check if the method exists and the command is allowed
				if( array_key_exists($command,$commands) && method_exists($this,$command) )
				{
					if( count($attributes) > $commands[$command][0] )
					{
						$this->print_ln('error: wrong parameter count');
					}
					elseif( count($attributes) < $commands[$command][1] )
					{
						$this->print_ln('error: wrong parameter count');
					}
					else
					{
						if( count($attributes) == 1 )
						{
							$this->$command($attributes[0]);
						}
						elseif( count($attributes) < 1 )
						{
							$this->$command();
						}
						else
						{
							$this->$command($attributes);
						}
					}
				}
				else
				{
					$this->print_ln('error: command does not exist');
				}
			}
		}
		
		private function set_prompt($user=NULL,$branch=NULL,$dir=NULL)
		{
			if($user==NULL)
			{
				$user = $this->get_data('user');
			}
			if($branch==NULL)
			{
				$branch = $this->get_data('branch');
			}
			if($dir==NULL)
			{
				$dir = $this->get_data('dir');
			}
			
			$prompt = $user.'@'.$branch.': '.$dir.'$';
			
			$this->set_data('default_prompt',$prompt,TRUE);
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