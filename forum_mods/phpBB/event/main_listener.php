<?php

namespace omnimaga\OmnomIRC\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class main_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup' => 'load_language_on_setup',
			'core.permissions' => 'add_permissions',
			'core.page_header' => 'add_oirc_to_header',
			'core.posting_modify_submit_post_after' => 'report_post',
			'core.acp_manage_forums_display_form' => 'acp_generate_forum_data',
			'core.acp_manage_forums_validate_data' => 'acp_validate_forum_data',
			'core.approve_posts_after' => 'approve_post',
			'core.approve_topics_after' => 'approve_topic'
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
	public function add_permissions($event)
	{
		$permissions = $event['permissions'];
		$permissions['u_oirc_view'] = array('lang' => 'ACL_U_OIRC_VIEW', 'cat' => 'misc');
		$permissions['m_oirc_op'] = array('lang' => 'ACL_M_OIRC_OP', 'cat' => 'misc');
		$event['permissions'] = $permissions;
	}
	public function add_oirc_to_header($event)
	{
		global $config,$auth,$user,$phpbb_container,$request;
		$show = $auth->acl_get('u_oirc_view');
		if($show)
		{
			$data = unserialize($user->data['oirc_pages']);
			if(empty($data))
			{
				$data = unserialize($phpbb_container->get('config_text')->get('oirc_pages'));
			}// PHP_SELF SCRIPT_FILENAME
			header('Content-Type:text/plain');
			$page = substr(basename($request->variable('PHP_SELF',$request->variable('SCRIPT_FILENAME','index.php',false,\phpbb\request\request_interface::SERVER),false,\phpbb\request\request_interface::SERVER)),0,-4);
			$show = !isset($data[$page]) || $data[$page];
		}
		$this->template->assign_vars(array(
			'OIRC_SHOW' => $show,
			'OIRC_TITLE' => $config['oirc_title'],
			'OIRC_HEIGHT' => $config['oirc_height'],
			'OIRC_FRAMEURL' => $config['oirc_frameurl'],
			'OIRC_DOMAIN' => $config['oirc_domain']
		));
	}
	public function report_post($event)
	{
		global $user,$config,$db;
		
		$chan = $event['post_data']['oirc_chan'];
		
		if(!$chan || $chan == '' || $chan == -1)
		{
			return;
		}
		if($chan == '00')
		{
			$chan = 0;
		}
		
		$data = $event['data'];
		
		$sql = $db->sql_build_query('SELECT', array(
			'SELECT' => 'post_visibility',
			'FROM' => array(POSTS_TABLE => 'p'),
			'WHERE' => $db->sql_in_set('post_id',$data['post_id'])
		));
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		
		if(!$row['post_visibility']){
			return;
		}
		if($data['topic_first_post_id'] != 0)
		{
			if(!$data['post_edit_user'])
			{
				if($config['oirc_posts'])
				{
					$this->sendToOmnomIRC(strtr($config['oirc_postnotification'],array(
						'{COLOR}' => "\x03",
						'{NAME}' => $user->data['username'],
						'{TOPIC}' => $data['topic_title'],
						'{SUBJECT}' => $data['post_subject'],
						'{TOPICID}' => $data['topic_id'],
						'{POSTID}' => $data['post_id']
					)),$chan);
				}
			}
			else
			{
				if($config['oirc_edits'])
				{
					$this->sendToOmnomIRC(strtr($config['oirc_editnotification'],array(
						'{COLOR}' => "\x03",
						'{NAME}' => $user->data['username'],
						'{TOPIC}' => $data['topic_title'],
						'{SUBJECT}' => $data['post_subject'],
						'{TOPICID}' => $data['topic_id'],
						'{POSTID}' => $data['post_id']
					)),$chan);
				}
			}
		}
		else
		{
			if($config['oirc_topics'])
			{
				$this->sendToOmnomIRC(strtr($config['oirc_topicnotification'],array(
					'{COLOR}' => "\x03",
					'{NAME}' => $user->data['username'],
					'{TOPIC}' => $data['topic_title'],
					'{SUBJECT}' => $data['post_subject'],
					'{TOPICID}' => $data['topic_id'],
					'{POSTID}' => $data['post_id']
				)),$chan);
			}
		}
	}
	public function approve_post($event)
	{
		global $config;
		$data = $event->get_data();
		if($data['action'] == 'approve')
		{
			foreach($data['post_info'] as $post)
			{
				$chan = $post['oirc_chan'];
				if(!$chan || $chan == '' || $chan == -1)
				{
					continue;
				}
				if($chan == '00')
				{
					$chan = 0;
				}
				if($post['post_id'] == $post['topic_first_post_id']) // we report the topic
				{
					if($config['oirc_topics'])
					{
						$this->sendToOmnomIRC(strtr($config['oirc_topicnotification'],array(
							'{COLOR}' => "\x03",
							'{NAME}' => $post['username'],
							'{TOPIC}' => $post['topic_title'],
							'{SUBJECT}' => $post['post_subject'],
							'{TOPICID}' => $post['topic_id'],
							'{POSTID}' => $post['post_id']
						)),$chan);
					}
				}
				else
				{
					if($config['oirc_posts'])
					{
						$this->sendToOmnomIRC(strtr($config['oirc_postnotification'],array(
							'{COLOR}' => "\x03",
							'{NAME}' => $post['username'],
							'{TOPIC}' => $post['topic_title'],
							'{SUBJECT}' => $post['post_subject'],
							'{TOPICID}' => $post['topic_id'],
							'{POSTID}' => $post['post_id']
						)),$chan);
					}
				}
			}
		}
	}
	public function approve_topic($event)
	{
		global $config;
		if(!$config['oirc_topics'])
		{
			return;
		}
		$data = $event->get_data();
		foreach($data['topic_info'] as $topic)
		{
			$chan = $topic['oirc_chan'];
			if(!$chan || $chan == '' || $chan == -1)
			{
				continue;
			}
			if($chan == '00')
			{
				$chan = 0;
			}
			$this->sendToOmnomIRC(strtr($config['oirc_topicnotification'],array(
				'{COLOR}' => "\x03",
				'{NAME}' => $topic['topic_first_poster_name'],
				'{TOPIC}' => $topic['topic_title'],
				'{SUBJECT}' => $topic['topic_title'],
				'{TOPICID}' => $topic['topic_id'],
				'{POSTID}' => $topic['topic_first_post_id']
			)),$chan);
		}
	}
	public function acp_generate_forum_data($event)
	{
		global $user;
		
		$data = $event->get_data();
		$chan = $data['forum_data']['oirc_chan'];
		if(empty($chan))
		{
			$chan = -1;
		}
		
		global $oirc_config;
		$sigurl = $this->generateOircSigURL();
		$s = file_get_contents($oirc_config['oircUrl'].'/config.php?channels&'.$sigurl);
		$foundChan = $chan == -1;
		$chanlist = array(
			-1 => $user->lang('ACP_OIRC_NO_NOT')
		);
		if($s != '')
		{
			$s = json_decode($s,true);
			if($s !== NULL && !empty($s['channels']))
			{
				foreach($s['channels'] as $i => $n)
				{
					if((string)$i == $chan)
					{
						$foundChan = true;
					}
					$chanlist[$i] = $n;
				}
			}
		}
		$chanother = '';
		if(!$foundChan)
		{
			$chanother = $chan;
			$chan = -2;
		}
		$chanlist[-2] = $user->lang('ACP_OIRC_OTHER_NOT');
		
		$chanpickerhtml = '<select id="oirc_chan" name="oirc_chan">';
		foreach($chanlist as $i => $n)
		{
			$chanpickerhtml .= '<option value="'.$i.'"'.($i == $chan?' selected="selected"':'').'>'.htmlspecialchars($n).'</option>';
		}
		$chanpickerhtml .= '</select> <input id="oirc_chan_other" '.($chan != -2?'style="display:none;"':'').' type="text" name="oirc_chan_other" value="'.htmlspecialchars($chanother).'" />';
		$chanpickerhtml .= '<script type="text/javascript">
			document.getElementById("oirc_chan").addEventListener("change",function(){
				document.getElementById("oirc_chan_other").style.display = this.value == -2?"":"none";
			},false);
		</script>';
		
		$data['template_data']['OIRC_CHANPICKERHTML'] = $chanpickerhtml;
		
		$event->set_data($data);
	}
	function acp_validate_forum_data($event)
	{
		global $request;
		$data = $event->get_data();
		
		$chan = (string)$request->variable('oirc_chan',-1);
		$otherchan = $request->variable('oirc_chan_other','');
		if($chan == -2)
		{
			$chan = $otherchan;
		}
		if($chan == 0)
		{
			$chan = '00';
		}
		
		$data['forum_data']['oirc_chan'] = $chan;
		$event->set_data($data);
	}
}
?>
