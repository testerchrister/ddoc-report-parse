<?php
ini_set('max_execution_time', 0);
include './vendor/autoload.php';
use Gufy\PdfToHtml\Config;

class Credit_check_total
{
	private $pdf;
	private $total_pages;
	private $pase_report_info;
	private $info_type;

	private $basic_info;

	public function __construct()
	{
		Config::set('pdftohtml.bin', 'C:/poppler-0.51/bin/pdftohtml.exe');
		Config::set('pdfinfo.bin', 'C:/poppler-0.51/bin/pdfinfo.exe');
		$this->total_pages = 0;
		$this->pase_report_info = array();
		$this->info_type = array();
	}

	/**
	 * File name should be full path
	 */
	public function init($file_name)
	{
		if(isset($file_name) && file_exists($file_name)) {
			try{
				$this->pdf = new Gufy\PdfToHtml\Pdf($file_name);
				$this->total_pages = $this->pdf->getPages();
				return true;	
			} catch(Exception $e) {
				log_message("error", "Unable to parse the document to HTML. " . $e->getMessage());
				return fase;
			}

		} else {
			log_message("error", "File not exists");
			return false;
		}
	}

	private function getPageDom($page = '')
	{
		if (is_numeric($page) && $page <= $this->total_pages) {
			try {
				$html = $this->pdf->html($page);
				if ($html) {
					$dom = new DOMDocument();
					@$dom->loadHTML($html, LIBXML_NONET);
					$dom->preserveWhiteSpace = false;	
				} else {
					log_message("error", "Unable to parse PDF to HTML format!") ;
					return false;
				}	
			} catch(Exception $e) {
				log_message('error', "Exception : Unable to parse page to HTML");
				return false;
			}
			
		} else {
			log_message('error', "Invalid page number");
			return false;
		}
		return $dom;
	}

	/**
	 * This method is the beginning of document parsing.
	 */
	public function startDocParsing()
	{
		$basic_info = $this->getBasicInfo();
		if ($basic_info) {
			$this->showResult($basic_info);
		}
	}

	public function getBasicInfo()
	{	
		$dom = $this->getPageDom(1);
		if (!$dom instanceof DOMDocument) {
			log_message('error', "Unable to convert page to DOM Object");
			return false;
		}
		$basic_info = array();
		$paras = $dom->getElementById('page1-div')->getElementsByTagName('p');
		if ($paras instanceof DOMNodeList &&  $paras->length) {
			$next = false;
			foreach ($paras as $p) {
				$content = $p->textContent;
				$content = str_replace("Â", "", $content);
				if (!empty($content) && !isset($basic_info['created_for'])) {				 
					 $exist = preg_match("/Credit Report Prepared For/", $content);
					 if($exist) {
					 	$next = true;
					 } else if($next) {
					 	$basic_info['created_for'] = $content;
					 	$next = false;
					 } 
				}
				if (!empty($content) && isset($basic_info['created_for'])) {
					$exist = preg_match("/Report as Of/", $content);
					if ($exist) {
						$report_date_parts = explode(":", $content);
						$report_date = preg_replace("/\s/", "", $report_date_parts[1]);
						if(preg_match("/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/", $report_date, $match)){
							$report_date = $match[0];
						} else {
							$report_date = date('Y-m-d');
						}
						try{
							$basic_info['report_date'] = date("Y-m-d", strtotime(trim($report_date)));
						} catch(Exception $e) {
							log_message('error', "Failed to convert report date. " . $e->getMessage());
						}						
					}
				}
				
				if (isset($basic_info['created_for']) && isset($basic_info['report_date'])) {
					break;
				}
			}		
		} else {
			log_message('info', "No valid DOMNodeList object found in the page");
		}
		return  $basic_info;

	}

