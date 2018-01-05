<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller
{
	private $validation_errors = "";
	private $status = false;
	public function __construct()
	{
		parent::__construct();
	}
	public function identity_iq()
	{
		if (strtolower($_SERVER["REQUEST_METHOD"]) == 'post') {
			$this->load->library('form_validation');
			$this->form_validation->set_rules('passcode', "Pass Code", "required|callback_authenticate");
			if ($this->form_validation->run()) {

				if (!$_FILES['userfile']['error']) {
					$result = $this->do_upload();
					if (!array_key_exists('error', $result)) {
						$report_info = array("user_id" => 1001, "file_name" => $result["upload_data"]["file_name"]);
						$this->load->model("report_parser_model");
						$doc_id = $this->report_parser_model->save_report_file($report_info);
						if($doc_id){
							$this->status = $this->report_parser_model->start_report_parsing($doc_id);
						} else {
							$this->validation_errors = "Error: Unable to start document parsing. Failed to save report file.";
						}
					} else {
						$this->validation_errors = $result['error'];
					}	

				} else {
					$this->validation_errors = "Failed to upload file";
				} 
			}		
			
		}
		$data = array('error' => $this->validation_errors, 'parse_report' => $this->status);
		$this->load->view('header_tpl');
		$this->load->view('home_tpl', $data);
		$this->load->view('footer_tpl');
	}

	public function do_upload()
    {
        $config['upload_path']		= './uploads/reports/';
        $config['allowed_types']    = 'html';
        $config['max_size']         = 2000;
        $temp	= explode(".", $_FILES["userfile"]["name"]);
        $i = 0;
        do {
        	$config['file_name']	= round(microtime(true)) . '.' . end($temp);
        	
        } while(file_exists("./uploads/reports/" . $config['file_name']));

        $this->load->library('upload', $config);
        if ( ! $this->upload->do_upload('userfile'))
        {
            $error = array('error' => $this->upload->display_errors());
            //$this->load->view('upload_form', $error);
            return $error;
        }
        else
        {
            $data = array('upload_data' => $this->upload->data());
            //$this->load->view('upload_success', $data);
            return $data;
        }
    }

    public function authenticate($passcode)
    {
    	if($passcode == PASSCODE){
    		return true;
    	}
    	$this->form_validation->set_message('authenticate', 'Invalid passcode.');
    	return false;
    }

    public function test()
    {
    	echo "Hello World!";
    	die();
    }

    public function index()
    {  	
    	$this->load->view('header_tpl');
		$this->load->view('report_types');
		$this->load->view('footer_tpl');
    }
}
