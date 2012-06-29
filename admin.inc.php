<?php

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

add_event_handler('get_popup_help_content', 'extended_desc_popup', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);
add_event_handler('loc_begin_admin_page', 'add_ed_help');

function add_ed_help()
{
  global $page, $template;
  
  $target = null;
  switch ($page['page'])
  {
    case 'album':
      $target = 'album_properties';
      break;
    case 'photo':
      $target = 'picture_modify';
      break;
    case 'configuration':
      $target = 'config';
      break;
    case 'notification_by_mail':
      $target = 'notification_by_mail';
      break;
    case 'plugin':
      if ($_GET['section'] == 'AdditionalPages/admin.php') $target = 'plugin_admin_content';
      if ($_GET['section'] == 'header_manager/admin.php')  $target = 'header_manager';
      break;
  }
  
  if (!empty($target))
  {
    load_language('plugin.lang', EXTENDED_DESC_PATH);
    $template->set_prefilter($target, 'add_ed_help_prefilter');
  }
}

function add_ed_help_prefilter($content)
{
  global $template;
  $themeconf = $template->get_template_vars('themeconf');
  
  $search = '</textarea>';
  $add = '
{combine_script id=\'core.scripts\' load=\'async\' path=\'themes/default/js/scripts.js\'}
<a href="./admin/popuphelp.php?page=extended_desc" onclick="popuphelp(this.href); return false;" title="'.l10n('ExtendedDesc_help').'" style="vertical-align: middle; border: 0; margin: 0.5em;"><img src="'.$themeconf['admin_icon_dir'].'/help.png" class="button" alt="'.l10n('ExtendedDesc_help').'"></a>';
  
  return str_replace($search, $search.$add, $content);
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