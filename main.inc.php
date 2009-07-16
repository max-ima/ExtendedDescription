<?php
/*
Plugin Name: Extended Description
Version: 2.0.f
Description: Add multilinguale descriptions, banner, NMB, category name, etc...
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=175
Author: P@t & Grum

--------------------------------------------------------------------------------
 history

| date       | release |                                                       
|            | 2.0.c   | P@t
| 2009-04-01 | 2.0.d   | Grum 
|            |         | * bug corrected, markup <!--hidden--> now works again 
|            |         |   on categories name
|            |         | * new functionality, can use a markup <!--hidden-->  
|            |         |   on image's name
|            |         | * new functionality, add a new parameter for the image   
|            |         |   markup [img=] ; possibility to show the image name 
|            |         |   with the "name" parameter
|            |         | * new functionality, the image markup [img=] allows now 
|            |         |   to display more than one image
| 2009-04-30 | 2.0.e   | P@t
|            |         | * bug corrected, avoid errors on categories pages when
|            |         |   $conf['show_thumbnail_caption'] = false
| 2009-05-10 | 2.0.f   | P@t
|            |         | * add possibility to remove a category from menubar
|            |         |   with markup <!--mb-hidden-->
|            |         | 

*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
define('EXTENDED_DESC_PATH' , PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/');
load_language('plugin.lang', EXTENDED_DESC_PATH);

global $conf;

$extdesc_conf = array(
  'more'           => '<!--more-->',
  'complete'       => '<!--complete-->',
  'up-down'        => '<!--up-down-->',
  'not_visible'    => '<!--hidden-->',
  'mb_not_visible' => '<!--mb-hidden-->'
);

$conf['ExtendedDescription'] = isset($conf['ExtendedDescription']) ?
  array_merge($extdesc_conf, $conf['ExtendedDescription']) :
  $extdesc_conf;


// Traite les balises [lang=xx]
function get_user_language_desc($desc)
{
	global $user;
  
	$user_lang = substr($user['language'], 0, 2);

	if (!substr_count(strtolower($desc), '[lang=' . $user_lang . ']'))
	{
		$user_lang = 'default';
  }
  
  if (substr_count(strtolower($desc), '[lang=' . $user_lang . ']'))
	{
    // la balise avec la langue de l'utilisateur a été trouvée
    $patterns[] = '#(^|\[/lang\])(.*?)(\[lang=(' . $user_lang . '|all)\]|$)#is';
    $replacements[] = '';
    $patterns[] = '#\[lang=(' . $user_lang . '|all)\](.*?)\[/lang\]#is';
    $replacements[] = '\\1';
  }
  else
  {
    // la balise avec la langue de l'utilisateur n'a pas été trouvée
    // On prend tout ce qui est hors balise
    $patterns[] = '#\[lang=all\](.*?)\[/lang\]#is';
    $replacements[] = '\\1';
    $patterns[] = '#\[lang=.*\].*\[/lang\]#is';
    $replacements[] = '';
  }
  return preg_replace($patterns, $replacements, $desc);
}

// Traite les autres balises
function get_extended_desc($desc, $param='')
{
	global $conf;
  
  $desc = get_user_language_desc($desc);
  
  // Balises [cat=xx]
  $patterns[] = '#\[cat=(\d*)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : 'get_cat_thumb("$1")';
  
  // Balises [img=xx.yy,xx.yy,xx.yy;float;name]
  //$patterns[] = '#\[img=(\d*)\.?(\d*|);?(left|right|);?(name|)\]#ie';
  $patterns[] = '#\[img=([\d\s\.]*);?(left|right|);?(name|)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : 'get_img_thumb("$1", "$2", "$3")';

  
  // Balises <!--complete-->, <!--more--> et <!--up-down-->
	switch ($param)
	{
		case 'subcatify_category_description' :
      $patterns[] = '#^(.*?)('. preg_quote($conf['ExtendedDescription']['complete']) . '|' . preg_quote($conf['ExtendedDescription']['more']) . '|' . preg_quote($conf['ExtendedDescription']['up-down']) . ').*$#is';
      $replacements[] = '$1';
      $desc = preg_replace($patterns, $replacements, $desc);
      break;
			
    case 'main_page_category_description' :
      $patterns[] = '#^.*' . preg_quote($conf['ExtendedDescription']['complete']) . '|' . preg_quote($conf['ExtendedDescription']['more']) . '#is';
      $replacements[] = '';
      $desc = preg_replace($patterns, $replacements, $desc);
      if (substr_count($desc, $conf['ExtendedDescription']['up-down']))
      {
        list($conf['ExtendedDescription']['top_comment'], $desc) = explode($conf['ExtendedDescription']['up-down'], $desc);
        add_event_handler('loc_end_index', 'add_top_description');
      }
      break;
      
    default:
      $desc = preg_replace($patterns, $replacements, $desc);
	}

  return $desc;
}

function extended_desc_mail_group_assign_vars($assign_vars)
{
	if (isset($assign_vars['CPL_CONTENT']))
	{
		$assign_vars['CPL_CONTENT'] = get_extended_desc($assign_vars['CPL_CONTENT']);
	}
	return $assign_vars;
}

// Add top description
function add_top_description()
{
  global $template, $conf;
  $template->concat('PLUGIN_INDEX_CONTENT_BEGIN', '
    <div class="additional_info">
    ' . $conf['ExtendedDescription']['top_comment'] . '
    </div>');
}

// Remove a category
function ext_remove_cat($tpl_var, $categories)
{
  global $conf;

  $i=0;
  while($i<count($tpl_var))
  {
    if (substr_count($tpl_var[$i]['NAME'], $conf['ExtendedDescription']['not_visible']))
    {
      array_splice($tpl_var, $i, 1);
    }
    else
    {
      $i++;
    }
  }

  return $tpl_var;
}

// Remove a category from menubar
function ext_remove_menubar_cats($where)
{
  global $conf;

  $query = 'SELECT id, uppercats
    FROM '.CATEGORIES_TABLE.'
    WHERE name LIKE "%'.$conf['ExtendedDescription']['mb_not_visible'].'%"';

  $result = pwg_query($query);
  while ($row = mysql_fetch_assoc($result))
  {
    $ids[] = $row['id'];
    $where .= '
      AND uppercats NOT LIKE "'.$row['uppercats'].',%"';
  }
  if (!empty($ids))
  {
    $where .= '
      AND id NOT IN ('.implode(',', $ids).')';
  }
  return $where;
}

// Remove an image
function ext_remove_image($tpl_var, $pictures)
{
  global $conf;

  $i=0;
  while($i<count($tpl_var))
  {
    if (substr_count($pictures[$i]['name'], $conf['ExtendedDescription']['not_visible']))
    {
      array_splice($tpl_var, $i, 1);
      array_splice($pictures, $i, 1);
    }
    else
    {
      $i++;
    }
  }

  return $tpl_var;
}

// Return html code for  caterogy thumb
function get_cat_thumb($elem_id)
{
  global $template;

  $query = 'SELECT cat.id, cat.name, cat.comment, cat.representative_picture_id, cat.permalink,
            uc.nb_images, uc.count_images, uc.count_categories,
            img.path, img.tn_ext
            FROM ' . CATEGORIES_TABLE . ' AS cat
            INNER JOIN '.USER_CACHE_CATEGORIES_TABLE.' as uc
            INNER JOIN ' . IMAGES_TABLE . ' AS img
            ON cat.id = uc.cat_id
            AND img.id = cat.representative_picture_id
            WHERE cat.id = ' . $elem_id . ';';
  $result = pwg_query($query);

  if($result)
  {
    $template->set_filename('extended_description_content', dirname(__FILE__) . '/template/cat.tpl');
    while($category=mysql_fetch_array($result))
    {
      $template->assign(array(
          'ID'    => $category['id'],
          'TN_SRC'   => get_thumbnail_url($category),
          'TN_ALT'   => strip_tags($category['name']),
          'URL'   => make_index_url(array('category' => $category)),
          'CAPTION_NB_IMAGES' => get_display_images_count
                                  (
                                    $category['nb_images'],
                                    $category['count_images'],
                                    $category['count_categories'],
                                    true,
                                    '<br />'
                                  ),
          'DESCRIPTION' =>
            trigger_event('render_category_literal_description',
              trigger_event('render_category_description',
                @$category['comment'],
                'subcatify_category_description')),
          'NAME'  => trigger_event(
                       'render_category_name',
                       $category['name'],
                       'subcatify_category_name'
                     ),
        ));
    }
    return $template->parse('extended_description_content', true);
  }
  return '';
}

// Return html code for img thumb
//function get_img_thumb($elem_id, $cat_id='', $align='', $name='')
function get_img_thumb($elem_ids, $align='', $name='')
{
  global $template;

  $ids=explode(" ",$elem_ids);
  $assoc = array();
  foreach($ids as $key=>$val)
  {    
    list($a,$b)=array_pad(explode(".",$val),2,"");
    $assoc[0][]=$a;
    $assoc[1][]=$b;
  }

  $query = 'SELECT * FROM ' . IMAGES_TABLE . ' WHERE id in (' . implode(",",$assoc[0]). ');';
  $result = pwg_query($query);
  
  if($result)
  {
    $template->set_filename('extended_description_content', dirname(__FILE__) . '/template/img.tpl');

    $imglist=array();
    while ($picture = mysql_fetch_array($result))
    {
      $imglist[$picture["id"]]=$picture;
    }

    $img=array();
    for($i=0;$i<count($assoc[0]);$i++)
    {
      if (!empty($assoc[1][$i]))
      {
        $url = make_picture_url(array(
          'image_id' => $imglist[$assoc[0][$i]]['id'],
          'category' => array(
            'id' => $assoc[1][$i],
            'name' => '',
            'permalink' => '')));
      }
      else
      {
        $url = make_picture_url(array('image_id' => $imglist[$assoc[0][$i]]['id']));
      }

      $img[]=array(
          'IMAGE'       => get_thumbnail_url($imglist[$assoc[0][$i]]),
          'IMAGE_ALT'   => $imglist[$assoc[0][$i]]['file'],
          'IMG_TITLE'   => get_thumbnail_title($imglist[$assoc[0][$i]]),
          'U_IMG_LINK'  => $url,
          'LEGEND'  => ($name!="")?$imglist[$assoc[0][$i]]['name']:"",
          'FLOAT' => !empty($align) ? 'float: ' . $align . ';' : '',
          'COMMENT' => $imglist[$assoc[0][$i]]['file']);
      

    }
     $template->assign('img', $img);
    return $template->parse('extended_description_content', true);
  }
  return '';
}


if (script_basename() == 'admin' or script_basename() == 'popuphelp')
{
  include(EXTENDED_DESC_PATH . 'admin.inc.php');
}

add_event_handler ('render_page_banner', 'get_user_language_desc');
add_event_handler ('render_category_name', 'get_user_language_desc');
add_event_handler ('render_category_description', 'get_extended_desc', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);
add_event_handler ('render_element_description', 'get_extended_desc');
add_event_handler ('nbm_render_user_customize_mail_content', 'get_extended_desc');
add_event_handler ('mail_group_assign_vars', 'extended_desc_mail_group_assign_vars');
add_event_handler ('loc_end_index_category_thumbnails', 'ext_remove_cat', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);
add_event_handler ('loc_end_index_thumbnails', 'ext_remove_image', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);
add_event_handler ('get_categories_menu_sql_where', 'ext_remove_menubar_cats');
?>