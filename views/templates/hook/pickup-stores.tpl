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

<div id="pickup-stores" class="card card-block block box">
  <h4 class="title_block">{l s='Pickup from Store' mod='storepickup'}</h4>
    {include file="./stores-box.tpl"}
    {if isset($pickupDate) AND $pickupDate}
      {include file="./pickup-time.tpl"}
    {/if}
    <div id="store-pickup-map" class="store_pickup"></div>
</div>