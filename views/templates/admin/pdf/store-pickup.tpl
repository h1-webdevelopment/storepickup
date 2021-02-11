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

<table class="product" width="80%" cellpadding="4" cellspacing="0">
    <thead>
        <tr>
            <th class="product header small" width="100%">{l s='Pickup from Store' mod='storepickup' pdf='true'}</th>
        </tr>
    </thead>
    <tbody>
        <tr class="product">
            <td class="center grey bold" width="45%" style="margin-left:15px;">
                {l s='Pickup Store' mod='storepickup' pdf='true'}
            </td>

            <td class="left white" width="55%" style="margin-left:15px;">
                {$pickup_data.store_name|escape:'htmlall':'UTF-8'}
            </td>
        </tr>

        <tr class="product">
            <td class="center grey bold" width="45%" style="margin-left:15px;">
                {l s='Store Address' mod='storepickup' pdf='true'}
            </td>

            <td class="left white" width="55%" style="margin-left:15px;">
                {$pickup_data.store_address}
            </td>
        </tr>

        {if (isset($pickup_data.store_address) AND $pickup_data.store_address)}
            <tr class="product">
                <td class="center grey bold" width="45%" style="margin-left:15px;">
                    {l s='Pickup Date' mod='storepickup' pdf='true'}
                </td>

                <td class="left white" width="55%" style="margin-left:15px;">
                    {$pickup_data.pickup_date|escape:'htmlall':'UTF-8'}
                </td>
            </tr>
        {/if}
</table>