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

<div class="panel box">
    <h2 class="panel-heading"><i class="icon-truck"></i> {l s='Pickup from Store' mod='storepickup'}</h2>
    <div class="pcickup_details">
            <div class="form-group row">
                <span class="center grey bold" width="45%" style="margin-left:15px;">
                    {l s='Pickup Store' mod='storepickup'}
                </span>

                <span class="left white" width="55%" style="margin-left:15px;">
                    <strong>{$pickup_data.store_name|escape:'htmlall':'UTF-8'}</strong>
                </span>
            </div>

            <div class="form-group row">
                <span class="center grey bold" width="45%" style="margin-left:15px;">
                    {l s='Store Address' mod='storepickup'}
                </span>

                <span class="left white" width="55%" style="margin-left:15px;">
                    <strong>{$pickup_data.store_address|escape:'htmlall':'UTF-8'}</strong>
                </span>
            </div>

        {if isset($pickup_data.store_address) AND $pickup_data.store_address}
            <div class="form-group row">
                <span class="center grey bold" width="45%" style="margin-left:15px;">
                    {l s='Pickup Date' mod='storepickup'}
                </span>

                <span class="left white" width="55%" style="margin-left:15px;">
                    <strong>{$pickup_data.pickup_date|escape:'htmlall':'UTF-8'}</strong>
                </span>
            </div>
        {/if}

        {if isset($storeOrder->delivery_number) AND $storeOrder->delivery_number}
            <div class="form-group row">
                <span class="center grey bold" width="45%" style="margin-left:15px;">
                    {l s='Pickup Slip' mod='storepickup'}
                </span>

                <span class="left white" width="55%" style="margin-left:15px;">
                    <a class="btn btn-info" href="{$linkPdf|strip_tags|escape:'htmlall':'UTF-8'}">
                        <i class="icon-file-text large"></i> {l s='Download Pickup Slip' mod='storepickup'}
                    </a>
                </span>
            </div>
        {/if}
    </div>
</div>