	//This method scans the document index page for each sections of the report
	public function getPageInfoType()
	{
		$dom = $this->getPageDom(2);
		if (!$dom instanceof DOMDocument) {
			log_message('error', "Unable to convert page to DOM Object");
			return false;
		}

		$pages_info = array();
		$paras = $dom->getElementById('page2-div')->getElementsByTagName('p'); 
		if ($paras instanceof DOMNodeList &&  $paras->length) {
			$page_head = '';
			$i = -1;
			foreach ($paras as $p) {
				if ($p->hasAttribute('class') && $p->getAttribute('class') == 'ft03') {
					$content = $p->textContent;
					$content = str_replace("Â", "", $content);
					if(!preg_match('/Table of Contents/', $content)) {
						$i++;
						$pages_info[$i]['title'] = $content;
						continue;
					}
				}

				if (isset($pages_info[$i]['title']) && !isset($pages_info[$i]['page_start']) && $p->hasAttribute('class') && $p->getAttribute('class') == 'ft01') {
					$content = $p->textContent;
					$content = str_replace("Â", "", $content);
					$pages_info[$i]['page_start'] = $content;
					if ($i > 0) {
						$pages_info[$i - 1]['page_end'] = $pages_info[$i]['page_start'] - 1;
					}
					continue;
				}
			}

			if (!isset($pages_info[$i]['page_end'])) {
				
				$pages_info[$i]['page_end'] =  $this->total_pages;
			}
			//$this->showResult($pages_info);
			return $pages_info;
		}
	}

	//This method parse personal information section.
	public function getPersonalInformation($page_start, $page_stop)
	{
		for($i = $page_start; $i <= $page_start; $i++) {	
			$dom = $this->getPageDom($i);
			if (!$dom instanceof DOMDocument) {
				log_message('error', "Unable to convert page to DOM Object");
				return false;
			}

			$personal_info = array();
			$paras = $dom->getElementById('page3-div')->getElementsByTagName('p');
			$name_found = false;
			if($paras instanceof DOMNodeList) {
				$name_parsed = false;
				$aka_found = false;
				$aka_parsed = false;
				$yob_found = false;
				$yob_parsed = false;
				$next_head = '';
				$address_found = $address_parsed = false;
				foreach($paras as $p) {
					$sub_head = $p->getElementsByTagName('i');
					if ($sub_head instanceof DOMNodeList && $sub_head->length) {
						foreach($sub_head as $sh) {
							$bureau_index = 1;
							$next_head = str_replace("Â", "", $sh->textContent);
						}
						continue;
					}
					$content = $p->textContent;
					$content = str_replace("Â", "", $content);
					if(!empty($next_head) && preg_match("/Name/", $next_head)) {
						if (!$name_parsed) {
							switch ($bureau_index) {
								case 1:
									$personal_info['experian']['name'] = $content;
									$bureau_index++;
									break;
								case 2:
									$personal_info['equifax']['name'] = $content;
									$bureau_index++;
									break;
								case 3:
									$personal_info['transunion']['name'] = $content;
									$bureau_index = 1;
									$name_parsed = true;
									break;
							}
						}
						continue;
					}
					 
					if (!empty($next_head) && preg_match("/AKA/", $next_head)) {
						if (!$aka_parsed) {
							switch($bureau_index) {
								case 1:
									$personal_info['experian']['aka'] = $content;
									$bureau_index++;
									break;
								case 2:
									$personal_info['equifax']['aka'] = $content;
									$bureau_index++;
									break;
								case 3:
									$personal_info['transunion']['aka'] = $content;
									$bureau_index = 1;
									$aka_found = false;
									$aka_parsed = true;
									break;
							}
						}
						continue;
					}
					

					if(!empty($next_head) && preg_match("/Year of Birth/", $next_head)) {
						if (!$yob_parsed) {
							switch($bureau_index) {
								case 1:
									$personal_info['experian']['year_of_birth'] = $content;
									$bureau_index++;
									break;
								case 2:
									$personal_info['equifax']['year_of_birth'] = $content;
									$bureau_index++;
									break;
								case 3:
									$personal_info['transunion']['year_of_birth'] = $content;
									$bureau_index = 1;
									$yob_parsed = true;
									break;
							}
						}
						continue;
					}
					
					if(!empty($next_head) && preg_match("/Address/", $next_head)) {
						if (!$address_parsed) {
							switch($bureau_index) {
								case 1:
									$personal_info['experian']['addresses'] = explode('****', str_replace('&nbsp;&nbsp;', '****', $content));
									$bureau_index++;
									break;
								case 2:
									$personal_info['equifax']['addresses'] = explode('****', str_replace('&nbsp;&nbsp;', '****', $content));
									$bureau_index++;
									break;
								case 3:
									$personal_info['transunion']['addresses'] = explode('****', str_replace('&nbsp;&nbsp;', '****', $content));
									$bureau_index = 1;
									$address_parsed = true;
									break;
							}
						}
						continue;
					}
					$type = null;
					if($p->hasAttribute('style')) {
						if(preg_match('/left:3\d\dpx/', $p->getAttribute('style'))) {
							$type = 'experian';
						} elseif(preg_match('/left:5\d\dpx/', $p->getAttribute('style'))) {
							$type = 'equifax';
						} elseif (preg_match('/left:7\d\dpx/', $p->getAttribute('style'))) {
							$type = 'transunion';
						}
					}
					if (!empty($next_head) && preg_match("/Current Employer/", $next_head)) {
						if (!is_null($type) && !preg_match('/Page/', $content)) {
							$personal_info[$type]['current_employer'] = $content;	
						}
						continue;
					}

					if (!empty($next_head) && preg_match("/Previous Employer/", $next_head)) {
						if (!is_null($type) && !preg_match('/Page/', $content)) {
							$personal_info[$type]['previous_employer'] = $content;
						}
						continue;
					}
				}
			} else {
				die('Something goes wrong while parsinf the record');
			}
		}		
		return $personal_info;

	}

