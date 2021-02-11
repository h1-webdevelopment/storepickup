{*
*
* DISCLAIMER
*
* Do not edit or add to this file.
* You are not authorized to modify, copy or redistribute this file.
* Permissions are reserved by FME Modules.
*
*  @author    FMM Modules
*  @copyright FME Modules 2020
*  @license   Single domain
*}

{* Generate HTML code for printing Invoice Icon with link *}
<span class="btn-group-action">
    <span class="btn-group">
    {* Generate HTML code for printing Delivery Icon with link *}
    {if $order->delivery_number}
        <a class="btn btn-default _blank" href="{$adminPdf|escape:'htmlall':'UTF-8'}">
            <i class="icon-file"></i>
        </a>
    {/if}
    </span>
</span>
