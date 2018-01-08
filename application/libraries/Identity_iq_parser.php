<?php
include_once('config.php');

//This library is for parsing credit report page from Identity IQ
class Identity_iq_parser
{
	private $report_doc;
	private $parse_result;
	private $file_path;
	private $parse_response_messages;
	protected $CI;
	private $doc_id;

	function __construct($param)
	{
		$this->CI = & get_instance();
		$this->report_doc = $param['file_name'];
		$this->doc_id = $param['id'];
		$this->parse_result["basic_info"] = null;
		$this->file_path = FCPATH."uploads".DIRECTORY_SEPARATOR."reports".DIRECTORY_SEPARATOR . $this->report_doc; 
		if (!file_exists($this->file_path)) {
			throw new Exception("Error: No report file found", 1);
		}
	}
	public function reportParsingProcess()
	{
		$data = file_get_contents($this->file_path);
		if (!$data) {
			throw new Exception("Error: Unable to processing file content", 1);			
		}
		$doc_id = $this->doc_id;
		$dom = new DOMDocument();
		@$dom->loadHTML($data);
		$dom->preserveWhiteSpace = false;
		
		// Parse the basic information like report date & reference number
		$message = "Started parsing basic information";
		$this->set_progress($doc_id, $message);
		$res = $this->parseReportBasicInfo($dom);
		if ($res) {
			$message = 'Basic Information parsed successfully.';
			$this->set_progress($doc_id, $message);
			$this->parse_response_messages[] = array('status' => 'success', 'message' => $message);
		} else {
			$message = 'Failed to parse basic information.';
			$this->set_progress($doc_id, $message);
			$this->parse_response_messages[] = array('status' => 'error', 'message' => $message);
		}
		//Get all credit bureaus
		$message = 'Started parsing Credit Bureaus information';
		$this->set_progress($doc_id, $message);
		$credit_bureau = $dom->getElementsByTagName('table')->item(BUREAUS_TABLE_INDEX);
		$res = $this->getCreditBureaus($credit_bureau);
		if ($res) {
			$message = 'Credit Bureau Information parsed successfully.';
			$this->parse_response_messages[] = array('status' => 'success', 'message' => $message);
		} else {
			$message = 'Failed to parse credit bureau information.';
			$this->parse_response_messages[] = array('status' => 'error', 'message' => $message);
		}		
		$this->set_progress($doc_id, $message);

		//Personal Information in various credit reports
		$message = 'Started parsing Personal Information';
		$this->set_progress($doc_id, $message);
		$personal_info_section = $dom->getElementsByTagName('table')->item(PERSONAL_TABLE_INDEX);
		$res = $this->getPersonalInfo($personal_info_section);	
		if ($res) {
			$message =  'Personal Information parsed successfully.';
			$this->parse_response_messages[] = array('status' => 'success', 'message' => $message);
		} else {
			$message = 'Failed to parse Personal Information.';
			$this->parse_response_messages[] = array('status' => 'error', 'message' => $message);
		}
		$this->set_progress($doc_id, $message);

		//Credit Score
		$message = 'Started parsing Credit Score';
		$this->set_progress($doc_id, $message);
 		$cs_title_div = $dom->getElementById(CREDIT_SCORE_DIV_ID);
 		if ($cs_title_div) {
 			$res = $this->parseCreditScore($cs_title_div);
 			if ($res) {
 				$message = 'Credit Score parsed successfully.';
				$this->parse_response_messages[] = array('status' => 'success', 'message' => $message);
			} else {
				$message = 'Failed to parse Credit Score.';
				$this->parse_response_messages[] = array('status' => 'error', 'message' => $message);
			}
 		} else {
 			$message = 'No Credit Score table information found'; 
 			$this->parse_response_messages[] = array('status' => 'error', 'message' => $message);
 		}
 		$this->set_progress($doc_id, $message);

 		//Account History
 		$message = 'Started parsing Account History';
		$this->set_progress($doc_id, $message);
 		$ah_title_div = $dom->getElementById(ACCOUNT_HISTORY_DIV_ID);
 		if ($ah_title_div) {
 			$res = $this->parseAccountHistory($ah_title_div);
 			if ($res) {
 				$message = 'Account History parsed successfully.';
 				$this->parse_response_messages[] = array('status' => 'success', 'message' => $message);
 			} else {
 				$message = 'Failed to parse Account History.';
 				$this->parse_response_messages[] = array('status' => 'error', 'message' => $message);
 			}
 		} else {
 			$message = 'No Account History table found in the document';
 			$this->parse_response_messages[] = array('status' => 'error', 'message' => $message);
 		}
 		$this->set_progress($doc_id, $message);

 		//Public Information 
 		$message = "Started parsing Public Information";
 		$this->set_progress($doc_id, $message);
 		$pi_title_div = $dom->getElementById(PUBIC_INFORMATION);
 		if($pi_title_div) {
 			$res = $this->parsePublicInformation($pi_title_div);	
 			if ($res) {
 				$message = 'Public Information parsed successfully.';
 				$this->parse_response_messages[] = array('status' => 'success', 'message' => $message);
 			} else {
 				$message = 'No Public Information table found';
 				$this->parse_response_messages[] = array('status' => 'error', 'message' => $message);
 			}
 		} else{
 			$message = 'No Public Information table found';
 			$this->parse_response_messages[] = array('status' => 'error', 'message' => $message);
 		}
 		$this->set_progress($doc_id, $message);

 		//Creditors Contact Information
 		$message = "Started parsing Creditors Contact Information";
 		$this->set_progress($doc_id, $message);
	 	$creditor_contacts_div = $dom->getElementById(CREDITOR_CONTACTS);
	 	if($creditor_contacts_div) {
	 		$res = $this->parseCreditorsContact($creditor_contacts_div);
	 		if ($res) {
	 			$message = 'Creditors Contact Information parsed successfully.';
 				$this->parse_response_messages[] = array('status' => 'success', 'message' => $message);
 			} else {
 				$message = 'No Creditors Contact Information table found';
 				$this->parse_response_messages[] = array('status' => 'error', 'message' => $message);
 			}
	 	} else {
	 		$message = "Warning: No Creditor Contacts table found";
	 		$this->parse_response_messages[] = array('status' => 'error', 'message' => $message);
	 	}
		$this->set_progress($doc_id, $message);
		$message = "Credit Report document parsing completed successfully.";
		$this->set_progress($doc_id, $message, 'completed');
	 	return array('parse_result' => $this->parse_result, "response_messages" => $this->parse_response_messages);	
	}

