<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cct_report_parser_model extends CI_Model
{
	public function saveBasicInfo($report_id, $basic_info)
	{
		$this->db->where('id', $report_id);
		$this->db->update('report_documents', $basic_info);
	}

	/**
	 * This method will return file of the given document id
	 */
	public function getDocFileName($doc_id)
	{
		$this->db->select('file_name');
		try{
			$res = $this->db->get_where('report_documents', array('id'=>$doc_id))->row();	
		} catch(Exception $e) {
			log_message('error', "DB Error: Unable to get file name. ". $e->getMessage());
			return false;
		}
		return $res->file_name;
	}

	public function savePersonalInfo($doc_id, $personal_info)
	{
		foreach($personal_info as $info) {
			$info['doc_id'] = $doc_id;
			try{
				$this->db->insert('cct_personal_information', $info);
			} catch(Exception $e) {
				log_message('error', "DB Error: Unable to save peronal information. ".$e->getMessage());
				return false;
			}
		}
		return true;
	}

	public function saveReportSummary($doc_id, $summary)
	{
		if(!is_array($summary) || !count($summary)) {
			return false;
		}
		foreach ($summary as $key => $value) {			
			$type = $key;
			if (is_array($value)) {
				foreach ($value as $key2 => $value2) {
					$bureau = $key2;
					if(is_array($value2)){
						$tmp_summary = array();
						foreach ($value2 as $key3 => $value3) {
							$tmp_summary[strtolower($key3)] = $value3;
						}
						if(count($tmp_summary)) {
							$tmp_summary['type'] 	= $type;
							$tmp_summary['bureau'] 	= $bureau;
							$tmp_summary['doc_id']	= $doc_id;
							try{
								$this->db->insert('cct_report_summary', $tmp_summary);
							} catch(Exception $e) {
								log_message('error', $e->getMessage());
							}
						} else {
							log_message('info', "No summary record found");
						}
					} else {
						log_message('info', "No summary record found for type ".$type);
					}
				}
			} else {
				log_message('info', "No summary record found for bureau ".$bureau);
			}
		}

		return true;
	}

	public function saveCreditInquiries($doc_id, $info)
	{
		if(!is_array($info) || !count($info)) {
			return false;
		}

		foreach ($info as $inquiry) {
			$master_info = array();
			$master_info['title'] = isset($inquiry['title']) ?  $inquiry['title'] : "";
			$master_info['address'] = isset($inquiry['address']) ?  $inquiry['address'] : "";
			$master_info['doc_id'] = $doc_id;
			try{
				$this->db->insert('cct_credit_inquiry_master', $master_info);
				$master_id = $this->db->insert_id();
				if ($master_id) {
					unset($inquiry['title'], $inquiry['address']);
					foreach ($inquiry as $key => $value) {
						$bureau_info = array();
						$bureau_info['inquiry_id'] = $master_id;
						$bureau_info['bureau'] = $key;
						$bureau_info['doc_id'] = $doc_id;
						$bureau_info['business_name'] = isset($value['business_name']) ? $value['business_name'] : "";
						//$bureau_info['inquiry_date'] = isset($value['inquiry_date']) ? $value['inquiry_date'] : "";
						if(isset($value['inquiry_date']) && !empty($value['inquiry_date'])) {
							if(preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4}/", $value['inquiry_date'], $match)) {
								$bureau_info['inquiry_date'] = date('Y-m-d', strtotime($match[0]));
							}
						}
						$bureau_info['business_type'] = isset($value['business_type']) ? $value['business_type'] : "";
						try{
							$this->db->insert('cct_credit_inquiry_bureau_info', $bureau_info);	
						} catch(Exception $e) {
							log_message('error', $e->getMessage());
						}
						
					}
				} else {
					log_message('error', "Failed to get the last insert ID of inquiry master");
				}	
			} catch(Exception $e) {
				log_message('error', $e->getMessage());
			}
			
		}
		return true;
	}

	public function saveCurrentPastDues($doc_id, $info)
	{
		if(!is_array($info) || !count($info)) {
			return false;
		}
		foreach ($info as $data) {
			$master_info = array();
			$master_info['title'] = isset($data['title']) ?  $data['title'] : "";
			$master_info['address'] = isset($data['address']) ?  $data['address'] : "";
			$master_info['doc_id'] = $doc_id;
			try{
				$this->db->insert('cct_creditcard_other_debt_master', $master_info);	
				$master_id = $this->db->insert_id();
			} catch(Exception $e) {
				log_message('error', "Failed to insert cct_creditcard_other_debt_master");
				continue;
			}
			if (isset($master_id)) {
				unset($data['title'], $data['address']);
				foreach ($data as $key => $value) {
					$bureau_info = array();
					$bureau_info['master_id'] = $master_id;
					$bureau_info['bureau'] = $key;
					$bureau_info['doc_id'] = $doc_id;
					$bureau_info['account_name'] = isset($value['account_name']) ? $value['account_name'] : "";
					$bureau_info['account'] = isset($value['account']) ? $value['account'] : "";
					$bureau_info['account_type'] = isset($value['account_type']) ? $value['account_type'] : "";
					$bureau_info['balance'] = isset($value['balance']) ? $value['balance'] : "";
					$bureau_info['past_due'] = isset($value['past_due']) ? $value['past_due'] : "";
					if (isset($value['date_opened']) && !empty($value['date_opened']) && preg_match('/[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4}/', $value['date_opened'], $match)) {
						$bureau_info['date_opened'] = date('Y-m-d', strtotime($match[0]));
					}
					
					$bureau_info['payment_status'] = isset($value['payment_status']) ? $value['payment_status'] : "";
					$bureau_info['mo_payment'] = isset($value['mo_payment']) ? $value['mo_payment'] : "";
					$bureau_info['payment_status'] = isset($value['payment_status']) ? $value['payment_status'] : "";
					$bureau_info['high_balance'] = isset($value['high_balance']) ? $value['high_balance'] : "";
					$bureau_info['limits'] = isset($value['limit']) ? $value['limit'] : "";
					$bureau_info['terms'] = isset($value['terms']) ? $value['terms'] : "";
					$bureau_info['comments'] = isset($value['comments']) ? $value['comments'] : "";

					try{
						$this->db->insert('cct_creditcard_other_debt_bureau_info', $bureau_info);	
					} catch(Exception $e) {
						log_message('error', $e->getMessage());
					}
				}
			} else {
				log_message('error', "Failed to get the last insert ID of Credit Card and Other Debt master");
			}
			
		}
		return true;
	}

	public function saveFicoCreditScores($doc_id, $info)
	{
		if(!is_array($info) || !count($info)) {
			return false;
		}
		foreach ($info as $data) {
			$cs_info = array();
			$cs_info['title'] = isset($data['title']) ?  $data['title'] : "";
			$cs_info['experian'] = isset($data['experian']) ?  $data['experian'] : "";
			$cs_info['equifax'] = isset($data['equifax']) ?  $data['equifax'] : "";
			$cs_info['transunion'] = isset($data['transunion']) ?  $data['transunion'] : "";
			$cs_info['doc_id'] = $doc_id;
			try{
				$this->db->insert('cct_fico_credit_scores', $cs_info);
			} catch(Exception $e) {
				log_message('error', "Failed to insert cct_fico_credit_scores");
				continue;
			}
		}
		return true;
	}
}