	// This method parse report summary section.
	public function getReportSummary($page_start, $page_stop)
	{
		$full_report_summary = array();
		for($i = $page_start; $i <= $page_stop; $i++) {
			$dom = $this->getPageDom($i);
			if (!$dom instanceof DOMDocument) {
				log_message('error', "Unable to convert page to DOM Object");
				return false;
			}
		
			$report_summary = array();
			$paras = $dom->getElementsByTagName('p');
			$next_head = $next_sub_head = '';
			if ($paras instanceof DOMNodeList) {
				foreach($paras as $p) {
					$tmp_sub_head = $p->getElementsByTagName('i');
					if ($tmp_sub_head instanceof DOMNodeList && $tmp_sub_head->length) {
						foreach($tmp_sub_head as $sbh) {
							$next_sub_head = str_replace("Â", "", $sbh->textContent);
							//echo $next_sub_head . " -- Sub<br />";
							$bureau_index = 1;
							break;
						}
						continue;
					}

					$temp_head = $p->getElementsByTagName('b');
					if ($temp_head instanceof DOMNodeList && $temp_head->length) {
						foreach($temp_head as $nh) {
							$next_head = str_replace("Â", "", $nh->textContent);
							//echo $next_head . "<br />";
							if (preg_match('/Report Summary/', $next_head)) {
								$next_head = '';
							}
						}
						continue;
					}
					$content = $p->textContent;
					$content = str_replace("Â", "", $content);
					if(!empty($next_sub_head) && preg_match('/Real Estate/', $next_head)) {
						/*echo $next_sub_head; die();*/
						switch ($bureau_index) {
							case 1:
								$report_summary['real_estate']['experian'][$next_sub_head] = $content;
								$bureau_index++;
								break;
							case 2:
								$report_summary['real_estate']['equifax'][$next_sub_head] = $content;
								$bureau_index++;
								break;
							case 3:
								$report_summary['real_estate']['transunion'][$next_sub_head] = $content;
								$bureau_index = 1;
								break;
						}
					}
					if(!empty($next_sub_head) && preg_match('/Revolving/', $next_head)) {
						/*echo $next_sub_head; die();*/
						switch ($bureau_index) {
							case 1:
								$report_summary['revolving']['experian'][$next_sub_head] = $content;
								$bureau_index++;
								break;
							case 2:
								$report_summary['revolving']['equifax'][$next_sub_head] = $content;
								$bureau_index++;
								break;
							case 3:
								$report_summary['revolving']['transunion'][$next_sub_head] = $content;
								$bureau_index = 1;
								break;
						}
					}

					if(!empty($next_sub_head) && preg_match('/Installments/', $next_head)) {
						/*echo $next_sub_head; die();*/
						switch ($bureau_index) {
							case 1:
								$report_summary['installments']['experian'][$next_sub_head] = $content;
								$bureau_index++;
								break;
							case 2:
								$report_summary['installments']['equifax'][$next_sub_head] = $content;
								$bureau_index++;
								break;
							case 3:
								$report_summary['installments']['transunion'][$next_sub_head] = $content;
								$bureau_index = 1;
								break;
						}
					}

					if(!empty($next_sub_head) && preg_match('/Other/', $next_head)) {
						/*echo $next_sub_head; die();*/
						switch ($bureau_index) {
							case 1:
								$report_summary['other']['experian'][$next_sub_head] = $content;
								$bureau_index++;
								break;
							case 2:
								$report_summary['other']['equifax'][$next_sub_head] = $content;
								$bureau_index++;
								break;
							case 3:
								$report_summary['other']['transunion'][$next_sub_head] = $content;
								$bureau_index = 1;
								break;
						}
					}

					if(!empty($next_sub_head) && preg_match('/Collections/', $next_head)) {
						/*echo $next_sub_head; die();*/
						switch ($bureau_index) {
							case 1:
								$report_summary['collections']['experian'][$next_sub_head] = $content;
								$bureau_index++;
								break;
							case 2:
								$report_summary['collections']['equifax'][$next_sub_head] = $content;
								$bureau_index++;
								break;
							case 3:
								$report_summary['collections']['transunion'][$next_sub_head] = $content;
								$bureau_index = 1;
								break;
						}
					}

					if(!empty($next_sub_head) && preg_match('/All Accounts/', $next_head)) {
						/*echo $next_sub_head; die();*/
						switch ($bureau_index) {
							case 1:
								$report_summary['all_accounts']['experian'][$next_sub_head] = $content;
								$bureau_index++;
								break;
							case 2:
								$report_summary['all_accounts']['equifax'][$next_sub_head] = $content;
								$bureau_index++;
								break;
							case 3:
								$report_summary['all_accounts']['transunion'][$next_sub_head] = $content;
								$bureau_index = 1;
								break;
						}
					}
				}
			}
			$full_report_summary[] = $report_summary;
		}
		//$this->showResult($full_report_summary);
		return $full_report_summary;
	}

