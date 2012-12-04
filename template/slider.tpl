{combine_script id='jquery.nivo.slider' path=$EXTENDED_DESC_PATH|cat:'template/nivoslider/jquery.nivo.slider.js' require='jquery' load='footer'}
{combine_css path=$EXTENDED_DESC_PATH|cat:'template/nivoslider/nivo-slider.css'}
{combine_css path=$EXTENDED_DESC_PATH|cat:'template/nivoslider/dark.css'}

{footer_script require='jquery.nivo.slider'}
$("#slider{$slider_id}").nivoSlider({ldelim}
  pauseTime: {$pauseTime},
  animSpeed: {$pauseTime}/6,
  effect: '{$effect}',
  directionNav: {$directionNav},
  controlNav: {$controlNav},
  beforeChange: function() {ldelim}
    if ($('#slider{$slider_id}').data('nivo:vars').currentImage.attr('src') == "")
    {ldelim}
      return false;
    }
    {if $elastic_size}
    $("#slider{$slider_id}").css({ldelim}
      height: 'auto',
    });
    {/if}
  }
});
{/footer_script}

{if $elastic_size}
{assign var=slider_full_height value=0}
{else}
{assign var=slider_full_height value=$img_size.h}
{/if}
{assign var=slider_full_width value=0}

<div class="slider-wrapper theme-default">
  <div id="slider{$slider_id}" class="nivoSlider">
  {foreach from=$slider_content item=thumbnail name=slider}{strip}
    {assign var=derivative value=$pwg->derivative($derivative_params, $thumbnail.src_image)}
    {if !$derivative->is_cached()}
    {combine_script id='jquery.ajaxmanager' path='themes/default/js/plugins/jquery.ajaxmanager.js' load='footer'}
    {combine_script id='thumbnails.loader' path='themes/default/js/thumbnails.loader.js' require='jquery.ajaxmanager' load='footer'}
    {/if}
    
    <img {if $derivative->is_cached()}src="{$derivative->get_url()}"{else}src="" data-src="{$derivative->get_url()}"{/if} alt="{$thumbnail.TN_ALT}" {$derivative->get_size_htm()} {if $show_title}title="<a href='{$thumbnail.URL}'>{$thumbnail.NAME|replace:'"':"'"}</a>"{/if}>
    
    {assign var=derivative_size value=$derivative->get_size()}
    {math assign=slider_full_width equation="max(x,y)" x=$slider_full_width y=$derivative_size[0]}
  {if $elastic_size}
    {math assign=slider_full_height equation="max(x,y)" x=$slider_full_height y=$derivative_size[1]}
  {else}
    {math assign=slider_full_height equation="min(x,y)" x=$slider_full_height y=$derivative_size[1]}
  {/if}
  {if $smarty.foreach.slider.first}
    {assign var=slider_init_width value=$derivative_size[0]}
    {assign var=slider_init_height value=$derivative_size[1]}
  {/if}
  
  {/strip}
  {/foreach}
  </div>
</div>

{footer_script}
$("#slider{$slider_id}").parent(".slider-wrapper").css({ldelim}
  height: {$slider_full_height}{if $controlNav=='true'}+40{/if},
  width: {$slider_full_width}
});
$("#slider{$slider_id}").css({ldelim}
  height: {if $elastic_size}{$slider_init_height}{else}{$slider_full_height}{/if},
  width: {$slider_init_width}
});
{/footer_script}