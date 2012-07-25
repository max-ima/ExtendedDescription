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

  // Balises [img=xx.yy,xx.yy,xx.yy;left|right|;name|titleName|]
  //$patterns[] = '#\[img=(\d*)\.?(\d*|);?(left|right|);?(name|titleName|)\]#ie';
  $patterns[] = '#\[img=([\d\s\.,]*);?(left|right|);?(name|titleName|)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : 'get_img_thumb("$1", "$2", "$3")';
  
  // Balises [photo id=xx album=yy size=SQ|TH|XXS|XS|S|M|L|XL|XXL html=yes|no link=yes|no]
  // $patterns[] = '#\[photo(?:(?:\s+(id)=(\d+))|(?:\s+(album)=(\d+))|(?:\s+(size)=(SQ|TH|XXS|XS|S|M|L|XL|XXL))|(?:\s+(html)=(yes|no))|(?:\s+(link)=(yes|no))){1,5}\s*\]#ie'; //10
  $patterns[] = '#\[photo ([^\]]+)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : 'get_photo_sized("$1")';

  // Balises [random album=xx size=SQ|TH|XXS|XS|S|M|L|XL|XXL html=yes|no link=yes|no]
  // $patterns[] = '#\[random(?:(?:\s+(album|cat)=(\d+))|(?:\s+(size)=(SQ|TH|XXS|XS|S|M|L|XL|XXL))|(?:\s+(html)=(yes|no))|(?:\s+(link)=(yes|no))){1,4}\s*\]#ie'; //8
  $patterns[] = '#\[random ([^\]]+)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : 'extdesc_get_random_photo("$1")';
  
  // Balises [slider album=xx nb_images=yy random=yes|no list=aa,bb,cc size=SQ|TH|XXS|XS|S|M|L|XL|XXL speed=z title=yes|no effect=... arrows=yes|no control=yes|no elastic=yes|no]
  // $patterns[] = '#\[slider(?:(?:\s+(album)=(\d+))|(?:\s+(nb_images)=(\d+))|(?:\s+(random)=(yes|no))|(?:\s+(list)=([\d,]+))|(?:\s+(size)=(SQ|TH|XXS|XS|S|M|L|XL|XXL))|(?:\s+(speed)=(\d+))|(?:\s+(title)=(yes|no))|(?:\s+(effect)=([a-zA-Z]+))|(?:\s+(arrows)=(yes|no))|(?:\s+(control)=(yes|no))|(?:\s+(elastic)=(yes|no))){1,11}\s*\]#ie'; //22
  $patterns[] = '#\[slider ([^\]]+)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : 'get_slider("$1")';

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

  $query = '
SELECT 
  cat.id, 
  cat.name, 
  cat.comment, 
  cat.representative_picture_id, 
  cat.permalink, 
  uc.nb_images, 
  uc.count_images, 
  uc.count_categories, 
  img.path
FROM ' . CATEGORIES_TABLE . ' AS cat
  INNER JOIN '.USER_CACHE_CATEGORIES_TABLE.' as uc
    ON cat.id = uc.cat_id AND uc.user_id = '.$user['id'].'
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
        'TN_SRC'   => DerivativeImage::thumb_url(array(
                                  'id' => $category['representative_picture_id'],
                                  'path' => $category['path'],
                                )),
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
    $assoc[$a] = $b;
  }

  $query = 'SELECT * FROM ' . IMAGES_TABLE . ' WHERE id in (' . implode(',', array_keys($assoc)) . ');';
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
    foreach ($imglist as $id => $picture)
    {
      if (!empty($assoc[$id]))
      {
        $url = make_picture_url(array(
          'image_id' => $picture['id'],
          'category' => array(
            'id' => $assoc[$id],
            'name' => '',
            'permalink' => '')));
      }
      else
      {
        $url = make_picture_url(array('image_id' => $picture['id']));
      }
      
      $img[]=array(
          'ID'          => $picture['id'],
          'IMAGE'       => DerivativeImage::thumb_url($picture),
          'IMAGE_ALT'   => $picture['file'],
          'IMG_TITLE'   => ($name=="titleName")?htmlspecialchars($picture['name'], ENT_QUOTES):get_thumbnail_title($picture, $picture['name'], null),
          'U_IMG_LINK'  => $url,
          'LEGEND'      => ($name=="name")?$picture['name']:"",
          'COMMENT'     => $picture['file'],
          );
    }
    
    $template->assign('img', $img);
    $template->assign('FLOAT', !empty($align) ? 'float: ' . $align . ';' : '');
    return $template->parse('extended_description_content', true);
  }
  return '';
}

/**
 * Return html code for a photo
 *
 * @int    id:    picture id
 * @int    album: album to display picture in    (default: null)
 * @string size:  picture size                   (default: M)
 * @string html:  return complete html structure (default: yes)
 * @string link:  add a link to the picture      (default: yes)
 */
