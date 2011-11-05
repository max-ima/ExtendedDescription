<?php
/*
Plugin Name: Extended Description
Version: auto
Description: Add multilinguale descriptions, banner, NMB, category name, etc...
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=175
Author: P@t & Grum
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
function get_user_language_desc($desc, $user_lang=null)
{
  if (is_null($user_lang))
  {
    global $user;
    $user_lang = substr($user['language'], 0, 2);
  }

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

function get_user_language_tag_url($tag)
{
  return get_user_language_desc($tag, get_default_language());
}

// Traite les autres balises
function get_extended_desc($desc, $param='')
{
  global $conf, $page;

  if ($param == 'main_page_category_description' and isset($page['category']) and !isset($page['image_id']) and preg_match('#\[redirect (.*?)\]#i', $desc, $m1))
  {
    if (preg_match('#^(img|cat|search)=(\d*)\.?(\d*|)$#i', $m1[1], $m2))
    {
      switch ($m2[1])
      {
        case 'img':
        $url_params = array('image_id' => $m2[2]);
        if (!empty($m2[3]))
        {
          $url_params['category'] = array('id' => $m2[3], 'name' => '', 'permalink' => '');
        }
        $url = rtrim(make_picture_url($url_params), '-');
        break;

        case 'cat':
        $url_params = array('category' => array('id' => $m2[2], 'name' => '', 'permalink' => ''));
        $url = rtrim(make_index_url($url_params), '-');
        break;

        case 'search':
        $url = make_index_url(array('section' => 'search', 'search' => $m2[2]));
      }
    }
    else
    {
      $url = $m1[1];
    }
    if (is_admin())
    {
      global $header_notes;
      $header_notes[] = sprintf(l10n('This category is redirected to %s'), '<a href="'.$url.'">'.$url.'</a>');
    }
    else
    {
      redirect($url);
    }
  }

  $desc = get_user_language_desc($desc);

  // Remove redirect tags for subcatify_category_description
  $patterns[] = '#\[redirect .*?\]#i';
  $replacements[] = ''; 

  // Balises [cat=xx]
  $patterns[] = '#\[cat=(\d*)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : 'get_cat_thumb("$1")';

  // Balises [img=xx.yy,xx.yy,xx.yy;left|rigtht|;name|titleName|]
  //$patterns[] = '#\[img=(\d*)\.?(\d*|);?(left|right|);?(name|titleName|)\]#ie';
  $patterns[] = '#\[img=([\d\s\.]*);?(left|right|);?(name|titleName|)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : 'get_img_thumb("$1", "$2", "$3")';

    // [random album=xx]
    $patterns[] = '#\[random\s+(?:album|cat)=\s*?(\d+)\s*?\]#ie';
    $replacements[] = 'extdesc_get_random_photo("$1")';

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
        list($desc, $conf['ExtendedDescription']['bottom_comment']) = explode($conf['ExtendedDescription']['up-down'], $desc);
        add_event_handler('loc_end_index', 'add_bottom_description');
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

// Add bottom description
function add_bottom_description()
{
  global $template, $conf;
  $template->concat('PLUGIN_INDEX_CONTENT_END', '
    <div class="additional_info">
    ' . $conf['ExtendedDescription']['bottom_comment'] . '
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
  global $template, $user;

  $query = 'SELECT 
cat.id, cat.name, cat.comment, cat.representative_picture_id, cat.permalink, uc.nb_images, uc.count_images, uc.count_categories, img.path, img.tn_ext
FROM ' . CATEGORIES_TABLE . ' AS cat
INNER JOIN '.USER_CACHE_CATEGORIES_TABLE.' as uc
  ON cat.id = uc.cat_id AND user_id = '.$user['id'].'
INNER JOIN ' . IMAGES_TABLE . ' AS img
  ON img.id = uc.user_representative_picture_id
WHERE cat.id = ' . $elem_id . ';';
  $result = pwg_query($query);

  if($result and $category = mysql_fetch_array($result))
  {
    $template->set_filename('extended_description_content', dirname(__FILE__) . '/template/cat.tpl');

    $template->assign(
      array(
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
      )
    );

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
          'ID'          => $imglist[$assoc[0][$i]]['id'],
          'IMAGE'       => get_thumbnail_url($imglist[$assoc[0][$i]]),
          'IMAGE_ALT'   => $imglist[$assoc[0][$i]]['file'],
          'IMG_TITLE'   => ($name=="titleName")?htmlspecialchars($imglist[$assoc[0][$i]]['name'], ENT_QUOTES):get_thumbnail_title($imglist[$assoc[0][$i]]),
          'U_IMG_LINK'  => $url,
          'LEGEND'  => ($name=="name")?$imglist[$assoc[0][$i]]['name']:"",
          'FLOAT' => !empty($align) ? 'float: ' . $align . ';' : '',
          'COMMENT' => $imglist[$assoc[0][$i]]['file']);


    }
     $template->assign('img', $img);
    return $template->parse('extended_description_content', true);
  }
  return '';
}


function extdesc_get_random_photo($category_id)
{
  include_once(PHPWG_ROOT_PATH.'include/functions_picture.inc.php');
  
  $query = '
SELECT
    id,
    path
  FROM '.IMAGES_TABLE.'
    JOIN '.IMAGE_CATEGORY_TABLE.' ON image_id = id
  WHERE category_id = '.$category_id.'
  ORDER BY '.DB_RANDOM_FUNCTION.'()
  LIMIT 1
;';
  $result = pwg_query($query);
  while ($row = pwg_db_fetch_assoc($result))
  {
    return '<img src="'.get_element_url($row).'">';
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
add_event_handler ('render_tag_name', 'get_user_language_desc');
add_event_handler ('render_tag_url', 'get_user_language_tag_url', 40);
add_event_handler ('render_element_description', 'get_extended_desc');
add_event_handler ('nbm_render_user_customize_mail_content', 'get_extended_desc');
add_event_handler ('mail_group_assign_vars', 'extended_desc_mail_group_assign_vars');
add_event_handler ('loc_end_index_category_thumbnails', 'ext_remove_cat', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);
add_event_handler ('loc_end_index_thumbnails', 'ext_remove_image', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);
add_event_handler ('get_categories_menu_sql_where', 'ext_remove_menubar_cats');
?>
