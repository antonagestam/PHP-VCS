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
	 *  - suggest command while writing in console
	 *  X Show the user the state of the ajax request
	 *  \ Migrate all repository methods to a library and keep the console methods here
	 *  	D Create handler for the PVCS core library
	 *  	- Create generic handler of libraries
	 *  		? Use call_user_func_array()?
	 *  X Create a data handler (cache?)
	 *  - Add sha1 and salt to pw
	 *  - Create create_user() method
	 *  ? Add support for database stored users
	 *  - Add support for aliases
	 *  - Migrate a lot of configuration to /application/config/console.php
	 *  ? Can parse_query() use call_user_func_array()?
	 *  - Fix utilize_aliases() method
	 *  - Hide password
	 *  \ Fix a real command parser
	 *  - Fix the javascript "parseerror"
	 *  	aka "An error occured during transfer: parsererror"
	 *  	that comes on query "pd"
	 *  - Remove the extra 'error!' on 'no such command or library'
	 */

	define('STATIC_DIRECTORY',getcwd());
	
	
	class Console extends Controller
	{
		private $version = "PVCS console, version 0.0.4-beta";
		private $allowed_commands = array(
				// The allowed commands is stored according to this pattern:
				// '_method name_' => array(_max paramaters_,_min parameters_),
				'help',
				'cd',
				'ls',
				'dir',
				'logout',
		);
		private $libraries = array(
			'pvcs_core',
		);
		private $out = "";
		private $data = array();
		//private $aliases = array(
		//	'pvcs' => 'pvcs_core',
		//);
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
			
			// Update current directory
			$this->update_current_dir();
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
			asort($methods);
			
			if( $command == NULL )
			{
				// Print version etc
				$this->print_ln($this->version);
				$this->print_ln("These are the shell commands");
				$this->print_ln();
				
				// Print allowed commands
				foreach($methods as $method)
				{
					$this->print_ln($method);
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
				
		private function ls()
		{
			$path = $this->get_data('dir');
			$files = glob($path.'/*');
			foreach($files as $file)
			{
				$this->print_ln(basename($file));
			}
		}
		
		private function cd($attr)
		{
			$new_dir = $attr['main'];
			
			// Go to the current directory
			chdir($this->get_data('dir'));
			
			// If it exists, go to the new directory
			if( @chdir($new_dir) === TRUE )
			{
				// Set new directory
				$this->set_data('dir',getcwd(),TRUE);
			}
			else
			{
				$this->print_ln('error: no such directory');
			}
			
			// Set defualt directory
			chdir(STATIC_DIRECTORY);
			
			// Update directory
			$this->update_current_dir();
		}
		
		private function update_current_dir()
		{
			$current_dir = $this->get_data('dir');
			if( $current_dir === FALSE || empty($current_dir))
			{
				$this->set_data('dir',getcwd(),TRUE);
			}
			
			$this->set_prompt();
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
						$this->print_ln('error: wrong password for `'.htmlentities($temp_user).'`');
						return $prompt;
					}
				}
			}
			elseif( !empty( $query ) && !empty( $user ) )
			{
				// Print the query together with the prompt
				$this->print_ln($this->get_data('default_prompt').' '.$query);
				$query = $this->extract_attributes($query);
				if( isset($query['library']) )
				{
					// Load the library
					$this->load->library($query['library']);
					// If set_dir() exists execute it
					if( method_exists($this->$query['library'],'set_dir') )
					{
						$dir = $this->get_data('dir');
						$this->$query['library']->set_dir($dir);
					}
					// If the command exists, execute it
					if( method_exists($this->$query['library'],$query['command']) )
					{
						$this->$query['library']->$query['command']($query['attributes']);
						
						// If get_output exists, add that to the console output
						if( method_exists($this->$query['library'],'get_output') )
						{
							$this->add_out( $this->$query['library']->get_output() );
						}
					}
					else
					{
						// Else, error
						$this->print_ln('error: command does not exist in that library');
					}
				}
				elseif( isset($query['command']) )
				{
					$this->$query['command']($query['attributes']);
				}
				else
				{
					$this->print_ln('error!');
				}
			}
		}
		
		private function extract_attributes($query)
		{
			$chunks = explode(' ',$query);
			
			if( method_exists($this,$chunks[0]) && in_array($chunks[0],$this->allowed_commands) )
			{
				$return['command'] = $chunks[0];
				unset($chunks[0]);
			}
			elseif( in_array($chunks[0],$this->libraries) )
			{
				$return['library'] = $chunks[0];
				if( isset($chunks[1]))
				{
					$return['command'] = $chunks[1];
					unset($chunks[0]);
					unset($chunks[1]);
				}
				else
				{
					$this->print_ln('error: your query had no command');
					return false;
				}
			}
			else
			{
				$this->print_ln('error: no such command or library');
				return false;
			}
			
			$return['attributes'] = array();
			$first = TRUE;
			
			foreach($chunks as $index => $chunk)
			{
				$pattern = '#^-(\w.*)#';
				$next = isset( $chunks[$index+1] ) ? $chunks[$index+1] : null;
				
				if( preg_match($pattern,$chunk) )
				{
					$name = substr($chunk,1);
					if( !empty($next) && !preg_match($pattern,$next) )
					{
						$return['attributes'][$name] = $next;
						unset($chunks[$index+1]);
					}
					else
					{
						$return['attributes'][$name] = TRUE;
					}
				}
				elseif( $first === TRUE )
				{
					$return['attributes']['main'] = $chunk;
				}
				else
				{
					continue;
				}
				
				$first = FALSE;
			}
			
			return $return;
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
		
		public function logout()
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
		
		private function utilize_aliases($string)
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
			// can I move the insertion of the data values to the session cookies to here?
			$this->print_ln('the end');
		}
	}