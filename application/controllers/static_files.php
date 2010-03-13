<?php
	class Static_files extends Controller
	{
		public function __construct()
		{
			parent::Controller();
			$this->load->scaffolding('hej')
		}
		
		public function Index()
		{
			echo 'hej';
		}
	}