<?php
	class utils extends std_out{
		private $ci;
		
		public function wget($attr){
			$url = isset($attr['url'])?$attr['url']:@$attr['main'];
			
			if(empty($url)){
				$this->print_ln("error: A URL is required by this command");
				return;
			}
			
			if($data = @file_get_contents($url)){
				if(isset($attr['print'])){
					$this->set_out($data);
				}else{
					$path = pathinfo($url);
					$filename = $path['filename'].$path['extension'];
					$this->print_ln($filename);
				}
			}else{
				$this->print_ln("error: Could not fetch data from URL");
			}
		}
	}