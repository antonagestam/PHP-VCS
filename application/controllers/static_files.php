<?php
	class Static_files extends Controller
	{
		public $static_directory = "static/";
		public $view = "raw";
		
		public function __construct()
		{
			parent::Controller();
			$this->index();
		}
		
		public function Index()
		{
			$filename = basename( $this->uri->segment(2) );
			$path = $this->static_directory . $filename;
			$content = read_file($path);
			$mime = get_mime_by_extension($path);
			$date = date('r',time()+(365*24*60*60));
			
			if($mime == 'text/css')
			{
				$content = $this->minify_css($content);
			}
						
			$data = array(
				'content' => $content,
			);
			
			$this->output->set_header('Content-type: '.$mime);
			// Turn off while developing css/js
			//$this->output->set_header('Expires: '.$date);
			$this->load->view($this->view,$data);
		}
		
		private function minify_css($css)
		{
			$css = preg_replace( '#\s+#', ' ', $css );
			$css = preg_replace( '#/\*.*?\*/#s', '', $css );
			$css = str_replace( '; ', ';', $css );
			$css = str_replace( ': ', ':', $css );
			$css = str_replace( ' {', '{', $css );
			$css = str_replace( '{ ', '{', $css );
			$css = str_replace( ', ', ',', $css );
			$css = str_replace( '} ', '}', $css );
			$css = str_replace( ';}', '}', $css );
		
			return trim( $css );
		}
	}