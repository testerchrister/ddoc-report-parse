<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller
{
	private $validation_errors = "";
	private $status = false;
	private $doc_id = 0;
	public function __construct()
	{
		parent::__construct();
		$this->load->model('report_parser_model');
	}
	public function identity_iq()
	{
		$data = array('error' => $this->validation_errors, 'parse_report' => $this->status);
		$this->load->view('header_tpl');
		$this->load->view('home_tpl', $data);
		$this->load->view('footer_tpl');
	}

	public function doc_iiq_post()
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
							$this->doc_id = $doc_id;
							$message = "Document profile created successfully";
							$this->report_parser_model->set_progress($doc_id, $message);
						} else {
							$message = "Error: Failed to create document profile.";
							$this->report_parser_model->set_progress($doc_id, $message, 'error');
							$this->validation_errors = $message;
						}
					} else {
						$this->validation_errors = $result['error'];
						$message = "Error: Failed to upload file";
						$this->report_parser_model->set_progress($doc_id, $message, 'error');
					}	

				} else {
					$this->validation_errors = "Failed to upload file";
					$message = "Error: Failed to upload file";
					$this->report_parser_model->set_progress($doc_id, $message, 'error');
				} 
			}
			$status = $this->parse_progress_iiq(true);	
			echo $status;
		}
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

    public function iiq_init()
    {
    	if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
    		$doc_id = $this->input->post('doc_id');
    		if(!empty($doc_id) && is_numeric($doc_id)){
    			$this->current_doc['id'] = $doc_id;
    		} else {
    			echo json_encode(array('message'=>"Error: Failed to initialize document parsing.", 'progress'=>0));
    			return;
    		}
    		
    		$this->startParsing($doc_id);
    	}
    }

    private function startParsing($doc_id)
    {
    	$this->status = $this->report_parser_model->start_report_parsing($doc_id);
    }

    public function index()
    {  	
    	$this->load->view('header_tpl');
		$this->load->view('report_types');
		$this->load->view('footer_tpl');
    }

    public function parse_progress_iiq($internal = false)
    {
    	ob_implicit_flush(true);
		ob_end_flush();
    	if (strtolower($_SERVER['REQUEST_METHOD']) == 'post' || $internal) {
    		if($internal){
    			$doc_id = $this->doc_id;
    		} else {
    			$doc_id = $this->input->post('doc_id');
    		}
    		
    		$status = "";
    		if (isset($doc_id) && is_numeric($doc_id)) {
    			$status = json_encode($_SESSION['iiq'][$doc_id]);
    		} else {
    			$status = json_encode(array('status' => 'error', 'progress' => 'Unable to track parsing process'));
    		}
    		
    		if ($internal) {
    			return $status;
    		} else {
    			echo $status;
    		}

    	} else {
    		echo json_encode(array('status' => 'error', 'progress' => 'Unable to track parsing process'));
    	}
    }

    public function parse_progress()
    {
    	ob_implicit_flush(true);
		ob_end_flush();
    	if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
    		$doc_id = $this->input->post('doc_id');
    		if(isset($doc_id) && is_numeric($doc_id)){
    			echo json_encode($_SESSION['parser'][$doc_id]);
    		} else {
    			echo json_encode(array('status' => 'error', 'progress' => 'Unable to track parsing process'));
    		}
    		
    	} else {
    		echo json_encode(array('status' => 'error', 'progress' => 'Unable to track parsing process'));
    	}
    }

}