	public function getCreditInquiries($page_start, $page_stop)
	{
		$full_credit_enquires = array();
		for($i = $page_start; $i <= $page_stop; $i++) {
			$dom = $this->getPageDom($i);
			if (!$dom instanceof DOMDocument) {
				log_message('error', "Unable to convert page to DOM Object");
				return false;
			}

		 	$paras = $dom->getElementsByTagName('p');
		 	$credit_enquiries = array();
		 	$tmp_enquiry = array();
		 	$next_sub_head = null;
		 	$index = 0;
		 	$sub_col_count = 0;
		 	if ($paras instanceof DOMNodeList && $paras->length > 0) {
		 		foreach ($paras as $p) {
		 			//Check whther is sub heading - Business Name, Enquiry Date etc
		 			$para = $p->getElementsByTagName('i');
		 			if ($para instanceof DOMNodeList && $para->length) {
		 				$tmp_sub_head = $para[0]->textContent;
		 				$tmp_sub_head = str_replace("Â", "", $tmp_sub_head);
		 				$reg_expr = '/(Business Name|Inquiry Date|Business Type)/';
		 				if (!preg_match($reg_expr, $tmp_sub_head)) {
		 					$address = str_replace("Â", "", $p->textContent);
		 					$tmp_enquiry['address'] =  $address;
		 					continue;
		 				} else {
		 					if (preg_match('/Business Name/', $tmp_sub_head)) {
		 						$next_sub_head =  'business_name';
		 						continue;
		 					} else if(preg_match('/Inquiry Date/', $tmp_sub_head)) {
		 						$next_sub_head =  'inquiry_date';
		 						continue;
		 					} else if(preg_match('/Business Type/', $tmp_sub_head)) {
		 						$next_sub_head =  'business_type';
		 						continue;
		 					}
		 				}				
		 			}


		 			// Check it's a main heading - Inquierer name
		 			$para = $p->getElementsByTagName('b');
		 			if($para instanceof DOMNodeList && $para->length) {
		 				$tmp_head = $para[0]->textContent;
		 				$tmp_head = str_replace("Â", "", $tmp_head);
		 				if (!preg_match('/Credit Inquiries|Bankruptcies/', $tmp_head)) {
		 					$next_sub_head = '';					
		 					if(isset($tmp_enquiry) && is_array($tmp_enquiry) && count($tmp_enquiry)) {
		 						if (count($tmp_enquiry) > 1) {
		 							$credit_enquiries[] = $tmp_enquiry;
		 							$tmp_enquiry = array();	
		 							$index++;
		 							//Max credit report per page is 4;
		 							if($index == 4) {
		 								break;
		 							}
		 						} 						
		 					}
		 					$tmp_enquiry['title'] = $tmp_head;
		 				}
		 				//If true, its the end of credit enquirier section
		 				if (preg_match('/Bankruptcies/', $tmp_head)) {
		 					break;
		 				}

		 				continue;
		 			}

		 			// Check its just a content
		 			if(isset($next_sub_head) && !empty($next_sub_head) && !is_null($next_sub_head)) {
		 				$type = '';
		 				if($p->hasAttribute("style")) {
		 					$pos = $p->getAttribute('style');
		 					if (preg_match('/left:3\d\dpx/', $pos)) {
		 						$type = 'experian';
		 					} else if(preg_match('/left:5\d\dpx/', $pos)) {
		 						$type = 'equifax';
		 					} else if(preg_match('/left:7\d\dpx/', $pos)) {
		 						$type = 'transunion';
		 					} else {
		 						$type = '';
		 					}
		 				}
		 				$content = $p->textContent;
		 				$content = str_replace("Â", "", $content);
		 				if (!preg_match('/Page/', $content) && !empty($type)) {
		 					$tmp_enquiry[$type][$next_sub_head] = $content;	
		 				}
		 				
		 				continue;
		 			}
		 		}
		 	} else {
		 		log_message('error', "CreditInquiries: Page $i, Something goes wrong with given page DOM format.") ;
		 	}
		 	$full_credit_enquires[] = $credit_enquiries;

		}
		return $full_credit_enquires;
	}

