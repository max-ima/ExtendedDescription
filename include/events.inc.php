<?php
defined('EXTENDED_DESC_PATH') or die('Hacking attempt!');

/**
 * Parse [lang] tags
 */
function get_user_language_desc($desc, $user_lang=null)
{
  global $user;

  if (is_null($user_lang))
  {
    $user_lang = $user['language'];
  }

  $small_user_lang = substr($user_lang, 0, 2);

  if (!preg_match('#\[lang=('.$user_lang.'|'.$small_user_lang.')\]#i', $desc))
  {
    $user_lang = 'default';
    $small_user_lang = 'default';
  }

  if (preg_match('#\[lang=('.$user_lang.'|'.$small_user_lang.')\]#i', $desc))
  {
    // la balise avec la langue de l'utilisateur a été trouvée
    $patterns[] = '#(^|\[/lang\])(.*?)(\[lang=(' . $user_lang . '|' . $small_user_lang . '|all)\]|$)#is';
    $replacements[] = '';
    $patterns[] = '#\[lang=(' . $user_lang . '|' . $small_user_lang . '|all)\](.*?)\[/lang\]#is';
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

/**
 * Parse [lang] tags in keywords url
 */
function get_user_language_tag_url($tag)
{
  return get_user_language_desc($tag, get_default_language());
}

/**
 * Get all variations of [lang] tags
 */
function ed_get_all_alt_names($arr, $name)
{
  if (preg_match_all('#\[lang=(.*?)\](.*?)\[/lang\]#is', $name, $matches, PREG_SET_ORDER))
  {
    foreach ($matches as $match)
    {
      @$arr[$match[1]] .= $match[2];
    }
  }
  return $arr;
}

/**
 * Parse all ED tags
 */
function get_extended_desc($desc, $param='')
{
  global $conf, $page;

  // [redirect]
  // Only parse if category description on category page
  // or element description on element page
  // to avoid undesired redirect when parsing other where
  if (
    $param != 'subcatify_category_description'
    and $param != 'main_page_element_description'
    and ( script_basename() != 'picture' or $param != 'main_page_category_description' )
    and preg_match('#\[redirect (.*?)\]#i', $desc, $m1)
  )
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
      load_language('plugin.lang', EXTENDED_DESC_PATH);
      $header_notes[] = sprintf(l10n('This category is redirected to %s'), '<a href="'.$url.'">'.$url.'</a>');
    }
    else
    {
      redirect($url);
    }
  }

  $desc = get_user_language_desc($desc);

  // Remove unparsed redirect tag
  $patterns[] = '#\[redirect .*?\]#i';
  $replacements[] = '';

  // [cat=xx]
  $patterns[] = '#\[cat=(\d*)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : 'get_cat_thumb("$1")';

  // [img=xx.yy,xx.yy,xx.yy;left|right|;name|titleName|]
  $patterns[] = '#\[img([^\]]*)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : '[img] must be replaced by [photo]';

  // [photo id=xx album=yy size=SQ|TH|XXS|XS|S|M|L|XL|XXL html=yes|no link=yes|no]
  $patterns[] = '#\[photo ([^\]]+)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : 'get_photo_sized("$1")';

  // [random album=xx size=SQ|TH|XXS|XS|S|M|L|XL|XXL html=yes|no link=yes|no]
  $patterns[] = '#\[random([^\]]*)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : 'extdesc_get_random_photo("$1")';

  // [slider album=xx nb_images=yy random=yes|no list=aa,bb,cc size=SQ|TH|XXS|XS|S|M|L|XL|XXL speed=z title=yes|no effect=... arrows=yes|no control=yes|no|thumb elastic=yes|no thumbs_size=dd]
  $patterns[] = '#\[slider ([^\]]+)\]#ie';
  $replacements[] = ($param == 'subcatify_category_description') ? '' : 'get_slider("$1")';

  // <!--complete-->, <!--more--> et <!--up-down-->
  switch ($param)
  {
    case 'subcatify_category_description' :
      $patterns[] = '#^(.*?)(' . preg_quote($conf['ExtendedDescription']['complete']) . '|' . preg_quote($conf['ExtendedDescription']['more']) . '|' . preg_quote($conf['ExtendedDescription']['up-down']) . ').*$#is';
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

/**
 * Parse ED tags on NBM mail
 */
function extended_desc_mail_group_assign_vars($assign_vars)
{
  if (isset($assign_vars['CPL_CONTENT']))
  {
    $assign_vars['CPL_CONTENT'] = get_extended_desc($assign_vars['CPL_CONTENT']);
  }
  return $assign_vars;
}

/**
 * Add bottom description when <!--up-down--> found
 */
function add_bottom_description()
{
  global $template, $conf;

  $template->concat('PLUGIN_INDEX_CONTENT_END', '
    <div class="additional_info">
    ' . $conf['ExtendedDescription']['bottom_comment'] . '
    </div>');
}

/**
 * Remove categories with <!--hidden-->
 */
function ext_remove_cat($tpl_var)
{
  global $conf;

  $i=0;
  while ($i<count($tpl_var))
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

/**
 * Remove categories with <!--mb-hidden-->
 */
function ext_remove_menubar_cats($where)
{
  global $conf;

  $query = 'SELECT id, uppercats
    FROM '.CATEGORIES_TABLE.'
    WHERE name LIKE "%'.$conf['ExtendedDescription']['mb_not_visible'].'%"';

  $result = pwg_query($query);
  while ($row = pwg_db_fetch_assoc($result))
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

/**
 * Remove elements with <!--hidden-->
 */
function ext_remove_image($tpl_var, $pictures)
{
  global $conf;

  $i=0;
  while ($i<count($tpl_var))
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