	public function parseReportBasicInfo($dom)
	{
		$report_basic_info_section = $dom->getElementById('reportTop')->getElementsByTagName('table');
		if(isset($report_basic_info_section->length) && $report_basic_info_section->length > 0) {
			foreach ($report_basic_info_section as $element) {
				$text_content = preg_replace("/\s+/", " ", trim($element->nodeValue));
				if (($report_date_pos = stripos($text_content, "Report Date")) !== false) {
					$date_section = substr($text_content, $report_date_pos);
					if (preg_match("/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/", $date_section, $matches)) {
						$this->parse_result['basic_info']['report_date'] = $matches[0];
					} else {
						$this->parse_result['basic_info']['report_date'] = null;
					} 

					$reference_section = substr($text_content, 0, $report_date_pos);
					$reference_number = str_replace("Reference # ", "", $reference_section);
					if(strlen($reference_number) > 0) {
						$this->parse_result['basic_info']['reference_number'] = $reference_number;
					} else {
						$this->parse_result['basic_info']['reference_number'] = null;
					}
					return true;
				} else {
					log_message('error', "Basic information: Unable to locate `Report Date` in the file.");
				}
			}
		} else {
			log_message('error', "Basic information of the report (Report Date & Reference Number) not found.");
		}
		return false;
	}

	public function getCreditBureaus($table)
	{
		$i = 0;	
		foreach($table->getElementsByTagName('tr') as $tr) {
			$tds = $tr->getElementsByTagName('td');
			
			foreach($tds as $td) {
				$text_content = preg_replace("/\s+/", " ", trim($td->nodeValue));
				$text_content = str_replace(":", "", $text_content);
				if (strlen($text_content) > 1) {
					$this->parse_result['credit_bureaus'][$i] = $text_content;	
					$i++;
				}
			}		
		}
		if($i){
			return true;
		} else{
			return false;
		}
	}

