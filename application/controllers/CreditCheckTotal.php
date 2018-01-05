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
		if (strtolower($_SERVER["REQUEST_METHOD"]) == 'post') {
			$this->load->library('form_validation');
			$this->form_validation->set_rules('passcode', "Pass Code", "required|callback_authenticate");
			if ($this->form_validation->run()) {

				if (!$_FILES['userfile']['error']) {
					$result = $this->do_upload();
					if (!array_key_exists('error', $result)) {
						$report_info = array("user_id" => 1001, "file_name" => $result["upload_data"]["file_name"]);
						$this->load->model("report_parser_model");
						$this->report_type_info = $this->report_model->get_report_type_info('credit-check-total');
						$report_info['report_type'] = $this->report_type_info->id;	
						$doc_id = $this->report_parser_model->save_report_file($report_info);
						if($doc_id){
							$this->current_doc['id'] = $doc_id;
							$this->startParsing();							
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
		$data = array('error' => $this->validation_errors, 'parse_report' => $this->response_messages);
		$this->load->view('header_tpl');
		$this->load->view('credit_check_total_tpl', $data);
		$this->load->view('footer_tpl');
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

    private function startParsing()
    {	
    	if(isset($this->current_doc['id']) && is_numeric($this->current_doc['id'])) {
    		$doc_id = $this->current_doc['id'];
    	} else {
    		log_message('error', "Invalid document ID");
    		return false;
    	}
    	$this->load->library('credit_check_total');
    	$this->load->model('cct_report_parser_model');
    	$file_name = $this->cct_report_parser_model->getDocFileName($doc_id);
    	if ($file_name) {
    		$file_name = FCPATH."uploads".DIRECTORY_SEPARATOR."cct-reports".DIRECTORY_SEPARATOR.$file_name;
    	} else {
    		log_message("error", "Unable to fetch uploaded file name");
    		return false;
    	}
    	
    	$result = $this->credit_check_total->init($file_name);
    	if ($result) {
    		$basic_info = $this->credit_check_total->getBasicInfo();
    		if (isset($basic_info) && is_array($basic_info) && count($basic_info)) {
    			$this->response_messages[] = array('status' => 'success', 'message' => "Basic Information parsed successfully!");
    			$result = $this->cct_report_parser_model->saveBasicInfo($doc_id, $basic_info);
    			if ($result) {
    				$this->response_messages[] = array('status' => 'success', 'message' => "Basic Information saved successfully!");
    				
    			} else {
    				$this->response_messages[] = array('status' => 'error', 'message' => "Failed to save Basic Information");
    			}
    		} else {
    			log_message("error", "Failed to parse basic information");
    			$this->response_messages[] = array('status' => 'error', 'message' => "Failed to parse Basic Information");
    		}

    		$info_types = $this->credit_check_total->getPageInfoType();
    		if (is_array($info_types)  && count($info_types)) {
    			$this->parseDocumentSections($info_types);
    		} else {
    			log_message("error", "Unable to document pages information. Document parsing aborting.");
    			$this->response_messages[] = array('status' => 'error', 'message' => "Unable to document pages information. Document parsing aborting.");
    		}

    	} else {
    		echo "Error: Failed to initialize Document parser";
    		$this->response_messages[] = array('status' => 'error', 'message' => "Failed to initialize document parser");
    	}

    	return true;
    }

    private function parseDocumentSections($info_types)
    {
    	if(isset($this->current_doc['id']) && is_numeric($this->current_doc['id'])) {
    		$doc_id = $this->current_doc['id'];
    	} else {
    		log_message('error', "Invalid document ID");
    		return false;
    	}
    	foreach ($info_types as $page_info) {
    		if (isset($page_info['title']) && preg_match('/Personal Information/', $page_info['title'])) {
				if(isset($page_info['page_start']) && !empty($page_info['page_start']) && isset($page_info['page_end']) && !empty($page_info['page_end'])) {
					$personal_info = $this->credit_check_total->getPersonalInformation($page_info['page_start'], $page_info['page_end']);
					$p_info = array();
					if (isset($personal_info) && is_array($personal_info) && count($personal_info)) {
						if (isset($personal_info['experian']) && count($personal_info['experian'])) {
							$tmp_info['bureau'] = 'experian';							
							$tmp_info['name'] = isset($personal_info['experian']['name']) ? $personal_info['experian']['name'] : "";
							$tmp_info['year_of_birth'] = isset($personal_info['experian']['year_of_birth']) ? $personal_info['experian']['year_of_birth'] : "";
							if(isset($personal_info['experian']['addresses']) && count(isset($personal_info['experian']['addresses']))) {
								$tmp_info['addresses'] = implode("*****", $personal_info['experian']['addresses']);
							} 
							$tmp_info['current_employer'] = isset($personal_info['experian']['current_employer']) ? $personal_info['experian']['current_employer'] : "";
							$tmp_info['previous_employer'] = isset($personal_info['experian']['previous_employer']) ? $personal_info['experian']['previous_employer'] : "";
							$p_info[] = $tmp_info;
						}

						if (isset($personal_info['equifax']) && count($personal_info['equifax'])) {
							$tmp_info['bureau'] = 'equifax';							
							$tmp_info['name'] = isset($personal_info['equifax']['name']) ? $personal_info['equifax']['name'] : "";
							$tmp_info['year_of_birth'] = isset($personal_info['equifax']['year_of_birth']) ? $personal_info['equifax']['year_of_birth'] : "";
							
							if(isset($personal_info['equifax']['addresses']) && count(isset($personal_info['equifax']['addresses']))) {
								$tmp_info['addresses'] = implode("*****", $personal_info['equifax']['addresses']);
							}
							$tmp_info['current_employer'] = isset($personal_info['equifax']['current_employer']) ? $personal_info['equifax']['current_employer'] : "";
							$tmp_info['previous_employer'] = isset($personal_info['equifax']['previous_employer']) ? $personal_info['equifax']['previous_employer'] : "";
							$p_info[] = $tmp_info;
						}

						if (isset($personal_info['transunion']) && count($personal_info['transunion'])) {
							$tmp_info['bureau'] = 'transunion';							
							$tmp_info['name'] = isset($personal_info['transunion']['name']) ? $personal_info['transunion']['name'] : "";
							$tmp_info['year_of_birth'] = isset($personal_info['transunion']['year_of_birth']) ? $personal_info['transunion']['year_of_birth'] : "";
							if(isset($personal_info['transunion']['addresses']) && count(isset($personal_info['transunion']['addresses']))) {
								$tmp_info['addresses'] = implode("*****", $personal_info['transunion']['addresses']);
							}
							$tmp_info['current_employer'] = isset($personal_info['transunion']['current_employer']) ? $personal_info['transunion']['current_employer'] : "";
							$tmp_info['previous_employer'] = isset($personal_info['transunion']['previous_employer']) ? $personal_info['transunion']['previous_employer'] : "";
							$p_info[] = $tmp_info;
						}

						$result = $this->cct_report_parser_model->savePersonalInfo($doc_id, $p_info);
		    			if ($result) {
		    				$this->response_messages[] = array('status' => 'success', 'message' => "Personal Information saved successfully!");
		    				
		    			} else {
		    				$this->response_messages[] = array('status' => 'error', 'message' => "Failed to save Personal Information");
		    			}
					}
				} else {
					log_message('error', "Unable to find Personal Information section start and end pages");
				}
			}

			//Report Summary
			if (isset($page_info['title']) && preg_match('/Report Summary/', $page_info['title'])) {
				if(isset($page_info['page_start']) && !empty($page_info['page_start']) && isset($page_info['page_end']) && !empty($page_info['page_end'])) {
					$report_summary = $this->credit_check_total->getReportSummary($page_info['page_start'], $page_info['page_end']);
					foreach ($report_summary as $summary) {
						$result = $this->cct_report_parser_model->saveReportSummary($doc_id, $summary);
						if ($result) {
		    				$this->response_messages[] = array('status' => 'success', 'message' => "Report Summary saved successfully!");
		    				
		    			} else {
		    				$this->response_messages[] = array('status' => 'error', 'message' => "Failed to save Report Summary");
		    			}

					}				
				} else {
					error_log("Error: Report Summary - Unable to figure out the start and end pages in the document");
				}			
			}

			//Credit Inquiries
			if (isset($page_info['title']) && preg_match('/Credit Inquiries/', $page_info['title'])) {
				if(isset($page_info['page_start']) && !empty($page_info['page_start']) && isset($page_info['page_end']) && !empty($page_info['page_end'])) {
					$credit_inquiries = $this->credit_check_total->getCreditInquiries($page_info['page_start'], $page_info['page_end']);
					if ($credit_inquiries && is_array($credit_inquiries) && count($credit_inquiries)) {
						foreach ($credit_inquiries as $ci_info) {
							$result = $this->cct_report_parser_model->saveCreditInquiries($doc_id, $ci_info);
							if ($result) {
								$this->response_messages[] = array('status' => 'success', 'message' => "Credit Inquiries saved successfully!");
							} else {
								$this->response_messages[] = array('status' => 'error', 'message' => "Failed to save Credit Inquiries");
							}
						}
					}
				} else {
					error_log("Error: Credit Inquiries - Unable to figured out start and end pages.");
				}				
			}

			//Credit Cards, Loans & Other Debt
			if (isset($page_info['title']) && preg_match('/Credit Cards, Loans & Other Debt/', $page_info['title'])) {
				if(isset($page_info['page_start']) && !empty($page_info['page_start']) && isset($page_info['page_end']) && !empty($page_info['page_end'])) {
					$cp_info = $this->credit_check_total->getCurrentPastDues($page_info['page_start'], $page_info['page_end']);
					if(isset($cp_info) && is_array($cp_info) && count($cp_info)) {	
						$result = $this->cct_report_parser_model->saveCurrentPastDues($doc_id, $cp_info);
						if ($result) {
							$this->response_messages[] = array('status' => 'success', 'message' => "Credit Cards, Loans & Other Debt saved successfully!");
						} else {
							$this->response_messages[] = array('status' => 'error', 'message' => "Failed to save Credit Cards, Loans & Other Debt");
						}						
					} else {
						log_message('error', "Unable to parse Current and Past dues");
					}
					
				}
			}

			//Credit Score
			if (isset($page_info['title']) && preg_match('/Credit Score/', $page_info['title'])) {
				if(isset($page_info['page_start']) && !empty($page_info['page_start']) && isset($page_info['page_end']) && !empty($page_info['page_end'])) {
						$fico_scores = $this->credit_check_total->getFicoCreditScores($page_info['page_start'], $page_info['page_end']);

						if(isset($fico_scores) && is_array($fico_scores) && count($fico_scores)) {
							$result = $this->cct_report_parser_model->saveFicoCreditScores($doc_id, $fico_scores);
							if ($result) {
								$this->response_messages[] = array('status' => 'success', 'message' => "Fico Credit Scores saved successfully!");
							} else {
								$this->response_messages[] = array('status' => 'error', 'message' => "Failed to save Fico Credit Scores");
							}
						}
				} else {
					error_log("Error: Credit Score - Unable to figured out the start and end pages.");
				}
			}


			
    	}
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
}
