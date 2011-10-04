<?php
	class std_out{
		private $out;
		
		public function add_out($string){
			$this->out .= $string;
		}
		
		public function clear_out(){
			$this->out = "";
		}
		
		public function set_out($string){
			$this->clear_out();
			$this->add_out($string);
		}
		
		public function get_output(){
			return $this->out;
		}
		
		public function print_ln($string=NULL){
			$this->add_out($string."<br/>\n");
		}
		
		public function print_r($array)
		{
			ob_start();
			print_r($array);
			$ob = ob_get_contents();
			ob_end_clean();
			$this->print_ln('<pre>'.$ob.'</pre>');
		}
	}