	public function getPersonalInfo($table)
	{
		$this->parse_result['personal_information'] = array();
		foreach($table->getElementsByTagName('tr') as $tr) {

			$ths = $tr->getElementsByTagName('th');
			if (isset($ths->length) && $ths->length> 0) {
				$k = 0;
				foreach ($ths as $th) {
					$bureau = preg_replace("/\s+/", " ", rtrim(trim($th->nodeValue), " -"));
					if ($bureau) {
						$this->parse_result['personal_information'][$k-1]['bureau'] = $bureau;
					}
					$k++;
				}
				continue;
			}

			 $tds = $tr->getElementsByTagName('td');
			 $i = 0;
			 if(isset($tds->length) && $tds->length > 0){
				 $info_tite = "";
				 $tmp_info = array();
				 foreach($tds as $td) {
				 	$info = preg_replace("/\s+/", " ", rtrim(trim($td->nodeValue), " -") );
				 	if ($info) {			 		
					 	if ($i == 0) {
					 		$info_tite = $info;
					 		 $info_tite = strtolower(str_replace(" ", "_", preg_replace("/[^a-zA-Z0-9\s]/", "", $info_tite)));
					 	} else {
					 		//$this->parse_result['details'][$i-1]['personal_information'][$info_tite] = rtrim($info, "\s-");
					 		$this->parse_result['personal_information'][$i-1][$info_tite] = $info;
					 	}
					 	$i++;
				 	}
				 }
			 }		
		}

		if (is_array($this->parse_result['personal_information']) && count($this->parse_result['personal_information'])){
			return true;
		} else {
			return flase;
		}
	}

   	public function parseCreditScore($cs_title_div)
   	{
 		$section_title = preg_replace("/\s+/", " ", trim($cs_title_div->nodeValue));
		if (strtolower($section_title)  == "credit score" ) {
			$credit_score_table = $cs_title_div->parentNode->getElementsByTagName('table')->item(1);
			if ($credit_score_table) {
				$res = $this->getCreditScore($credit_score_table);
				return $res;
			}else {
				log_message('error', "Error: No table with valid `Credit Score` details");
			}
		} else {
			log_message("Error: No table found with title `Credit Score`"); 
		}
		return false;
   	}

  	public function getCreditScore($table)
  	{
		foreach($table->getElementsByTagName('tr') as $tr){
			$i = 0;
			$info_tite = "";
			$ths = $tr->getElementsByTagName('th');
			if(isset($ths->length) && $ths->length > 0){
				$k = 0;
				foreach ($ths as $th) {
					$bureau = preg_replace("/\s+/", " ", trim($th->nodeValue));
					if ($bureau) {
						$this->parse_result['credit_score'][$k-1]['bureau'] = $bureau;
					}
					$k++;
				}
				continue;
			}

			$tds = $tr->getElementsByTagName('td');
			$i = 0;
			if(isset($tds->length) && $tds->length > 0){
				foreach ($tds as $td) {
					$info = preg_replace("/\s+/", " ", trim($td->nodeValue));
					if ($info) {
						if ($i == 0) {
					 		$info_tite = $info;
					 		$info_tite = strtolower(str_replace(" ", "_", preg_replace("/[^a-zA-Z0-9\s]/", "", $info_tite)));
					 	} else {
					 		$this->parse_result['credit_score'][$i-1][$info_tite] = $info;
					 	}
					}
					$i++;
				}
			}
		}
		
		if (is_array($this->parse_result['credit_score']) && count($this->parse_result['credit_score'])) {
			return true;
		} else {
			return false;
		}

	} 

  	public function parseAccountHistory($ah_title_div) 
  	{
	 	$account_info = array();
	 	$section_title = preg_replace("/\s+/", " ", trim($ah_title_div->nodeValue));
		if (strtolower($section_title)  == "account history" ) {

			$institution_tables = $ah_title_div->parentNode->getElementsByTagName('address-history')->item(0)->getElementsByTagName('table');

			if (isset($institution_tables->length) && $institution_tables->length) {
				$table_index = $institution_tables->length;
				$i = 1;
				while ($table_index > $i) {
					$institution_title_section = $ah_title_div->parentNode->getElementsByTagName('table')->item($i++);
					$institution_title = $this->getInstitutionTitle($institution_title_section);
					if($institution_title) {
						$account_history_table = $ah_title_div->parentNode->getElementsByTagName('table')->item($i++);
						if ($account_history_table) {
							$account_info[] = $this->getAccountInfo($account_history_table, $institution_title);
						}else {
							echo "Error: No table with valid `Credit Score` details";
						}
					} else {
						echo "No Financial institnution title found";
						$i++;
					}	 
					$i++;
				}
			}

		}else {
			log_message('error', "Error: No table f ound with account history details");
		}
		$this->parse_result['account_history'] = $account_info;
		if (is_array($account_info) && count($account_info)) {
			return true;
		} else {
			return false;
		}	
 	}

