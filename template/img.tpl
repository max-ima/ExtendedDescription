<ul class="thumbnails" style="margin:4px;display:inline;text-align:none;{$FLOAT}">
  {foreach from=$img key=name item=data}
  <li>
    <span class="wrap1">
      <span class="wrap2">
        <a href="{$data.U_IMG_LINK}">
            <img class="thumbnail" src="{$data.IMAGE}" alt="{$data.IMAGE_ALT}" title="{$data.IMG_TITLE}">
        </a>
      </span>
    {if $data.LEGEND!=""}
      <span class="thumbLegend">{$data.LEGEND}</span>
    {/if}
    </span>
  </li>
  {/foreach}
</ul>