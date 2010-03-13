<?php
	/***
	 * Legend:
	 * - To be done
	 * \ Initiated
	 * X Done
	 * ? Question/goal
	 * D Deleted = not valid
	 * 
	 * 	TODO
	 *  - attribute handling
	 *  - suggest command while writing in console
	 *  - Show the user the state of the ajax request
	 *  \ Migrate all repository methods to a library and keep the console methods here
	 *  	D Create handler for the PVCS core library
	 *  	- Create generic handler of libraries
	 *  		? Use call_user_func_array()?
	 *  - Create a data handler (cache?)
	 *  - Add sha1 and salt to pw
	 *  - Create create_user() method
	 *  - Add support for database stored users
	 *  - Add support for aliases
	 *  - Migrate a lot of configuration to /application/config/console.php
	 *  ? Can parse_query() use call_user_func_array()?
	 *  - Fix utilize_aliases() method
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
				'pvcs' => array(1,1),
				'logout' => array(0,0),
			);
		private $out = "";
		private $data = array();
		private $dir = "/"; // defaults to root
		private $aliases = array(
			'pvcs' => 'pvcs_core',
			'vcs' => 'pvcs_core',
		);
		private $libraries = array(
			'pvcs_core',
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
		// the following two functions has to be considered!!
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
			$path = trim($this->get_data('dir'),'/').'/';
			$files = glob($path.'*');
			foreach($files as $file)
			{
				$this->print_ln(basename($file));
			}
		}
		
		private function cd($dir)
		{
			/*
			 * special chars ?
			 * .
			 * ..
			 * /
			 * ~ = favorite file? :)
			 */
			if( file_exists($dir) )
			{
				if( $dir[0] == '/' )
				{
					$this->set_data('dir',$dir,TRUE);
				}
				else
				{
					$getdir = $this->get_data('dir');
					if($getdir != FALSE)
					{
						$dir = $getdir.'/'.$dir;
					}
					$this->set_data('dir',$dir,TRUE);
				}
				
				$this->set_prompt(NULL,NULL,$dir);
			}
			else
			{
				$this->print_ln('error: no such file or directory');
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
				$attributes = explode( " ", trim(substr(trim($query),$strlen)) );
				
				if( count( $attributes ) == 1 && empty( $attributes[0] ) )
				{
					unset($attributes[0]);
				}
				
				// Get all allowed commands
				$commands = $this->allowed_commands;
				$libraries = $this->libraries;
				
				// Check if the method exists and the command is allowed
				if( array_key_exists($command,$commands) && method_exists($this,$command) )
				{
					$count = count($attributes);
					if( $count > $commands[$command][0] )
					{
						$this->print_ln('error: wrong parameter count[1]; '.$count);
					}
					elseif( $count < $commands[$command][1] )
					{
						$this->print_ln('error: wrong parameter count[2]'.$count);
					}
					else
					{
						if( $count == 1 )
						{
							$this->$command($attributes[0]);
						}
						elseif( $count < 1 )
						{
							$this->$command();
						}
						else
						{
							$this->$command($attributes);
						}
					}
				}
				elseif( in_array($command,$libraries) )
				{
					// Set $library to $command
					$library = $command;
					// Glue together the attributes
					$attr_str = trim( implode(" ",$attributes) );
					// Extract the command
					preg_match('#^(\w+)[[ \w*]|$]#',$attr_str,$matches);
					// Fetch command from matches
					$command = trim($matches[0]);
					
					// Data to pass to the library's constructor
					$config = array(
						'dir' => $this->get_data('dir')
					);
					
					// Load library
					$this->load->library($library,$config);
					
					// Check if the command exist
					if( method_exists( $this->$library, $command ) )
					{
						// Execute method
						$this->$library->$command();
						// Get the libraries output
						if( method_exists( $this->$library, 'get_output' ) )
						{
							$this->add_out($this->$library->get_output());
						}
						else
						{
							$this->print_ln('error: get_output is missing in library `'.$library.'`');
						}
					}
					else
					{
						$this->print_ln('error: command `'.$command.'`does not exist in library `'.$library.'`');
					}
				}
				else
				{
					$this->print_ln('error: no such command or library');
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
		
		private function logout()
		{
			$sessiondata = $this->sessiondata;
			foreach($sessiondata as $variable)
			{
				if( $this->get_data($variable) != FALSE )
				{
					$this->session->unset_userdata($variable);
				}
			}
			$this->print_ln('Logged out');
		}
		
		private function salt_password($password)
		{
			return $password;
		}
		
		private function Utilize_aliases($string)
		{
			if( !empty($string) )
			{
				$aliases = $this->aliases;
				foreach( $aliases as $needle => $replace )
				{
					$string = str_replace($needle,$replace,$string);
				}
			}
			
			return $string;
		}
		
		public function __destruct()
		{
			// can we move the insertion of the data values to the session
			// cookies to here?
			// might be a bad idea ...
			$this->print_ln('the end');
		}
	}