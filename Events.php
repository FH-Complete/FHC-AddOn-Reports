<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use CI3_Events as Events;

Events::on('widget_report_link', function ($result, $statistik) {
	$CI =& get_instance();

	if (!$CI->permissionlib->isBerechtigt('addon/reports', 's')) {
		$result->cis4link = '';
		return;
	}

	$res = $CI->StatistikModel->db->get_where(
		'addon.tbl_rp_gruppenzuordnung',
		[ 'statistik_kurzbz' => $statistik->statistik_kurzbz ]
	);
	
	if (!$res) {
		$result->cis4link = '';
		return;
	}

	$rows = $res->result();

	if (!count($rows)) {
		$result->cis4link = '';
		return;
	}

	$reportgruppe_id = current($rows)->reportgruppe_id;

	$result->cis4link = base_url('addons/reports/cis/index.php?reportgruppe_id=' . $reportgruppe_id);
});
