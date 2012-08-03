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

{if not $elastic_size}
{assign var=slider_min_h value=$img_size.h}
{/if}

<div class="slider-wrapper theme-default" style="width:{$img_size.w}px;{if $elastic_size}height:{math equation='x+y' x=$img_size.h y=40}px;{/if}">
  <div id="slider{$slider_id}" class="nivoSlider" style="width:{$img_size.w}px;{if $elastic_size}height:{$img_size.h}px;{/if}">
  {foreach from=$slider_content item=thumbnail}{strip}
    {assign var=derivative value=$pwg->derivative($derivative_params, $thumbnail.src_image)}
    {if !$derivative->is_cached()}
    {combine_script id='jquery.ajaxmanager' path='themes/default/js/plugins/jquery.ajaxmanager.js' load='footer'}
    {combine_script id='thumbnails.loader' path='themes/default/js/thumbnails.loader.js' require='jquery.ajaxmanager' load='footer'}
    {/if}
    
    <img {if $derivative->is_cached()}src="{$derivative->get_url()}"{else}src="" data-src="{$derivative->get_url()}"{/if} alt="{$thumbnail.TN_ALT}" {$derivative->get_size_htm()} {if $show_title}title="<a href='{$thumbnail.URL}'>{$thumbnail.NAME|replace:'"':"'"}</a>"{/if}>
    
    {if not $elastic_size}
    {assign var=derivative_size value=$derivative->get_size()}
    {math assign=slider_min_h equation="min(x,y)" x=$slider_min_h y=$derivative_size[1]}
    {/if}
  {/strip}{/foreach}
  </div>
</div>

{if not $elastic_size}
{footer_script}
$("#slider{$slider_id}").css({ldelim}
  height: {$slider_min_h}
});
{/footer_script}
{/if}