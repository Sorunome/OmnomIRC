<?php

namespace sorunome\OmnomIRC\controller;

class main
{
	protected $config;
	protected $helper;
	protected $template;
	protected $user;
	public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
	}
	
}

?>