	public function getAccountInfo($table, $institution_title) 
	{
	 	$account_history_info = array('institution' => $institution_title);
		$q = 0;
		$tmp_body_trs = $table->getElementsByTagName('tr');
		
		foreach ($tmp_body_trs as $tmp_body_tr) {
			$tmp_body_th = $tmp_body_tr->getElementsByTagName('th');
			$j = $k = 0;
			foreach ($tmp_body_th as $tmp_th) {
				$node_value = preg_replace("/\s+/", " ", trim($tmp_th->nodeValue));
				if(strlen($node_value) > 1 && $j > 0){
					$account_history_info['info'][]['credit_bureau'] = $node_value;
					$k++;
				}
				$j++;
			}

			$tmp_body_tds = $tmp_body_tr->getElementsByTagName('td');
			$p = 0;
			foreach ($tmp_body_tds as $tds) {
				$node_value = preg_replace("/\s+/", " ", trim($tds->nodeValue));
				if(!$p){
					$node_value = str_replace(" - ", " ", $node_value);
					$label = strtolower(str_replace(" ", "_", preg_replace("/[^a-zA-Z0-9\s]/", "", $node_value)));
					$label = rtrim($label, "_");
				} else {
					$account_history_info['info'][$p-1][$label] = $node_value;
				}	
				if($p > 2){
					break;
				}	
				$p++;
				$q++;
			}
		}
		return ($account_history_info); 
	 }

 	public function getInstitutionTitle($section)
 	{
	 	$title = "";
	 	//Fetch Financial institutions title
		$temp_head_info = $section->getElementsByTagName('div');
		foreach ($temp_head_info as $tmp_head) {
			if($tmp_head){
				$title = preg_replace("/\s+/", " ", trim($tmp_head->nodeValue));
				break;
			}
		}
	 	return $title;
 	}

	public function parseCreditorsContact($cc_div)
	{
	 	$cc_table = $cc_div->getElementsByTagName('table')->item(1);
	 	$creditors_contact = $this->getCreditorContacts($cc_table);
	 	if ($creditors_contact) {
	 		$this->parse_result['creditors_contact'] = $creditors_contact;
	 	}
	 	if (is_array($creditors_contact) && count($creditors_contact)) {
	 		return true;
	 	} else {
	 		return false;
	 	}
	}

	// This method wil pullout all the creditor contacts details
	private function getCreditorContacts($table)
	{
		$creditor_contacts = array();
		$trs = $table->getElementsByTagName('tr');
		if (isset($trs->length) && $trs->length > 0) {
			foreach ($trs as $tr) {
				$tds = $tr->getElementsByTagName('td');
				$i = 0;
				$tmp_row = array();
				foreach ($tds as $td) {
					switch($i) {
						case 0:
							$tmp_row['creditor_name'] = preg_replace("/\s+/", " ", trim($td->nodeValue));
							break;
						case 1:
							$tmp_row['address'] = preg_replace("/\s+/", " ", trim($td->nodeValue));
							break;
						case 2:
							$tmp_row['phone_number'] = preg_replace("/\s+/", " ", trim($td->nodeValue));
							break;
					}
					$i++;
				}
				if (array_count_values($tmp_row)) {
					$creditor_contacts[] = $tmp_row;
				}
				
			}
		}
		if (count($creditor_contacts) > 0) {
			return $creditor_contacts;
		} else {
			return false;
		}
	}

