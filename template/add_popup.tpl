<script type="text/javascript">
pop = '<a href="./popuphelp.php?page=extended_desc" onclick="popuphelp(this.href); return false;" title="{'ExtendedDesc_help'|@translate|@escape:'javascript'}" style="vertical-align: middle; border: 0; margin: 0.5em;"><img src="{$ROOT_URL}{$themeconf.admin_icon_dir}/help.png" class="button" alt="{'ExtendedDesc_help'|@translate|@escape:'javascript'}"></a>';
{if $ed_page == 'cat_modify'}
comment = jQuery("td:has(textarea[name=\'comment\'])").html();
mail_content = jQuery("td:has(textarea[name=\'mail_content\'])").html();
jQuery("td:has(textarea[name=\'comment\'])").html(comment + pop);
jQuery("td:has(textarea[name=\'mail_content\'])").html(mail_content + pop);
jQuery("td:has(input[name=\'name\'])").html('<textarea cols="50" rows="2" name="name" id="name" class="description" style="height: 2em">' + '{$CAT_NAME|@escape:'javascript'}' + '</textarea>' + pop);
{/if}
{if $ed_page == 'picture_modify'}
name = jQuery("td:has(input[name=\'name\'])").html();
desc = jQuery("td:has(textarea[name=\'description\'])").html();
jQuery("td:has(input[name=\'name\'])").html(name + pop);
jQuery("td:has(textarea[name=\'description\'])").html(desc + pop);
{/if}
{if $ed_page == 'configuration'}
page_banner = jQuery("li:has(textarea[name=\'page_banner\'])").html();
jQuery("li:has(textarea[name=\'page_banner\'])").html(page_banner + pop);
{/if}
{if $ed_page == 'notification_by_mail'}
nbm_complementary_mail_content = jQuery("td:has(textarea[name=\'nbm_complementary_mail_content\'])").html();
send_customize_mail_content = jQuery("td:has(textarea[name=\'send_customize_mail_content\'])").html();
jQuery("td:has(textarea[name=\'nbm_complementary_mail_content\'])").html(nbm_complementary_mail_content + pop);
jQuery("td:has(textarea[name=\'send_customize_mail_content\'])").html(send_customize_mail_content + pop);
{/if}
</script>
