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

<div id="fme-pickup-stores-page" class="clearfix card card-block">
  <div class="form-group row">
    <label class="col-md-3 control-label">{l s='Our Stores' mod='storepickup'}</label>
    <div class="form-group col-lg-6">
      <select id="store-pickup-select" class="form-control">
        {if $stores|@count}
          <option value="-1">{$stores|@count|escape:'htmlall':'UTF-8'} {l s='Stores' mod='storepickup'}</option>
            {foreach from=$stores key=j item=str}
              <option value="{$j|escape:'htmlall':'UTF-8'}"
              label="{$str.id_store|escape:'htmlall':'UTF-8'}"
              data-value="{$str.id_store|escape:'htmlall':'UTF-8'}"
              {if isset($default_store) AND default_store AND $str.id_store == $default_store}selected="selected"{/if}>
                {$str.id_store|escape:'htmlall':'UTF-8'}-{$str.name|escape:'htmlall':'UTF-8'}
              </option>
            {/foreach}
          {else}
            <option>-</option>
        {/if}
      </select>
    </div>
  </div>
</div>
<div class="clearfix"></div>