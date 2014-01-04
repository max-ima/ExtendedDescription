<?php
defined('EXTENDED_DESC_PATH') or die('Hacking attempt!');

/**
 * Main help page on plugins list
 */
function extdesc_admin_menu($menu) 
{
  $menu[] = array(
    'NAME' => 'Extended Description',
    'URL' => get_root_url() . 'admin.php?page=plugin-ExtendedDescription',
    );
  return $menu;
}

/**
 * Add link to help popup
 */
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
<a href="./admin/popuphelp.php?page=extended_desc" onclick="popuphelp(this.href); return false;" title="'.l10n('Use Extended Description tags...').'" style="vertical-align: middle; border: 0; margin: 0.5em;"><img src="'.$themeconf['admin_icon_dir'].'/help.png" class="button" alt="'.l10n('Use Extended Description tags...').'"></a>';

  return str_replace($search, $search.$add, $content);
}


/**
 * Display popup content
 */
function extended_desc_popup($help_content, $get)
{
  if ($get == 'extended_desc')
  {
    global $template;

    load_language('plugin.lang', EXTENDED_DESC_PATH);

    $template->assign(array(
      'EXTENDED_DESC_PATH' => EXTENDED_DESC_PATH,
      'EXTDESC_HELP' => array(
        'lang' =>       load_language('help.lang.html', EXTENDED_DESC_PATH, array('return'=>true)),
        'extdesc' =>    load_language('help.extdesc.html', EXTENDED_DESC_PATH, array('return'=>true)),
        'cat_photo' =>  load_language('help.cat_photo.html', EXTENDED_DESC_PATH, array('return'=>true)),
        'slider' =>     load_language('help.slider.html', EXTENDED_DESC_PATH, array('return'=>true)),
        'hide' =>       load_language('help.hide.html', EXTENDED_DESC_PATH, array('return'=>true)),
        'redirect' =>   load_language('help.redirect.html', EXTENDED_DESC_PATH, array('return'=>true)),
        ),
      ));

    $template->set_filename('extdesc', realpath(EXTENDED_DESC_PATH . 'template/admin.tpl'));
    
    $help_content = '<h2>Extended Description</h2>' . $template->parse('extdesc', true);
  }
  return $help_content;
}
