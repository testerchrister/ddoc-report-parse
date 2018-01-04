<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Report_parser_model extends CI_Model
{
	private $current_doc;
    private $response_messages;
	function save_report_file($info)
	{
		$info['created'] = date('Y-m-d H:i:s');	
		$result = $this->db->insert('report_documents', $info);
		if($result) {
			$info['id'] = $result = $this->db->insert_id();
			$this->current_doc = $info;
		}
		return $result;
	}

	function start_report_parsing($doc_id)
	{
		$response = "";
		$status = $this->get_doc_parse_satus($doc_id);
		if (!$status) {
			try{
				$this->load->library('identity_iq_parser', $this->current_doc);
				try{
					$result = $this->identity_iq_parser->reportParsingProcess();	
				} catch(Exception $e) {
					$response = array('status' => "error", "message" => $e->getMessage());
				}
				
				if(is_array($result) && count($result)){
					$this->response_messages = $result["response_messages"];
					$this->saveParseReportDetails($result["parse_result"]);
				} else {
					$response = array('status' => "error", "message" => "Failed to complete credit report document parsing");
				}
			} catch(Exception $e) {
				$response = array('status' => "error", "message" => $e->getMessage());
			}
		} else {
			$response = array('status' => "error", "message" => "This document is already processed. Reff ID# ".$doc_id);
		}
		return $this->response_messages;
	}

	function get_doc_parse_satus($doc_id)
	{
		$result = $this->db->get_where('report_documents', array('id'=>$doc_id))->row();
		if(isset($result) && isset($result->status) && $result->status == 'Y') {
			return true;
		} else {
			return false;
		}
	}

	function saveParseReportDetails($report)
	{
		// Save Basic info
		$this->saveBasicInfo($report["basic_info"]);

		// Save Personal Information
		$this->savePersonalInformation($report["personal_information"]);

		//Save Credit Scores
		$this->saveCreditScores($report["credit_score"]);

		//Save Account History
		$this->saveAccountHistory($report["account_history"]);

		//Save Creditor Contact details
		$this->saveCreditorsContacts($report["creditors_contact"]);

		//Save Public Record Information
		$this->savePublicRecordInformation($report["public_information"]);
		

	}

	function saveBasicInfo($basic_info)
	{
		if (is_array($basic_info) && count($basic_info) > 0) {
			try{
				$basic_info["report_date"] = date("Y-m-d", strtotime($basic_info["report_date"]));
				$basic_info["status"] = "Y";
				$this->db->where('id', $this->current_doc["id"]);
				$this->db->update('report_documents', $basic_info);
			} catch(Exception $e) {
				log_message("error", $e->getMessage());
			}
			
		} else {
			log_message("info", "No basic information found in the document.");
			return false;
		}
		return true;
	}

	private function savePersonalInformation($personal_info)
	{
		if (is_array($personal_info) && count($personal_info) > 0) {
			$p_info = array();
			foreach ($personal_info as $info) {
				$tmp = array();
				foreach ($info as $key => $value) {
					if ($key == "credit_report_date" || $key == "date_of_birth") {
						$value = date("Y-m-d", strtotime($value));
					}
					$tmp[$key] = $value;

				}
				$tmp["doc_id"] = $this->current_doc["id"];
				$p_info[] = $tmp;			
			}
			try {
					foreach($p_info as $info) {
						$this->db->insert('personal_information', $info);
					}				
					log_message('info', "Saved basic details sucessfully");
				} catch (Exception $e) {
					log_message("error", "Peronal Information: ". $e->getMessage());
				}
		} else {
			log_message("info", "No personal information found in document.");
			return false;
		}
	}

	public function saveCreditScores($credit_scores)
	{
		if (is_array($credit_scores) && count($credit_scores)) {
			foreach ($credit_scores as $cs) {
				try{
					$cs["doc_id"] = $this->current_doc["id"];
					$this->db->insert("credit_scores", $cs);
				} catch(Exception $e){
					log_message("error:", "Credit Scores: " . $e->getMessage());
				}
			}
		} else {
			log_message("info", "No credit score details found in the document");
			return false;
		}
	}

	private function saveAccountHistory($account_history)
	{
		if (isset($account_history) && count($account_history)) {
			foreach ($account_history as $ah) {
				if(isset($ah["institution"]) &&  !empty($ah["institution"])) {
					$inst_id = $this->saveInstitutionDetails($ah["institution"]);
					if ($inst_id) {
						$account_info = $ah["info"];
						if (is_array($account_info) && count($account_info)) {
							foreach($account_info as $ah_info) {
								try{
									$record = array();
									foreach ($ah_info as $key => $value) {
										if(in_array($key, array("date_opened", "last_reported", "date_last_active", "date_of_last_payment"))) {
											$value = date('Y-m-d', strtotime($value));
										}
										$record[$key] = $value;
										$record["doc_id"] = $this->current_doc["id"];
										$record["fi_id"] = $inst_id;
									}
									$this->db->insert("account_history", $record);
								} catch (Exception $e){
									log_message("error", "Unabel to save account history of " . $ah["institution"]. " from " . $ah_info["credit_bureau"] . ". ". $e->getMessage());
								}
							}
							
						} else{
							log_message("info", "No valid information found under " . $ah["institution"]);
						}
					} else {
						log_message("info", "Unable to save financial institution details. Aborting related account history transation details");
					}
				} else {
					log_message("info", "No Financial information found. Aborting Account History parsing.");
				}
			}
		} else {
			log_message("info", "No Account History information found in the document");
			return false;
		}
	}

	private function saveInstitutionDetails($institution)
	{
		// Check already exist
		$inst_id = 0;
		$result = $this->db->get_where("financial_institutions", array("institution" => $institution))->row();
		if($result && isset($result->id))  {
			$inst_id = $result->id;
		} else {
			try{
				$this->db->insert('financial_institutions', array("institution" => $institution, "created" => date('Y-m-d H:i:s')));
				$inst_id  = $this->db->insert_id();
			} catch (Exception $e) {
				log_message("Error", "Failed to insert financial instituion details.");
			}
		}

		return $inst_id;
	}

	private function saveCreditorsContacts($contacts)
	{
		if(is_array($contacts) && count($contacts)) {
			foreach ($contacts as $contact) {
				$contact["doc_id"] = $this->current_doc["id"];
				try{
					$this->db->insert("creditors_contact", $contact);
				} catch(Exception $e) {
					log_message("error", "Failed to save creditor contact details. " . $e->getMessage());
				}
			}
		}else {
			log_message("info", "No Creditors contact information found in the document");
		}
	}

	private function savePublicRecordInformation($public_records)
	{
		if (is_array($public_records) && count($public_records)) {
			foreach ($public_records as $pr) {
				$public_record_title = $pr["title"];
				if ($public_record_title) {
					$pr_id = $this->getPublicRecordId($public_record_title);
					if ($pr_id) {
						$pr_info = $pr["info"];
						if (is_array($pr_info) && count($pr_info)) {
							foreach($pr_info as $p_record){
								$is_empty = true;
								$temp_pr = array();
								foreach ($p_record as $key => $val) {
									if ($key != "credit_bureau" && !empty($val)) {
										$is_empty = false;										
									} 
									if (in_array($key, array("date_filedreported", "closing_date", "released_date", "date_deferred")) && !empty($val)){
										$val = date('Y-m-d', strtotime($val));
									} 
									if (!empty($val)) {
										$temp_pr[$key] = $val;
									}
								}
								if (!$is_empty) {
									try{
										$temp_pr["doc_id"] = $this->current_doc["id"];
										$this->db->insert("public_record_information", $temp_pr);
									} catch (Exception $e) {
										log_message("error", "Failed to save public record information " .$public_record_title . " - " . $temp_pr["credit_bureau"]);
									}
									
								}
							}
						} else {
							log_message("info", "No public records found under ". $public_record_title);
						}	
					} else {
						log_message("debug", "Unable to find/save public record type.");
					}
				}else {
					log_message("info", "Can't identify the public record type.");
				}
			}
		} else {
			log_message("info", "No public records found in the document");
		}
	}

	private function getPublicRecordId($public_record_title)
	{
		// Check its a;lready exist
		try{
			$record = $this->db->get_where("public_record_types", array('record_type' => $public_record_title))->row();
			if ($record) {
				return $record->id;
			} else {
				//Insert as a new type
				$this->db->insert("public_record_types", array("record_type" => $public_record_title, "created" => date('Y-m-d H:i:s')));
				return $this->db->insert_id();
			}
		} catch(Exception $e) {
			log_message("error", "Failed to retrieve public record type from ");
		}
		return false;
	}

	/********************** Credit Check Total *****************************/
	public function start_cct_report_parsing($doc_id)
	{
		$this->load->library('pdfparser');
		$file = FCPATH."uploads".DIRECTORY_SEPARATOR."cct-reports".DIRECTORY_SEPARATOR . $this->current_doc['file_name'];
		$content = $this->pdfparser->parseFile($file);
		if ($content) {
			print($content);
		}
		die();
	}

}