function get_photo_sized($param)
{
  global $template;
  
  $default_params = array(
    'id' =>    array('\d+', null),
    'album' => array('\d+', null),
    'size' =>  array('SQ|TH|XXS|XS|S|M|L|XL|XXL', 'M'),
    'html' =>  array('yes|no', 'yes'),
    'link' =>  array('yes|no', 'yes'),
    );
    
  $params = parse_parameters($param, $default_params);
  
  // check picture id
  if (empty($params['id'])) return 'missing picture id';
  
  // parameters
  $params['link'] = $params['link']=='no' ? false : true;
  $params['html'] = $params['html']=='no' ? false : true;
  $deriv_type = get_deriv_type($params['size']);

  // get picture
  $query = 'SELECT * FROM ' . IMAGES_TABLE . ' WHERE id = '.$params['id'].';';
  $result = pwg_query($query); 

  if (pwg_db_num_rows($result))
  {
    $picture = pwg_db_fetch_assoc($result);
    
    // url
    if ($params['link'])
    {
      if (!empty($params['album']))
      {
        $url = make_picture_url(array(
          'image_id' => $picture['id'],
          'category' => array(
            'id' => $params['album'],
            'name' => '',
            'permalink' => '',
            )));
      }
      else
      {
        $url = make_picture_url(array('image_id' => $picture['id']));
      }
    }
    
    // image
    $src_image = new SrcImage($picture);
    $derivatives = DerivativeImage::get_all($src_image);
    $selected_derivative = $derivatives[$deriv_type];

    $template->assign(array(
      'current' => array(
        'selected_derivative' => $selected_derivative,
        ),
      'ALT_IMG' => $picture['file'],
      ));

    // output
    if ($params['html']) 
    {
      $template->set_filename('extended_description_content', dirname(__FILE__).'/template/picture_content.tpl');
      $content = $template->parse('extended_description_content', true);
      if ($params['link']) return '<a href="'.$url.'">'.$content.'</a>';
      else                 return $content;
    }
    else
    {
      return $selected_derivative->get_url();
    }
  }
  
  return 'invalid picture id';
}

/**
 * Return html code for a random photo
 *
 * @int    album: select picture from this album
 * @string size:  picture size                   (default: M)
 * @string html:  return complete html structure (default: yes)
 * @string link:  add a link to the picture      (default: no)
 */
function extdesc_get_random_photo($param)
{
  $default_params = array(
    'album' => array('\d+', null),
    'cat' =>   array('\d+', null), // historical
    'size' =>  array('SQ|TH|XXS|XS|S|M|L|XL|XXL', 'M'),
    'html' =>  array('yes|no', 'yes'),
    'link' =>  array('yes|no', 'no'),
    );
    
  $params = parse_parameters($param, $default_params);
  
  // check album id
  if (empty($params['album']))
  {
    if (empty($params['cat'])) return 'missing album id';
    $params['album'] = $params['cat'];
  }
  
  // get picture id
  $query = '
SELECT id
  FROM '.IMAGES_TABLE.'
    JOIN '.IMAGE_CATEGORY_TABLE.' ON image_id = id
  WHERE category_id = '.$params['album'].'
  ORDER BY '.DB_RANDOM_FUNCTION.'()
  LIMIT 1
;';
  $result = pwg_query($query);
  
  if (pwg_db_num_rows($result))
  {
    list($img_id) = pwg_db_fetch_row($result);
    return get_photo_sized('id='.$img_id.' album='.$params['album'].' size='.$params['size'].' html='.$params['html'].' link='.$params['link']);
  }

  return '';
}

/** 
 * Return html code for a nivo slider (album or list is mandatory)
 *
 * @int    album:     select pictures from this album
 * @int    nb_images: display only x pictures           (default: 10)
 * @string random:    random sort order                 (default: no)
 *
 * @string list:      pictures id separated by a comma
 *
 * @string size:      picture size                      (default: M)
 * @int    speed:     slideshow duration                (default: 3)
 * @string title:     display picture name              (default: no)
 * @string effect:    transition effect                 (default: fade)
 * @string arrows:    display navigation arrows         (default: yes)
 * @string control:   display navigation bar            (default: yes)
 * @string elastic:   adapt slider size to each picture (default: no)
 */