	public function getCurrentPastDues($page_start, $page_stop)
	{
		$full_current_past_dues = array();
		for($i = $page_start; $i <= $page_stop; $i++) {
			$dom = $this->getPageDom($i);
			if (!$dom instanceof DOMDocument) {
				log_message('error', "Unable to convert page to DOM Object");
				break;
			}

			try {
				$paras = $dom->getElementsByTagName('p');	
			} catch (Exception $e) {
				log_message('error', 'Unable to parse Credit Cards, Loans & Other Debt page. ' . $e->getMessage());
				break;
			}
			$index = 0;
			$tmp_info = array();
			$current_past_dues = array();
			$is_complete = false;
			if ($paras instanceof DOMNodeList && $paras->length) {
				foreach ($paras as $p) {
					// Main Sub-heading
					if ($p->hasAttribute('class') && $p->getAttribute('class') == 'ft03') {
						$is_complete = false;
						$tmp_head = $p->textContent;
		 				$tmp_head = str_replace("Â", "", $tmp_head);
		 				if(!preg_match('/Payment History Legend/', $tmp_head)) {
		 					$tmp_info['title'] = $tmp_head;
		 					$index++;
		 					continue;	
		 				} else {
		 					log_message('error', "Invalid page");
		 					break;
		 				}
		 				
					}

					if($p->hasAttribute('class') && preg_match('/ft011/', $p->getAttribute('class'))) {
						$address_info = $p->getElementsByTagName('b');
						if($address_info && $address_info->length > 0 ) {
							$address = str_replace("Â", "", $p->textContent);
							$tmp_info['address'] =  $address;	
						}
						continue;
					}

					// Sub heading like Account Name, Type Date Opened ect..
					if ($p->hasAttribute('class') && $p->getAttribute('class') == 'ft04') {
						$tmp_sub_head = str_replace("Â", "", $p->textContent);
						if (preg_match('/Account Name/', $tmp_sub_head)) {
							$next_sub_head =  'account_name';
							continue;
						} else if(preg_match('/Account #/', $tmp_sub_head)) {
							$next_sub_head =  'account';
							continue;
						} else if(preg_match('/Account Type/', $tmp_sub_head)) {
							$next_sub_head =  'account_type';
							continue;
						} else if(preg_match('/Balance/', $tmp_sub_head)) {
							$next_sub_head =  'balance';
							continue;
						} else if(preg_match('/Past Due/', $tmp_sub_head)) {
							$next_sub_head =  'past_due';
							continue;
						} else if(preg_match('/Date Opened/', $tmp_sub_head)) {
							$next_sub_head =  'date_opened';
							continue;
						} else if(preg_match('/Account Status/', $tmp_sub_head)) {
							$next_sub_head =  'account_satus';
							continue;
						} else if(preg_match('/Mo. Payment/', $tmp_sub_head)) {
							$next_sub_head =  'mo_payment';
							continue;
						} else if(preg_match('/Payment Status/', $tmp_sub_head)) {
							$next_sub_head =  'payment_status';
							continue;
						} else if(preg_match('/High Balance/', $tmp_sub_head)) {
							$next_sub_head =  'high_balance';
							continue;
						} else if(preg_match('/Limit/', $tmp_sub_head)) {
							$next_sub_head =  'limit';
							continue;
						} else if(preg_match('/Terms/', $tmp_sub_head)) {
							$next_sub_head =  'terms';
							continue;
						} else if(preg_match('/Comments/', $tmp_sub_head)) {
							$next_sub_head =  'comments';
							continue;
						}
					}

		 			if(isset($next_sub_head) && !empty($next_sub_head) && $p->hasAttribute('class') && $p->getAttribute('class') == 'ft01') {

		 				//identify the buroue
		 				if($p->hasAttribute('style') && preg_match('/left:3\d\dpx/', $p->getAttribute('style'))) {
		 					$tmp_info['experian'][$next_sub_head] = str_replace("Â", "", $p->textContent);
		 				}
		 				if($p->hasAttribute('style') && preg_match('/left:5\d\dpx/', $p->getAttribute('style'))) {
		 					$tmp_info['equifax'][$next_sub_head] = str_replace("Â", "", $p->textContent);
		 				}
		 				if($p->hasAttribute('style') && preg_match('/left:7\d\dpx/', $p->getAttribute('style'))) {
		 					$tmp_info['transunion'][$next_sub_head] = str_replace("Â", "", $p->textContent);
		 				}
		 			}

					if ($p->hasAttribute('class') && $p->getAttribute('class') == 'ft05' && !$is_complete) {
						$current_past_dues[] = $tmp_info;
						$is_complete = true;
						$tmp_info = array();
						//Max entries in a page is 2
						if ($index == 2) {
							break;
						}
					}
				}
			} else {
				log_message('error', 'Something goes wrong. Invalid parsed input.');
			}	 

			if(is_array($current_past_dues) && count($current_past_dues)) {
				$full_current_past_dues = array_merge($full_current_past_dues, $current_past_dues);
				$this->logResults($full_current_past_dues);
			}
		}
		return $full_current_past_dues;
	}

