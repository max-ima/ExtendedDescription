{if !$current.selected_derivative->is_cached()}
{combine_script id='jquery.ajaxmanager' path='themes/default/js/plugins/jquery.ajaxmanager.js' load='footer'}
{combine_script id='thumbnails.loader' path='themes/default/js/thumbnails.loader.js' require='jquery.ajaxmanager' load='footer'}
{/if}

<img {if $current.selected_derivative->is_cached()}src="{$current.selected_derivative->get_url()}" {$current.selected_derivative->get_size_htm()} {else}src="{$ROOT_URL}{$themeconf.img_dir}/ajax-loader-big.gif" data-src="{$current.selected_derivative->get_url()}"{/if} alt="{$ALT_IMG}">