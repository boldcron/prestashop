{if !count($tipos)}
<p class="payment_module">
	<a href="modules/cobredireto/redirect.php" title="{l s='CobreDireto' mod='cobredireto'}">
		<img src="{$module_template_dir}cobredireto.png" alt="{l s='CobreDireto' mod='cobredireto'}" />
		{l s='Pague com CobreDireto' mod='cobredireto'}
	</a>
</p>
{else}
{literal}
<style>
form#cobredireto_form .segmentacao {
    clear: both;
}
form#cobredireto_form .segmentacao p {
    font-size: 1.5em;
    padding-top: 15px;
    clear: left;
}
form#cobredireto_form .segmentacao label {
    float: left;
    width: 67px;
    height: 70px;
    margin-right: 20px;
    text-align: center;
}
form#cobredireto_form .segmentacao label span {
    border: medium none;
    float: left;
    height: 51px;
    padding: 0;
    text-indent: -9999px;
    width: 67px;
}
form#cobredireto_form .segmentacao label span#img_banrisul_pgta,
form#cobredireto_form .segmentacao label span#img_redecard_visa,
form#cobredireto_form .segmentacao label span#img_redecard_ws_visa,
form#cobredireto_form .segmentacao label span#img_cielo2p_master,
form#cobredireto_form .segmentacao label span#img_cielo2p_visa,
form#cobredireto_form .segmentacao label span#img_cielo3p_mastercard,
form#cobredireto_form .segmentacao label span#img_redecard_ws_mastercard,
form#cobredireto_form .segmentacao label span#img_redecard_ws_diners,
form#cobredireto_form .segmentacao label span#img_setef_hipercard       
{
    border: medium none;
    float: none;
    padding: 0;
    text-indent: 0px;
    display: block;
}

form#cobredireto_form .segmentacao label span#img_visa3dc
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 0 transparent;
}
form#cobredireto_form .segmentacao label span#img_redecard_mastercard
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -120px transparent;
}
form#cobredireto_form .segmentacao label span#img_redecard_diners
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -180px transparent;
}
form#cobredireto_form .segmentacao label span#img_amex_webpos2p
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -60px transparent;
}
form#cobredireto_form .segmentacao label span#img_bradesco
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -476px transparent;
}
form#cobredireto_form .segmentacao label span#img_itau
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -360px transparent;
}
form#cobredireto_form .segmentacao label span#img_bb
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -420px transparent;
}
form#cobredireto_form .segmentacao label span#img_unibanco
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -663px transparent;
}
form#cobredireto_form .segmentacao label span#img_real
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -300px transparent;
}
form#cobredireto_form .segmentacao label span#img_boleto_bradesco
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -476px transparent;
}
form#cobredireto_form .segmentacao label span#img_boleto_itau
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -360px transparent;
}
form#cobredireto_form .segmentacao label span#img_boleto_bb
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -420px transparent;
}
form#cobredireto_form .segmentacao label span#img_boleto_unibanco
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -663px transparent;
}
form#cobredireto_form .segmentacao label span#img_boleto_real
{
    background: url("{/literal}{$module_template_dir}{literal}images/logocartoes.jpg") no-repeat scroll 0 -300px transparent;
}
</style>
{/literal}
<form id="cobredireto_form" action="modules/cobredireto/redirect.php" method="post" style="border: 1px solid black; margin-left: 5px;">
    <p class="payment_module">
        <img src="{$module_template_dir}cobredireto.png" alt="{l s='CobreDireto' mod='cobredireto'}" />
    		{l s='Pague com CobreDireto' mod='cobredireto'}
    </p>
    {foreach from=$tipos item=pgto}
        <div class="segmentacao">
            <p>{$pgto.title}</p>
        {foreach from=$pgto item=band key=k}
            {if $k neq 'title'}
                <label for="{$k}">
                    <span id="img_{$k}">{$band}</span>
                    <input type="radio" name="pgto" value="{$k}" id="{$k}" />
                </label>
            {/if}
        {/foreach}
        </div>
    {/foreach}
    <br style="clear: both;" />
    <input type="submit" name="cobredireto_submit" value="Pagar" class="button" style="float: right;" />
    <br style="clear: both;" />
    <br style="clear: both;" />
</form>
{/if}
