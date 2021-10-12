{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{strip}
    <div class="action-link">
        {if !empty($templateUrl)}
            <a href="{$templateUrl}" class="button"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {$newButton}</span></a>
        {/if}
        {if !empty($reportUrl)}
            <a href="{$reportUrl}" class="button"><span>{ts}View All Reports{/ts}</span></a>
        {/if}
    </div>
    {if $list}
        <div class="crm-block crm-form-block crm-report-instanceList-form-block">
            {counter start=0 skip=1 print=false}
            {foreach from=$list item=comrows key=comreport}
                <div class="crm-accordion-wrapper crm-accordion_{$comreport}-accordion ">
                    <div class="crm-accordion-header">
                        {if isset($title)}{$title}{elseif $comreport EQ 'Contribute'}{ts}Contribution Reports{/ts}{else}{ts 1=$comreport}%1 Reports{/ts}{/if}
                    </div><!-- /.crm-accordion-header -->
                    <div class="crm-accordion-body">
                        {foreach from=$comrows item=controlrows key=control}
                            {if $control eq 'accordion'}<!-- Sub Sections -->
                                {foreach from=$controlrows item=rows key=report}
                                  <div class="crm-accordion-wrapper crm-accordion_{$report}-accordion ">
                                    <div class="crm-accordion-header">
                                      {$report}
                                    </div><!-- /.crm-accordion-header -->
                                    <div class="crm-accordion-body">
                                      <div class="boxBlock">
                                        <table class="report-layout">
                                            {foreach from=$rows item=row}
                                                <tr id="row_{counter}" class="crm-report-instanceList">
                                                    <td class="crm-report-instanceList-title" style="width:35%"><a href="{$row.url}" title="{ts}Run this report{/ts}"> <strong>{$row.title}</strong></a></td>
                                                    <td class="crm-report-instanceList-description">{$row.description}</td>
                                                    <td>
                                                        <a href="{$row.viewUrl}" class="action-item crm-hover-button">{ts}View Results{/ts}</a>
                                                        <span class="btn-slide crm-hover-button">more
                                                          <ul class="panel">
                                                            {foreach from=$row.actions item=action key=action_name}
                                                              <li><a href="{$action.url}" class="{$action_name} action-item crm-hover-button small-popup"
                                                              {if !empty($action.confirm_message)}onclick="return window.confirm({$action.confirm_message|json_encode|htmlspecialchars})"{/if}
                                                              title="{$action.label|escape}">{$action.label}</a></li>
                                                            {/foreach}
                                                          </ul>
                                                        </span>
                                                    </td>
                                                </tr>
                                            {/foreach}
                                        </table>
                                    </div>
                                </div>
                            </div>
                                {/foreach}
                            {else}
                                {foreach from=$controlrows item=row key=report}
                                  <div class="boxBlock"><!-- No section report list -->
                                    <table class="report-layout">
                                            <tr id="row_{counter}" class="crm-report-instanceList">
                                                <td class="crm-report-instanceList-title" style="width:35%"><a href="{$row.url}" title="{ts}Run this report{/ts}"> <strong>{$row.title}</strong></a></td>
                                                <td class="crm-report-instanceList-description">{$row.description}</td>
                                                <td>
                                                    <a href="{$row.viewUrl}" class="action-item crm-hover-button">{ts}View Results{/ts}</a>
                                                    <span class="btn-slide crm-hover-button">more
                                                      <ul class="panel">
                                                        {foreach from=$row.actions item=action key=action_name}
                                                          <li><a href="{$action.url}" class="{$action_name} action-item crm-hover-button small-popup"
                                                          {if !empty($action.confirm_message)}onclick="return window.confirm({$action.confirm_message|json_encode|htmlspecialchars})"{/if}
                                                                 title="{$action.label|escape}">{$action.label}</a></li>
                                                        {/foreach}
                                                      </ul>
                                                    </span>
                                                </td>
                                            </tr>
                                    </table>
                                  </div>
                                {/foreach}
                            {/if}
                        {/foreach}
                    </div>
                </div>
            {/foreach}
        </div>

    {else}
        <div class="crm-content-block">
            <div class="messages status no-popup">
                {icon icon="fa-info-circle"}{/icon}
                {if !empty($myReports)}
                    {ts}You do not have any private reports. To add a report to this section, edit the Report Settings for a report and set 'Add to My Reports' to Yes.{/ts} &nbsp;
                {else}
                    {ts 1=$compName}No %1 reports have been created.{/ts} &nbsp;
                    {if !empty($templateUrl)}
                        {ts 1=$templateUrl}You can create reports by selecting from the <a href="%1">list of report templates here.</a>{/ts}
                    {else}
                        {ts}Contact your site administrator for help creating reports.{/ts}
                    {/if}
                {/if}
            </div>
        </div>
    {/if}
{/strip}
