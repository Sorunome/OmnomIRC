<?php

namespace omnimaga\OmnomIRC\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class main_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup' => 'load_language_on_setup',
			'core.page_header' => 'add_oirc_to_header',
		);
	}
	
	protected $helper;
	protected $template;
	
	
	public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template)
	{
		$this->helper = $helper;
		$this->template = $template;
	}
	
	public function load_language_on_setup($event)
	{
		
	}
	public function add_oirc_to_header($event)
	{
		
		$this->template->assign_vars(array(
			'OIRC_SHOW' => true,
			'OIRC_TITLE' => 'OmnomIRC Chat',
			'OIRC_HEIGHT' => 280,
			'OIRC_FRAMEURL' => 'http://192.168.1.13/oirc'
		));
	}
}
?>