	public function getFicoCreditScores($page_start, $page_stop)
	{
		$full_credit_scores = array();
		for($i = $page_start + 1; $i <= $page_stop; $i++) {
			$dom = $this->getPageDom($i);
			if (!$dom instanceof DOMDocument) {
				log_message('error', "Unable to convert page to DOM Object");
				break;
			}

			try {
				$paras = $dom->getElementsByTagName('p');	
			} catch (Exception $e) {
				log_message('error', 'Unable to parse Credit Cards, Loans & Other Debt page. ' . $e->getMessage());
				break;
			}

			$title = '';
			$tmp_scores = array();
			foreach ($paras as $p) {
				//Title
				if ($p->hasAttribute('class') && $p->getAttribute('class') == 'ft03') {
					$title .= str_replace("Â", "", $p->textContent);
					continue;
				}
				//Scores

				if($p->hasAttribute('class') && ($p->getAttribute('class') == 'ft09' || $p->getAttribute('class') == 'ft08')) {
					$tmp_score = str_replace("Â", "", $p->textContent);
					if(is_numeric($tmp_score)) {				
						if($p->hasAttribute('style') && preg_match('/left:1\d\dpx/', $p->getAttribute('style'))) {	
							$tmp_scores['experian'] = $tmp_score;
							continue;
						}
						if($p->hasAttribute('style') && preg_match('/left:4\d\dpx/', $p->getAttribute('style'))) {
							$tmp_scores['equifax'] = $tmp_score;
							continue;
						}
						if($p->hasAttribute('style') && preg_match('/left:7\d\dpx/', $p->getAttribute('style'))) {
							$tmp_scores['transunion'] = $tmp_score;
							break;
						}
					}
				}
			}
			$tmp_scores['title'] = $title;
			if(is_array($tmp_scores) && count($tmp_scores)) {
				$full_credit_scores[] = $tmp_scores;
			} 
		}
		
		return $full_credit_scores;
	}

	private function showResult($info) {
		echo "<pre>";
		print_r($info);
		die();
	}

	private function logResults($info)
	{
		file_put_contents('temp_file.txt', print_r($info, 1));
	}

}