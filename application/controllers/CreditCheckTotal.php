<?php
ini_set('max_execution_time', 0);
defined('BASEPATH') OR exit('No direct script access allowed');

class Creditchecktotal extends CI_Controller
{
	private $validation_errors = "";
	private $status = false;
	private $report_type_info;
	private $current_doc;
	private $response_messages;
	public function __construct()
	{
		parent::__construct();
		$this->load->model("report_model");		
	}
	public function index()
	{
		
		$data = array('error' => $this->validation_errors, 'parse_report' => $this->response_messages);
		$this->load->view('header_tpl');
		$this->load->view('credit_check_total_tpl', $data);
		$this->load->view('footer_tpl');
	}

	public function ajax_post() 
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
						$this->report_type_info = $this->report_model->get_report_type_info(CCT_NAME);
						$report_info['report_type'] = $this->report_type_info->id;
						$_SESSION['progress'] = array('cur_process' => 'Creating document profile');
						$doc_id = $this->report_parser_model->save_report_file($report_info);
						if($doc_id){
							
							$this->current_doc['id'] = $doc_id;
							$_SESSION['parser'][$doc_id] = array();
							$_SESSION['parser'][$doc_id]['doc_id'] = $doc_id;
							$_SESSION['parser'][$doc_id]['progress'] = 'Document profile created successfully';
							$_SESSION['parser'][$doc_id]['status'] = 'success';
							$response = json_encode($_SESSION['parser'][$doc_id]);
							echo $response;
							return;
							//$this->startParsing();							
							
						} else {
							$this->validation_errors = "Error: Unable to start document parsing. Failed to save report file.";
							echo "error";
							return;
						}

					} else {
						$this->validation_errors = $result['error'];
					}	

				} else {
					$this->validation_errors = "Failed to upload file";
				} 
			}		
			
		}
	}
	public function do_upload()
    {
        $config['upload_path']		= './uploads/cct-reports/';
        $config['allowed_types']    = 'pdf';
        $config['max_size']         = 50000;
        $temp	= explode(".", $_FILES["userfile"]["name"]);
        $i = 0;
        do {
        	$config['file_name']	= round(microtime(true)) . '.' . end($temp);
        	
        } while(file_exists("./uploads/cct-reports/" . $config['file_name']));

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

    public function cct_init()
    {
    	if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
    		$doc_id = $this->input->post('doc_id');
    		if(!empty($doc_id) && is_numeric($doc_id)){
    			$this->current_doc['id'] = $doc_id;
    		} else {
    			echo json_encode(array('message'=>"Error: Failed to initialize document parsing.", 'progress'=>0));
    			return;
    		}
    		
    		$this->startParsing();
    	}
    }
    private function startParsing()
    {	
    	if(isset($this->current_doc['id']) && is_numeric($this->current_doc['id'])) {
    		$doc_id = $this->current_doc['id'];
    	} else {
    		$message = "Invalid document ID";
    		$this->set_progress(0, $message, "error");
    		echo json_encode($_SESSION['parser'][$doc_id]);
    		log_message('error', $message);
    		return false;
    	}
    	$this->load->library('credit_check_total');
    	$this->load->model('cct_report_parser_model');
    	$file_name = $this->cct_report_parser_model->getDocFileName($doc_id);
    	if ($file_name) {
    		$file_name = FCPATH."uploads".DIRECTORY_SEPARATOR."cct-reports".DIRECTORY_SEPARATOR.$file_name;
    	} else {
    		$message = "Unable to fetch uploaded file name";
    		$this->set_progress($doc_id, $message, "error");
    		echo json_encode($_SESSION['parser'][$doc_id]);
    		log_message("error", $message);
    		return false;
    	}
    	
    	$result = $this->credit_check_total->init($file_name);
    	if ($result) {
    		$message = "Started Basic Information parsing.";    		
    		$this->set_progress($doc_id, $message);
    		$basic_info = $this->credit_check_total->getBasicInfo();    		

    		if (isset($basic_info) && is_array($basic_info) && count($basic_info)) {
    			$message = "Basic Information parsed successfully!";
    			$this->response_messages[] = array('status' => 'success', 'message' => $message);
    			$this->set_progress($doc_id, $message);    			
				$message = "Saving Basic Information..";
    			$this->set_progress($doc_id, $message);
  				$status = "success";
    			$result = $this->cct_report_parser_model->saveBasicInfo($doc_id, $basic_info);
    			if ($result) {
    				$message = "Basic Information saved successfully!";
    				$this->response_messages[] = array('status' => 'success', 'message' => $message); 				
    			} else {
    				$message = "Failed to save Basic Information";
    				$status = "error";
    				$this->response_messages[] = array('status' => $status, 'message' => $message);
    			}

    			$this->set_progress($doc_id, $message, $status);
    		} else {
    			$status = "error";
    			$message = "Failed to parse basic information";
    			log_message($status, $message);
    			$this->response_messages[] = array('status' => $status, 'message' => $message);
    			$this->set_progress($doc_id, $message, $status);
    		}
    		$message = "Started document index page parsing";
    		$this->set_progress($doc_id, $message);
    		$info_types = $this->credit_check_total->getPageInfoType();

    		if (is_array($info_types)  && count($info_types)) {	
    			$message = "Document index page parsed successfully!";
    			$this->set_progress($doc_id, $message);    			
    			$this->parseDocumentSections($info_types);
    		} else {
    			$message = "Unable to document pages information. Document parsing aborting.";
    			log_message("error", $message);
    			$this->response_messages[] = array('status' => 'error', 'message' => $message);
    			$this->set_progress($doc_id, $message);
    			return; 
    		}

    	} else {
    		$status = 'error';
    		$message = "Failed to initialize Document parser";
    		$this->response_messages[] = array('status' => $status, 'message' => $message);
    		$this->set_progress($doc_id, $message, $status);
    		echo json_encode($_SESSION['parser'][$doc_id]);
    		return;
    	}

    	return true;
    }

    private function parseDocumentSections($info_types)
    {

    	if(isset($this->current_doc['id']) && is_numeric($this->current_doc['id'])) {
    		$doc_id = $this->current_doc['id'];
    	} else {
    		$message = "Invalid document ID";
    		$this->set_progress(0, $message); 
    		log_message('error', $message);
    		return false;
    	}
    	foreach ($info_types as $page_info) {
    		if (isset($page_info['title']) && preg_match('/Personal Information/', $page_info['title'])) {   			
				if(isset($page_info['page_start']) && !empty($page_info['page_start']) && isset($page_info['page_end']) && !empty($page_info['page_end'])) {
					$message = "Started personal information parsing."	;
					$this->set_progress($doc_id, $message); 
					$personal_info = $this->credit_check_total->getPersonalInformation($page_info['page_start'], $page_info['page_end']);
					file_put_contents("temp_file.txt", print_r($personal_info, 1));
					$p_info = array();
					if (isset($personal_info) && is_array($personal_info) && count($personal_info)) {
						if (isset($personal_info['experian']) && count($personal_info['experian'])) {
							$tmp_info['bureau'] = 'experian';							
							$tmp_info['name'] = isset($personal_info['experian']['name']) ? $personal_info['experian']['name'] : "";
							$tmp_info['year_of_birth'] = isset($personal_info['experian']['year_of_birth']) ? $personal_info['experian']['year_of_birth'] : "";
							$tmp_info['addresses'] = isset($personal_info['experian']['addresses']) ? $personal_info['experian']['addresses'] : "";
							$tmp_info['current_employer'] = isset($personal_info['experian']['current_employer']) ? $personal_info['experian']['current_employer'] : "";
							$tmp_info['previous_employer'] = isset($personal_info['experian']['previous_employer']) ? $personal_info['experian']['previous_employer'] : "";
							$p_info[] = $tmp_info;
						}

						if (isset($personal_info['equifax']) && count($personal_info['equifax'])) {
							$tmp_info['bureau'] = 'equifax';							
							$tmp_info['name'] = isset($personal_info['equifax']['name']) ? $personal_info['equifax']['name'] : "";
							$tmp_info['year_of_birth'] = isset($personal_info['equifax']['year_of_birth']) ? $personal_info['equifax']['year_of_birth'] : "";
							$tmp_info['addresses'] = isset($personal_info['equifax']['addresses']) ? $personal_info['equifax']['addresses'] : "";
							$tmp_info['current_employer'] = isset($personal_info['equifax']['current_employer']) ? $personal_info['equifax']['current_employer'] : "";
							$tmp_info['previous_employer'] = isset($personal_info['equifax']['previous_employer']) ? $personal_info['equifax']['previous_employer'] : "";
							$p_info[] = $tmp_info;
						}

						if (isset($personal_info['transunion']) && count($personal_info['transunion'])) {
							$tmp_info['bureau'] = 'transunion';							
							$tmp_info['name'] = isset($personal_info['transunion']['name']) ? $personal_info['transunion']['name'] : "";
							$tmp_info['year_of_birth'] = isset($personal_info['transunion']['year_of_birth']) ? $personal_info['transunion']['year_of_birth'] : "";
							$tmp_info['addresses'] = isset($personal_info['transunion']['addresses']) ? $personal_info['transunion']['addresses'] : "";
							$tmp_info['current_employer'] = isset($personal_info['transunion']['current_employer']) ? $personal_info['transunion']['current_employer'] : "";
							$tmp_info['previous_employer'] = isset($personal_info['transunion']['previous_employer']) ? $personal_info['transunion']['previous_employer'] : "";
							$p_info[] = $tmp_info;
						}

						$result = $this->cct_report_parser_model->savePersonalInfo($doc_id, $p_info);
		    			if ($result) {
		    				$message = "Personal Information saved successfully!"	;
							$this->set_progress($doc_id, $message);
		    				$this->response_messages[] = array('status' => 'success', 'message' => $message);
		    				
		    			} else {
		    				$message = "Failed to save Personal Information";
							$this->set_progress($doc_id, $message, 'error');
		    				$this->response_messages[] = array('status' => 'error', 'message' => $message);
		    				return false;
		    			}
					}
				} else {
					$message = "Unable to find Personal Information section start and end pages";
					log_message('error', $message);
					$this->set_progress($doc_id, $message, 'error');
				}

			}
		
			//Report Summary
			if (isset($page_info['title']) && preg_match('/Report Summary/', $page_info['title'])) {
				if(isset($page_info['page_start']) && !empty($page_info['page_start']) && isset($page_info['page_end']) && !empty($page_info['page_end'])) {
					$message = "Report Summary parsing started";
					$this->set_progress($doc_id, $message);
					$report_summary = $this->credit_check_total->getReportSummary($page_info['page_start'], $page_info['page_end']);
					if(isset($report_summary) && is_array($report_summary) && count($report_summary)) {
						$message = "Saving Report Summary..";
						$this->set_progress($doc_id, $message);
						$tmp_count = 0;
						foreach ($report_summary as $summary) {
							$result = $this->cct_report_parser_model->saveReportSummary($doc_id, $summary);
							if ($result) {
								$tmp_count++;
			    				$this->response_messages[] = array('status' => 'success', 'message' => "Report Summary saved successfully!");
			    				
			    			} else {
			    				$this->response_messages[] = array('status' => 'error', 'message' => "Failed to save Report Summary");
			    			}

						}
						if($tmp_count == count($report_summary)) {
							$message = "Saved all Report Summary successfully!";							
						} else {
							$message = "Saved ".$tmp_count."/" .count($report_summary)." Report Summary successfully!";
						}
						
						$this->set_progress($doc_id, $message);
					} else {
						$message = "Report Summary parsing failed";
						$this->set_progress($doc_id, $message, 'error');
					}				
				} else {
					$message = "Report Summary - Unable to figure out the start and end pages in the document";
					$this->set_progress($doc_id, $message, 'error');
					log_message("error", $message);
				}
			}
			
			//Credit Inquiries
			if (isset($page_info['title']) && preg_match('/Credit Inquiries/', $page_info['title'])) {
				if(isset($page_info['page_start']) && !empty($page_info['page_start']) && isset($page_info['page_end']) && !empty($page_info['page_end'])) {
					$message = "Start parsing Credit Inquiries section..";
					$this->set_progress($doc_id, $message);
					$credit_inquiries = $this->credit_check_total->getCreditInquiries($page_info['page_start'], $page_info['page_end']);
					if ($credit_inquiries && is_array($credit_inquiries) && count($credit_inquiries)) {
						$message = "Saving Credit Inquiries section..";
						$this->set_progress($doc_id, $message);
						foreach ($credit_inquiries as $ci_info) {
							$result = $this->cct_report_parser_model->saveCreditInquiries($doc_id, $ci_info);
							if ($result) {
								$this->response_messages[] = array('status' => 'success', 'message' => "Credit Inquiries saved successfully!");
							} else {
								$this->response_messages[] = array('status' => 'error', 'message' => "Failed to save Credit Inquiries");
							}
						}
						$message = "Credit Inquiries saved successfully!";
						$this->set_progress($doc_id, $message);
					} else {
						$message = "Failed to parse Credit Inquiries section.";
						$this->set_progress($doc_id, $message, 'error');
					}
				} else {
					error_log("Error: Credit Inquiries - Unable to figured out start and end pages.");
					$message = "Credit Inquiries - Unable to figured out start and end pages.";
					$this->set_progress($doc_id, $message, 'error');
				}				
			}

			//Credit Cards, Loans & Other Debt
			if (isset($page_info['title']) && preg_match('/Credit Cards, Loans & Other Debt/', $page_info['title'])) {
				if(isset($page_info['page_start']) && !empty($page_info['page_start']) && isset($page_info['page_end']) && !empty($page_info['page_end'])) {
					$message = "Started Credit Cards, Loans & Other Debt section.";
					$this->set_progress($doc_id, $message);
					$cp_info = $this->credit_check_total->getCurrentPastDues($page_info['page_start'], $page_info['page_end']);
					if(isset($cp_info) && is_array($cp_info) && count($cp_info)) {	
						$message = "Credit Cards, Loans & Other Debt section parsing completed.";
						$this->set_progress($doc_id, $message);
						$result = $this->cct_report_parser_model->saveCurrentPastDues($doc_id, $cp_info);
						if ($result) {
							$message = "Credit Cards, Loans & Other Debt section saved successfully!";
							$this->response_messages[] = array('status' => 'success', 'message' => $message);
							
							$this->set_progress($doc_id, $message);
						} else {
							$message = "Failed to save Credit Cards, Loans & Other Debt";
							$this->response_messages[] = array('status' => 'error', 'message' => $message);
							$this->set_progress($doc_id, $message, 'error');
						}						
					} else {
						log_message('error', "Unable to parse Current and Past dues");
						$message = "Unable to parse Current and Past dues";
						$this->set_progress($doc_id, $message, 'error');
					}	
				}
			}

			//Credit Score
			if (isset($page_info['title']) && preg_match('/Credit Score/', $page_info['title'])) {
				if(isset($page_info['page_start']) && !empty($page_info['page_start']) && isset($page_info['page_end']) && !empty($page_info['page_end'])) {
						$fico_scores = $this->credit_check_total->getFicoCreditScores($page_info['page_start'], $page_info['page_end']);
						$message = "Start parsing Credit Score Section..";
						$this->set_progress($doc_id, $message);
						if(isset($fico_scores) && is_array($fico_scores) && count($fico_scores)) {
							$result = $this->cct_report_parser_model->saveFicoCreditScores($doc_id, $fico_scores);
							if ($result) {
								$message = "Fico Credit Scores saved successfully!";
								$this->response_messages[] = array('status' => 'success', 'message' => $message);
								$this->set_progress($doc_id, $message);
							} else {
								$message = "Failed to save Fico Credit Scores";
								$this->response_messages[] = array('status' => 'error', 'message' => $message);
								$this->set_progress($doc_id, $message, 'error');
							}
						}
				} else {
					$message = "Credit Score - Unable to figured out the start and end pages.";
					error_log('error', $message);
					$this->set_progress($doc_id, $message, 'error');
				}
			}	
    	}
    	$message = "Document parsing process completed!";
    	$this->set_progress($doc_id, $message, 'completed');
    	//echo json_encode($_SESSION['parser'][$doc_id]);
    	return;
    }

    private function printLog()
    {
    	echo "<pre>";
    	print_r($this->response_messages);
    	die();
    }

    public function test()
    {
    	echo "Hello Welcome to CCT Parser Tester";
    	die();
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

    private function set_progress($doc_id, $message = '', $status = 'success')
    {
    	session_start();
    	if (!isset($doc_id)) {
    		return false;
    	}
    	if (!isset($_SESSION['parser'][$doc_id])) {
    		$_SESSION['parser'][$doc_id] = array();
    	}
		$_SESSION['parser'][$doc_id]['doc_id'] = $doc_id;
		$_SESSION['parser'][$doc_id]['progress'] = $message;
		$_SESSION['parser'][$doc_id]['status'] = $status;
		session_write_close();
    }
}
