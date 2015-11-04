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
			'core.posting_modify_submit_post_after' => 'report_post',
		);
	}
	
	
	private function getOircConfig()
	{
		global $oirc_config,$only_include_oirc;
		$only_include_oirc = true;
		include_once(realpath(dirname(__FILE__)).'/../checkLogin/index.php');
	}
	
	private function generateOircSigURL()
	{
		global $oirc_config;
		$this->getOircConfig();
		
		$nick = '*';
		$time = (string)(time() - 60*60*24 + 60); // the sig key is only valid for one min!
		$uid = rand();
		$network = 0;
		$signature = $time.'|'.hash_hmac('sha512',$nick.$uid,$network.$oirc_config['sigKey'].$time);
		return 'nick='.base64_url_encode($nick).'&signature='.base64_url_encode($signature).'&time='.$time.'&network='.$network.'&id='.$uid.'&noLoginErrors&serverident';
	}
	
	private function sendToOmnomIRC($message,$channel)
	{
		global $oirc_config;
		$sigurl = $this->generateOircSigURL();
		file_get_contents($oirc_config['oircUrl'].'/message.php?message='.base64_url_encode($message).'&channel='.$channel.'&serverident&'.$sigurl);
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
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'omnimaga/OmnomIRC',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
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
	public function report_post($event)
	{
		global $user;
		
		$data = $event['data'];
		
		header('Content-Type:text/plain');
		var_dump($event['data']);
		die();
		$this->sendToOmnomIRC(strtr($oirc_postnotification,array(
			'{COLOR}' => "\x03",
			'{NAME}' => $user->data['username'],
			'{TOPIC}' => $data['topic_title'],
			'{SUBJECT}' => $data['post_subject'],
			'{TOPICID}' => $data['topic_id'],
			'{POSTID}' => $data['post_id']
		)),0);
	}
}
?>
