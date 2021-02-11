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

<div id="storepickup-cal-wrapper" class="storepickup_calander card card-block">
  <div class="form-group row">
    <label class="col-md-3 control-label" for="storepickup_pickup_date">{l s='Pickup Date' mod='storepickup'}</label>
    <div class="col-md-9">
      <input type="text"
      class="form-control pickuptime"
      id="storepickup_pickup_date"
      name="storepickup_pickup_date"
      value="{if isset($selectedpickupTime) AND $selectedpickupTime}{$selectedpickupTime|escape:'htmlall':'UTF-8'}{/if}"
      data-type="date">
    </div>
  </div>
  {if isset($pickupTime) AND $pickupTime}
    <div id="storepickup_time_wrapper" class="form-group row">
      <label class="col-md-3 control-label" for="storepickup_pickup_time">{l s='Pickup Time' mod='storepickup'}</label>
      <div class="col-md-9">
        <input type="text"
        class="form-control pickuptime"
        id="storepickup_pickup_time"
        name="storepickup_pickup_time"
        value="{if isset($selectedpickupDate) AND $selectedpickupDate}{$selectedpickupDate|escape:'htmlall':'UTF-8'}{/if}"
        data-type="time">
      </div>
    </div>
  {/if}
</div>