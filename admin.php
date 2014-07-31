<?php
defined('EXTENDED_DESC_PATH') or die('Hacking attempt!');

global $template, $page;
load_language('plugin.lang', EXTENDED_DESC_PATH);

$page['infos'][] = l10n('Extended Description have been successfully installed. Now you can use all its features in most text boxes of Piwigo.');



$template->assign(array(
  'EXTENDED_DESC_PATH' => EXTENDED_DESC_PATH,
  'EXTDESC_HELP' => array(
    'lang' =>       load_language('help.lang.html', EXTENDED_DESC_PATH, array('return'=>true)),
    'extdesc' =>    load_language('help.extdesc.html', EXTENDED_DESC_PATH, array('return'=>true)),
    'cat_photo' =>  load_language('help.cat_photo.html', EXTENDED_DESC_PATH, array('return'=>true)),
    'slider' =>     load_language('help.slider.html', EXTENDED_DESC_PATH, array('return'=>true)),
    'hide' =>       load_language('help.hide.html', EXTENDED_DESC_PATH, array('return'=>true)),
    'redirect' =>   load_language('help.redirect.html', EXTENDED_DESC_PATH, array('return'=>true)),
    'logged' =>     load_language('help.logged.html', EXTENDED_DESC_PATH, array('return'=>true)),
    ),
  ));

$template->set_filename('extdesc', realpath(EXTENDED_DESC_PATH . 'template/admin.tpl'));
$template->assign_var_from_handle('ADMIN_CONTENT', 'extdesc');
