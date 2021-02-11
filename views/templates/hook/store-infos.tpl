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

{* The following lines allow translations in back-office and has to stay commented

    {l s='Monday' mod='storepickup'}
    {l s='Tuesday' mod='storepickup'}
    {l s='Wednesday' mod='storepickup'}
    {l s='Thursday' mod='storepickup'}
    {l s='Friday' mod='storepickup'}
    {l s='Saturday' mod='storepickup'}
    {l s='Sunday' mod='storepickup'}
*}
{if !empty($days_datas)}
    <table id="pickup-store-info" class="table-striped table-bordered table" style="width: 250px;">
        <tbody>
            {foreach from=$days_datas  item=one_day}
                <tr style="font-size: 12px; margin: 1px 0;">
                    <td><strong class="dark">{l s=$one_day.day|escape:'htmlall':'UTF-8' mod='storepickup'}: </strong></td>
                    <td>&nbsp;<span>{$one_day.hours|escape:'htmlall':'UTF-8'}</span></td>
                </tr>
            {/foreach}
        </tbody>
    </table>
{/if}

