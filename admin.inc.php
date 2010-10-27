<?php

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

add_event_handler('get_popup_help_content', 'extended_desc_popup', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);
add_event_handler('loc_end_page_tail', 'add_ed_js');

function add_ed_js()
{
  global $page, $template;
  
  $change = array('cat_modify', 'picture_modify', 'configuration', 'notification_by_mail');

  if (!isset($page['page']) or !in_array($page['page'], $change)) return;

  load_language('plugin.lang', EXTENDED_DESC_PATH);
  
  $template->assign('ed_page', $page['page']);
  $template->set_filename('add_ed_popup', dirname(__FILE__) . '/template/add_popup.tpl');
  $template->append('footer_elements', $template->parse('add_ed_popup', true));
}

function  extended_desc_popup($help_content, $get)
{
  if ($get == 'extended_desc')
  {
    $help_content = load_language('help.html', EXTENDED_DESC_PATH, array('return'=>true));
  }
  return $help_content;
}


?>