function get_slider($param)
{
  global $template, $conf;
  
  $default_params = array(
    'album' =>     array('\d+', null),
    'nb_images' => array('\d+', 10),
    'random' =>    array('yes|no', 'no'),
    'list' =>      array('[\d,]+', null),
    'size' =>      array('SQ|TH|XXS|XS|S|M|L|XL|XXL', 'M'),
    'speed' =>     array('\d+', 3),
    'title' =>     array('yes|no', 'no'),
    'effect' =>    array('[a-zA-Z]+', 'fade'),
    'arrows' =>    array('yes|no', 'yes'),
    'control' =>   array('yes|no', 'yes'),
    'elastic' =>   array('yes|no', 'no'),
    );
    
  $params = parse_parameters($param, $default_params);
  
  // check size
  $deriv_type = get_deriv_type($params['size']);
  $enabled = ImageStdParams::get_defined_type_map();
  if (empty($enabled[ $deriv_type ])) return 'size disabled';
  
  // parameters
  $params['arrows'] = $params['arrows']==='no' ? 'false' : 'true';
  $params['control'] = $params['control']==='no' ? 'false' : 'true';
  $params['elastic'] = $params['elastic']==='yes' ? true : false;
  $params['title'] = $params['title']==='yes' ? true : false;
  
  // pictures from album...
  if (!empty($params['album']))
  {
    // parameters
    $params['random'] = $params['random']==='yes' ? true : false;
    
    // get image order inside category
    if ($params['random'])
    {
      $order_by = DB_RANDOM_FUNCTION.'()';
    }
    else
    {
      $query = '
SELECT image_order
  FROM '.CATEGORIES_TABLE.'
  WHERE id = '.$params['album'].'
;';
      list($order_by) = pwg_db_fetch_row(pwg_query($query));
      if (empty($order_by))
      {
        $order_by = str_replace('ORDER BY ', null, $conf['order_by_inside_category']);
      }
    }
    
    // get pictures ids
    $query = '
SELECT image_id
  FROM '.IMAGE_CATEGORY_TABLE.' as ic
    INNER JOIN '.IMAGES_TABLE.' as i
    ON i.id = ic.image_id
  WHERE category_id = '.$params['album'].'
  ORDER BY '.$order_by.'
  LIMIT '.$params['nb_images'].'
;';
    $params['list'] = implode(',', array_from_query($query, 'image_id'));
  }
  // ...or pictures list
  if (empty($params['list']))
  {
    return 'missing album id or empty picture list';
  }
  
  // get pictures
  $query = '
SELECT *
  FROM '.IMAGES_TABLE.'
  WHERE id IN ('.$params['list'].')
;';
  $pictures = hash_from_query($query, 'id');
    
  foreach ($pictures as $row)
  {
    // url
    if (!empty($params['album']))
    {
      $url = make_picture_url(array(
        'image_id' => $row['id'],
        'category' => array(
          'id' => $params['album'],
          'name' => '',
          'permalink' => '',
          )));
    }
    else
    {
      $url = make_picture_url(array('image_id' => $row['id']));
    }

    $name = render_element_name($row);
    
    $tpl_vars[] = array_merge($row, array(
      'TN_ALT' => htmlspecialchars(strip_tags($name)),
      'NAME' => $name,
      'URL' => $url,
      'src_image' => new SrcImage($row),
      ));
  }
  
  list($img_size['w'], $img_size['h']) = $enabled[ $deriv_type ]->sizing->ideal_size;
  
  $template->assign(array(
    'EXTENDED_DESC_PATH' => EXTENDED_DESC_PATH,
    'slider_id' => crc32(uniqid($params['list'])), // need a unique id if we have multiple sliders
    'slider_content' => $tpl_vars,
    'derivative_params' => ImageStdParams::get_by_type( $deriv_type ),
    'img_size' => $img_size,
    'pauseTime' => $params['speed']*1000,
    'controlNav' => $params['control'],
    'effect' => $params['effect'],
    'directionNav' => $params['arrows'],
    'elastic_size' => $params['elastic'],
    'show_title' => $params['title'],
    ));
  
  $template->set_filename('extended_description_content', dirname(__FILE__).'/template/slider.tpl');
  return $template->parse('extended_description_content', true);
}


function parse_parameters($param, $default_params)
{
  $params = array();
  
  foreach ($default_params as $name => $value)
  {
    if (preg_match('#'.$name.'=('.$value[0].')#', $param, $matches))
    {
      $params[$name] = $matches[1];
    }
    else
    {
      $params[$name] = $value[1];
    }
  }
  
  return $params;
}

function get_deriv_type($size)
{
  $size_map = array(
    'SQ' => IMG_SQUARE,
    'TH' => IMG_THUMB,
    'XXS' => IMG_XXSMALL,
    'XS' => IMG_XSMALL,
    'S' => IMG_SMALL,
    'M' => IMG_MEDIUM,
    'L' => IMG_LARGE,
    'XL' => IMG_XLARGE,
    'XXL' => IMG_XXLARGE,
    );
    
  if (!array_key_exists($size, $size_map)) $size = 'M';
  
  return $size_map[ strtoupper($size) ];
}


if (script_basename() == 'admin' or script_basename() == 'popuphelp')
{
  include(EXTENDED_DESC_PATH . 'admin.inc.php');
}

add_event_handler ('render_page_banner', 'get_extended_desc');
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
