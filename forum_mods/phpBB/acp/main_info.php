<?php
namespace omnimaga\OmnomIRC\acp;

class main_info
{
	function module()
	{
		return array(
			'filename' => '\omnimaga\OmnomIRC\acp\main_module',
			'title' => 'ACP_OIRC_TITLE',
			'version' => '1.0.0',
			'modes' => array(
				'general' => array(
					'title' => 'ACP_OIRC_GENERAL',
					'auth' => 'ext_omnimaga/OmnomIRC && acl_a_board',
					'cat' => array('ACP_OIRC_TITLE')
				),
				'notifications' => array(
					'title' => 'ACP_OIRC_NOTIFICATIONS',
					'auth' => 'ext_omnimaga/OmnomIRC && acl_a_board',
					'cat' => array('ACP_OIRC_TITLE')
				),
				'checklogin' => array(
					'title' => 'ACP_OIRC_CHECKLOGIN',
					'auth' => 'ext_omnimaga/OmnomIRC && acl_a_board',
					'cat' => array('ACP_OIRC_TITLE')
				),
			),
		);
	}
	
	function install()
	{
		
	}
	
	function uninstall()
	{
		
	}
}

?>