	private function parsePublicInformation($pi_div)
	{
	 	$title_div = $pi_div->getElementsByTagName('div')->item(0);
	 	$public_info = array();
	 	$section_title = preg_replace("/\s+/", " ", trim($title_div->nodeValue));
		if (strtolower($section_title)  == "public information" ) {
	 		$rows = $pi_div->getElementsByTagName('ng');
	 		if(isset($rows->length) && $rows->length > 0){ 
	 			foreach ($rows as $pi_ng) {
	 				$ng_tbls = $pi_ng->getElementsByTagName('table');
	 				if(isset($ng_tbls->length) && $ng_tbls->length > 0) {
	 					$i = 0;
	 					foreach($ng_tbls as $ng_tbl) {
	 						$sub_title_div = $ng_tbl->parentNode->getElementsByTagName('div')->item(0);
	 						$public_info[$i]['title'] = preg_replace("/\s+/", " ", trim($sub_title_div->nodeValue));
	 						$info = $this->getPublicInformation($ng_tbl);
	 						$public_info[$i]['info'] = $info;
	 						$i++;
	 					}	 					
	 				}
	 			}
	 		}else {
	 			log_message('error', "No records found in Public Information table.");
	 		}		
	 	} else {
	 		log_message('error', "Error: Public Information table not found.");
	 	}
	 	$this->parse_result['public_information'] = $public_info;
	 	if (is_array($public_info) && count($public_info)) {
	 		return true;
	 	} else {
	 		return false;
	 	}
	}

	private function getPublicInformation($table)
	{
		$pi_table_info = array();
		$trs = $table->getElementsByTagName('tr');
		if (isset($trs->length) && $trs->length > 0) {
			$header_exist = false;
			foreach ($trs as $tr) {
				$ths = $tr->getElementsByTagName('th');			
				if(isset($ths->length) && $ths->length > 0) {
					$i = 0;
					foreach ($ths as $th) {
						$herad = preg_replace("/\s+/", " ", trim($th->nodeValue));
						if ($herad) {
							$header_exist = true;
							$pi_table_info[$i]['credit_bureau'] = $herad;
							$i++;
						}
					}
					continue;					
				}

				if (!$header_exist) {
					return;
				}
				$tds = $tr->getElementsByTagName('td');

				if (isset($tds->length) && $tds->length > 0 ) {
					$i = 0;
					$label = "";
					foreach ($tds as $td) {
						$node_value = preg_replace("/\s+/", " ", trim($td->nodeValue));				
						if($i == 0) {
							$label =  strtolower(str_replace(" ", "_", preg_replace("/[^a-zA-Z0-9\s]/", "", trim($node_value))));
						} else {
							if ($label) {
								$pi_table_info[$i -1][$label] = preg_replace("/\s+/", " ", trim($td->nodeValue));
									
							}						
						}
						$i++;
					}
				}
			}
			
		}
		//$this->parse_result['public_info'][] = $pi_table_info; 
		return $pi_table_info;
	}

	private function dipsplayParseData()
	{
		/*echo "<pre>";
		//echo json_encode($this->parse_result);
		print_r($this->parse_result);
		die();*/
		/*$_SESSION['serialized_data'] = urlencode(serialize($this->parse_result));
		header("Location: parse_result.php");
		die();*/

		
		
	}

	private function makeValidIndex($index_string)
	{
		return strtolower(str_replace(" ", "_", $index_string));
	}

	private function getSummary($table) 
	{
		$j = 0;
		foreach($table->getElementsByTagName('tr') as $tr){
			$i = 0;
			$info_tite = "";
			foreach ($tr->getElementsByTagName('td') as $td) {
				$info = preg_replace("/\s+/", " ", trim($td->nodeValue));
				if ($info) {
					if ($i == 0) {
				 		$info_tite = $info;
				 		$info_tite = strtolower(str_replace(" ", "_", preg_replace("/[^a-zA-Z0-9\s]/", "", $info_tite)));
				 	} else {
				 		$this->parse_result['details'][$i-1]['summary'][$info_tite] = $info;
				 	}
				}
				$i++;
			}
		}
		$j++;
	}

	public function test()
	{
		echo "Hello Testing";
	}
	public function set_progress($doc_id, $message = '', $status = 'success')
    {
    	session_start();
    	if (!isset($doc_id)) {
    		return false;
    	}
    	if (!isset($_SESSION['iiq'][$doc_id])) {
    			$_SESSION['iiq'][$doc_id] = array();
    			$_SESSION['iiq'][$doc_id]['progress'] = "";
    	}

		$_SESSION['iiq'][$doc_id]['doc_id'] = $doc_id;
		$_SESSION['iiq'][$doc_id]['progress'] .= "<br />" . $message;
		$_SESSION['iiq'][$doc_id]['status'] = $status;
		session_write_close();
    }
}

 
