<?php
	class std_out{
		private $out;
		
		private function add_out($string){
			$this->out .= $string;
		}
		
		public function get_output(){
			return $this->out;
		}
		
		public function print_ln($string=NULL){
			$this->add_out($string."<br/>\n");
		}
	}