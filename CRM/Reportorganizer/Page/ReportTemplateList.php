<?php

/**
 * Page for displaying list of Report templates available.
 */
class CRM_Reportorganizer_Page_ReportTemplateList extends CRM_Core_Page {

  /**
   * @param int $compID
   * @param null $grouping
   *
   * @return array
   */
  public static function &info($compID = NULL, $grouping = NULL) {
    $all = CRM_Utils_Request::retrieve('all', 'Boolean', CRM_Core_DAO::$_nullObject,
      FALSE, NULL, 'GET'
    );

    $compClause = '';
    if ($compID) {
      if ($compID == 99) {
        $compClause = " AND v.component_id IS NULL ";
      }
      else {
        $compClause = " AND v.component_id = {$compID} ";
      }
    }
    elseif ($grouping) {
      $compClause = " AND v.grouping = '{$grouping}' ";
    }
    $sql = "
SELECT  v.id, v.value, v.label, v.description, v.component_id, r.section_id,
  CASE
    WHEN comp.name IS NOT NULL THEN SUBSTRING(comp.name, 5)
    WHEN v.grouping IS NOT NULL THEN v.grouping
    ELSE 'Contact'
    END as component_name,
        v.grouping,
        inst.id as instance_id
FROM    civicrm_option_value v
INNER JOIN civicrm_option_group g
        ON (v.option_group_id = g.id AND g.name = 'report_template')
LEFT  JOIN civicrm_report_instance inst
        ON v.value = inst.report_id
LEFT JOIN civicrm_report_organizer_template r
        ON r.report_template_id = v.id AND r.component_id = v.component_id
LEFT  JOIN civicrm_component comp
        ON v.component_id = comp.id
";

    if (!$all) {
      $sql .= " WHERE v.is_active = 1 {$compClause}";
    }
    // Prevent fetching report templates to show in list if user is a client admin.
    global $user;
    if (in_array('client administrator', $user->roles)) {
      $hiddenTemplates = [
        'Contributions (Detailled)',
        'Contributions (Extended, Summary)',
        'Contributions (Extended, Pivot Chart)',
        'Contributions (Extended, Extra Fields)',
        'Recurring Contributions (Detailled)',
        'Recurring Contributions (Extended, Pivot Chart)',
        'Contacts (Detailled)',
        'Contacts (Extended, Pivot Chart)',
        'Activities (Detailled)',
        'Activities (Extended)',
        'Activities (Extended, Pivot Chart)',
        'Opportunity Report (Detailled)',
        'Opportunity Report (Statistics)',
      ];
      if (!$all) {
        $hideClause = " AND ";
      }
      else {
        $hideClause = " WHERE ";
      }
      $hideClause .= "v.label NOT IN ('" . implode("', '", $hiddenTemplates) . "') ";
      $sql .= $hideClause;
    }
    $sql .= " ORDER BY  v.weight ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $rows = [];
    $config = CRM_Core_Config::singleton();
    $sections = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => "component_template_section",
    ]);
    foreach ($sections['values'] as $section) {
      $sectionLabels[$section['value']] = $section['label'];
    }
    while ($dao->fetch()) {
      if ($dao->component_name != 'Contact' && $dao->component_name != $dao->grouping &&
        !in_array("Civi{$dao->component_name}", $config->enableComponents)
      ) {
        continue;
      }
      $report_sub_grouping = NULL;
      if ($dao->section_id) {
        $report_sub_grouping = $sectionLabels[$dao->section_id];
      }
      $componentName = ts($dao->component_name);
      if ($report_sub_grouping) {
        $rows[$componentName]['accordion'][$report_sub_grouping][$dao->value]['title'] = ts($dao->label);
        $rows[$componentName]['accordion'][$report_sub_grouping][$dao->value]['description'] = ts($dao->description);
        $rows[$componentName]['accordion'][$report_sub_grouping][$dao->value]['url'] = CRM_Utils_System::url('civicrm/report/' . trim($dao->value, '/'), 'reset=1');
        if ($dao->instance_id) {
          $rows[$componentName]['accordion'][$report_sub_grouping][$dao->value]['instanceUrl'] = CRM_Utils_System::url('civicrm/report/list',
            "reset=1&ovid={$dao->id}"
          );
        }
      }
      else {
        $rows[$componentName]['no_accordion'][$dao->value]['title'] = ts($dao->label);
        $rows[$componentName]['no_accordion'][$dao->value]['description'] = ts($dao->description);
        $rows[$componentName]['no_accordion'][$dao->value]['url'] = CRM_Utils_System::url('civicrm/report/' . trim($dao->value, '/'), 'reset=1');
        if ($dao->instance_id) {
          $rows[$componentName]['no_accordion'][$dao->value]['instanceUrl'] = CRM_Utils_System::url('civicrm/report/list',
            "reset=1&ovid={$dao->id}"
          );
        }
      }
    }

    // Move accordions to the beginning of each section
    foreach ($rows as &$row) {
      if (!empty($row['accordion'])) {
        $accordion = $row['accordion'];
        unset($row['accordion']);
        $row = ['accordion' => $accordion] + $row;
      }
    }

    // Handle sorting of reserved sections
    $contributionSectionOrder = [
      'General Contribution Reports',
      'Recurring Contribution Reports',
      'Receipt Reports',
    ];
    $sortedSections = CRM_Reportorganizer_Utils::accordionSorter('Contribute', $contributionSectionOrder, $rows);
    if (!empty($sortedSections)) {
      $rows['Contribute']['accordion'] = $sortedSections;
    }

    $contactSectionOrder = [
      'General Contact Reports',
      'Activity Reports',
      'Relationship Reports',
    ];
    $sortedSections = CRM_Reportorganizer_Utils::accordionSorter('Contact', $contactSectionOrder, $rows);
    if (!empty($sortedSections)) {
      $rows['Contact']['accordion'] = $sortedSections;
    }

    // Handle sorting of reserved instances
    $mailNoAccordionOrder = [
      'Mail Bounces',
      'Mail (Summary)',
      'Mail Opened',
      'Mail Click-Through',
      'Mail (Detailed)',
    ];
    $sortedSections = CRM_Reportorganizer_Utils::noAccordionSorter('Mail', $mailNoAccordionOrder, $rows);
    if (!empty($sortedSections)) {
      $rows['Mail']['no_accordion'] = $sortedSections;
    }

    $opportunityNoAccordionOrder = [
      'Opportunity Report (Detailled)',
      'Opportunity Report (Statistics)',
      'Opportunity (Detailed)',
    ];
    $sortedSections = CRM_Reportorganizer_Utils::noAccordionSorter('Opportunity', $opportunityNoAccordionOrder, $rows);
    if (!empty($sortedSections)) {
      $rows['Opportunity']['no_accordion'] = $sortedSections;
    }

    // Handle sorting for report instances within the sections.
    $instanceSections = [
      "General Contribution Reports" => [
        "Contributions (Summary)",
        "Contributions (Detailled)",
        "Repeat Contributions",
        "Top Donors",
        "SYBUNT",
        "LYBNT",
        "Contributions by Organization",
        "Contributions by Household",
        "Contributions by Relationship",
        "Contributions for Bookkeeping",
        "Contributions (Extended, Summary)",
        "Contributions (Detailed)",
        "Contributions (Extended, Pivot Chart)",
        "Contributions (Extended, Extra Fields)",
        "Contributions for Bookkeeping (Detailed)",
      ],
      "Recurring Contribution Reports" => [
        "Recurring Contributions (Summary)",
        "Recurring Contributions (Detailled)",
        "Recurring Contributions (Extended, Pivot Chart)",
        "Recurring Contributions (Detailed)",
      ],
      "Receipt Reports" => [
        "Tax Receipts (Issued)",
        "Tax Receipts (Not Yet Issued)",
      ],
    ];
    foreach ($instanceSections as $header => $sortOrder) {
      $sortedSections = CRM_Reportorganizer_Utils::insideAccordionSorter('Contribute', $header, $sortOrder, $rows);
      if (!empty($sortedSections)) {
        $rows['Contribute']['accordion'][$header] = $sortedSections;
      }
    }

    $instanceSections = [
      "General Contact Reports" => [
        "Contacts (Summary)",
        "Contacts (Detailled)",
        "Contacts (Detailed)",
        "Contacts (Extended, Pivot Chart)",
        "Database Log",
        "Address History",
      ],
      "Activity Reports" => [
        "Activities (Summary)",
        "Activities (Detailled)",
        "Activities (Extended)",
        "Activities (Extended, Pivot Chart)",
        "Activities (Detailed)",
      ],
      "Relationship Reports" => [
        "Relationships",
        "Current Employer",
        "Relationships (Detailed)",
      ]
    ];

    foreach ($instanceSections as $header => $sortOrder) {
      $sortedSections = CRM_Reportorganizer_Utils::insideAccordionSorter('Contact', $header, $sortOrder, $rows);
      if (!empty($sortedSections)) {
        $rows['Contact']['accordion'][$header] = $sortedSections;
      }
    }

    $rows = CRM_Reportorganizer_Utils::sortArrayByArray($rows, ["Contribute", "Contact", "Mail", "Opportunity", "Member", "Campaign Group"]);

    // Hide Campaign Group section
    unset($rows['Campaign Group']);
    return $rows;
  }

  /**
   * Run this page (figure out the action needed and perform it).
   */
  public function run() {
    $compID = CRM_Utils_Request::retrieve('compid', 'Positive', $this);
    $grouping = CRM_Utils_Request::retrieve('grp', 'String', $this);
    $rows = self::info($compID, $grouping);
    $this->assign('list', $rows);

    return parent::run();
  }

}
