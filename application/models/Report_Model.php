<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Report_model extends CI_Model
{
	public function get_all_report($user_id = 1001)
	{
		$reports = $this->db->get_where('report_documents', array('user_id' => $user_id))->result_array();
		print_r($reports);
		die();
	}

	public function get_report_type_info($report_name = '')
	{
		$retport_info = $this->db->get_where('report_types', array('type LIKE' => $report_name))->row();
		return $retport_info;
	}
}