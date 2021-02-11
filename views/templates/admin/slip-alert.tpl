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

<div class="btn-group-action">
    <div class="btn-group pull-right">
        {if $order->delivery_number}
        <a href="javascript:;" title="{l s='Send Alert' mod='storepickup'}" class="edit btn btn-default">
            <i class="icon-paper-plane"></i> {l s='Send Alert' mod='storepickup'}
        </a>
        <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <i class="icon-caret-down"></i>&nbsp;
        </button>
        <ul class="dropdown-menu">
            <li>
                <a href="javascript:;"
                title="{l s='Send Alert to Customer' mod='storepickup'}"
                onclick="sendStoreAlert({$order->id|escape:'htmlall':'UTF-8'}, {$id_store|escape:'htmlall':'UTF-8'}, 'customer'); return false;">
                    <i class="icon-user"></i> {l s='To Customer' mod='storepickup'}
                </a>
            </li>
            <li class="divider">
            </li>
            <li>
                <a href="javascript:;"
                title="{l s='Send Alert to Store Representative' mod='storepickup'}"
                onclick="sendStoreAlert({$order->id|escape:'htmlall':'UTF-8'}, {$id_store|escape:'htmlall':'UTF-8'}, 'store'); return false;">
                    <i class="icon-truck"></i> {l s='To Store' mod='storepickup'}
                </a>
            </li>
        </ul>
        {else}
            <button class="edit btn btn-default" disabled="disabled">
                <i class="icon-paper-plane"></i> {l s='Send Alert' mod='storepickup'}
            </button>
            <button class="btn btn-default dropdown-toggle" disabled="disabled">
                <i class="icon-caret-down"></i>&nbsp;
            </button>
        {/if}
    </div>
</div>