<?php
namespace omnimaga\OmnomIRC\ucp;

class main_info
{
	function module()
	{
		return array(
			'filename' => '\omnimaga\OmnomIRC\ucp\main_module',
			'title' => 'UCP_OIRC_SETTINGS',
			'version' => '1.0.0',
			'modes' => array(
				'settings' => array(
					'title' => 'UCP_OIRC_SETTINGS',
					'auth' => 'ext_omnimaga/OmnomIRC && acl_u_chgprofileinfo',
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