<?php

/**
 * Page for invoking report instances.
 */
class CRM_Reportorganizer_Page_ReportInstanceList extends CRM_Core_Page {

  public static $_links = NULL;

  public static $_exceptions = ['logging/contact/detail'];

  /**
   * Name of component if report list is filtered.
   *
   * @var string
   */
  protected $_compName = NULL;

  /**
   * ID of component if report list is filtered.
   *
   * @var int
   */
  protected $_compID = NULL;

  /**
   * ID of grouping if report list is filtered.
   *
   * @var int
   */
  protected $_grouping = NULL;

  /**
   * ID of parent report template if list is filtered by template.
   *
   * @var int
   */
  protected $_ovID = NULL;

  /**
   * Title of parent report template if list is filtered by template.
   *
   * @var string
   */
  protected $_title = NULL;

  /**
   * Retrieves report instances, optionally filtered.
   *
   * Filtering available by parent report template ($ovID) or by component ($compID).
   *
   * @return array
   */
  public function info() {

    $report = '';
    $queryParams = [];

    if ($this->ovID) {
      $report .= " AND v.id = %1 ";
      $queryParams[1] = [$this->ovID, 'Integer'];
    }

    if ($this->compID) {
      if ($this->compID == 99) {
        $report .= " AND v.component_id IS NULL ";
        $this->_compName = 'Contact';
      }
      else {
        $report .= " AND v.component_id = %2 ";
        $queryParams[2] = [$this->compID, 'Integer'];
        $cmpName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Component', $this->compID,
          'name', 'id'
        );
        $this->_compName = substr($cmpName, 4);
        if ($this->_compName == 'Contribute') {
          $this->_compName = 'Contribution';
        }
      }
    }
    elseif ($this->grouping) {
      $report .= " AND v.grouping = %3 ";
      $queryParams[3] = [$this->grouping, 'String'];
    }
    elseif ($this->myReports) {
      $report .= " AND inst.owner_id = %4 ";
      $queryParams[4] = [CRM_Core_Session::getLoggedInContactID(), 'Integer'];
    }

    global $user;
    if (in_array('client administrator', $user->roles)) {
      $hiddenInstances = [
        'Contribution History by Campaign Group (Summary)',
        'Contribution History by Campaign Group (Detailed)',
      ];
      $report .= " AND inst.title NOT IN ('" . implode("', '", $hiddenInstances) . "') ";
    }

    $sql = "
        SELECT inst.id, inst.title, inst.report_id, inst.description,  inst.owner_id, v.label, v.grouping, v.name as class_name, r.section_id,
          CASE
          WHEN comp.name IS NOT NULL THEN SUBSTRING(comp.name, 5)
          WHEN v.grouping IS NOT NULL THEN v.grouping
          ELSE 'Contact'
          END as compName
          FROM civicrm_option_group g
          LEFT JOIN civicrm_option_value v
                 ON v.option_group_id = g.id AND
                    g.name  = 'report_template'
          LEFT JOIN civicrm_report_instance inst
                 ON v.value = inst.report_id
          LEFT JOIN civicrm_component comp
                 ON v.component_id = comp.id
          LEFT JOIN civicrm_report_instance_organizer r
              ON r.report_instance_id = inst.id AND r.component_id = v.component_id

          WHERE v.is_active = 1 {$report}
                AND inst.domain_id = %9
          ORDER BY  v.weight ASC, inst.title ASC";
    $queryParams[9] = [CRM_Core_Config::domainID(), 'Integer'];

    $dao = CRM_Core_DAO::executeQuery($sql, $queryParams);

    $config = CRM_Core_Config::singleton();
    $rows = [];
    $url = 'civicrm/report/instance';
    $my_reports_grouping = 'My';
    $sections = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => "component_section",
    ]);
    foreach ($sections['values'] as $section) {
      $sectionLabels[$section['value']] = $section['label'];
    }
    while ($dao->fetch()) {
      if (in_array($dao->report_id, self::$_exceptions)) {
        continue;
      }

      $enabled = in_array("Civi{$dao->compName}", $config->enableComponents);
      if ($dao->compName == 'Contact' || $dao->compName == $dao->grouping) {
        $enabled = TRUE;
      }

      // filter report listings for private reports
      if (!empty($dao->owner_id) && CRM_Core_Session::getLoggedInContactID() != $dao->owner_id) {
        continue;
      }

      //filter report listings by permissions
      if (!($enabled && CRM_Report_Utils_Report::isInstancePermissioned($dao->id))) {
        continue;
      }
      //filter report listing by group/role
      if (!($enabled && CRM_Report_Utils_Report::isInstanceGroupRoleAllowed($dao->id))) {
        continue;
      }

      if (trim($dao->title)) {
        if ($this->ovID) {
          $this->title = ts("Report(s) created from the template: %1", [1 => $dao->label]);
        }

        $report_grouping = ts($dao->compName);
        if ($dao->owner_id != NULL) {
          $report_grouping = ts($my_reports_grouping);
        }
        $report_sub_grouping = NULL;
        if ($dao->section_id) {
          $report_sub_grouping = $sectionLabels[$dao->section_id];
        }
        if ($report_sub_grouping) {
          $rows[$report_grouping]['accordion'][$report_sub_grouping][$dao->id]['title'] = $dao->title;
          $rows[$report_grouping]['accordion'][$report_sub_grouping][$dao->id]['label'] = $dao->label;
          $rows[$report_grouping]['accordion'][$report_sub_grouping][$dao->id]['description'] = $dao->description;
          $rows[$report_grouping]['accordion'][$report_sub_grouping][$dao->id]['url'] = CRM_Utils_System::url("{$url}/{$dao->id}", "reset=1&output=criteria");
          $rows[$report_grouping]['accordion'][$report_sub_grouping][$dao->id]['viewUrl'] = CRM_Utils_System::url("{$url}/{$dao->id}", 'force=1&reset=1');
          $rows[$report_grouping]['accordion'][$report_sub_grouping][$dao->id]['actions'] = $this->getActionLinks($dao->id, $dao->class_name);
        }
        else {
          $rows[$report_grouping]['no_accordion'][$dao->id]['title'] = $dao->title;
          $rows[$report_grouping]['no_accordion'][$dao->id]['label'] = $dao->label;
          $rows[$report_grouping]['no_accordion'][$dao->id]['description'] = $dao->description;
          $rows[$report_grouping]['no_accordion'][$dao->id]['url'] = CRM_Utils_System::url("{$url}/{$dao->id}", "reset=1&output=criteria");
          $rows[$report_grouping]['no_accordion'][$dao->id]['viewUrl'] = CRM_Utils_System::url("{$url}/{$dao->id}", 'force=1&reset=1');
          $rows[$report_grouping]['no_accordion'][$dao->id]['actions'] = $this->getActionLinks($dao->id, $dao->class_name);
        }
      }
    }

    // Move My Reports to the beginning of the reports list
    if (isset($rows[$my_reports_grouping])) {
      $my_reports = $rows[$my_reports_grouping];
      unset($rows[$my_reports_grouping]);
      $rows = [$my_reports_grouping => $my_reports] + $rows;
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
      'Contribution History by Campaign',
      'Contribution History by Campaign Group',
      'Contribution History by Fund',
      'Contribution History by GL Account',
      'Custom Contribution Reports',
    ];
    $sortedSections = CRM_Reportorganizer_Utils::accordionSorter('Contribute', $contributionSectionOrder, $rows);
    if (!empty($sortedSections)) {
      $rows['Contribute']['accordion'] = $sortedSections;
    }

    // Handle sorting of reserved instances
    $contribNoAccordionOrder = [
      'Contribution History by Source (Summary)',
      'Recurring Contributions (Summary)',
      'Receipts',
    ];
    $sortedSections = CRM_Reportorganizer_Utils::noAccordionSorter('Contribute', $contribNoAccordionOrder, $rows);
    if (!empty($sortedSections)) {
      $rows['Contribute']['no_accordion'] = $sortedSections;
    }

    $contactNoAccordionOrder = [
      "Contact Report (Detailed)",
      "Activity Report",
      "New Email Replies",
      "Relationship Report",
    ];
    $sortedSections = CRM_Reportorganizer_Utils::noAccordionSorter('Contact', $contactNoAccordionOrder, $rows);
    if (!empty($sortedSections)) {
      $rows['Contact']['no_accordion'] = $sortedSections;
    }

    // Handle sorting for report instances within the sections.
    $instanceSections = [
      "Contribution History by Campaign" => [
        "Contribution History by Campaign (Summary)",
        "Contribution History by Campaign (Detailed)",
        "Contribution History by Campaign (Monthly)",
        "Contribution History by Campaign (Yearly)",
      ],
      "Contribution History by Campaign Group" => [
        "Contribution History by Campaign Group (Summary)",
        "Contribution History by Campaign Group (Detailed)",
      ],
      "Contribution History by Fund" => [
        "Contribution History by CH Fund (Summary)",
        "Contribution History by Fund (Summary)",
        "Contribution History by Fund (Detailed)",
        "Contribution History by Fund (Monthly)",
        "Contribution History by Fund (Yearly)",
      ],
      "Contribution History by GL Account" => [
        "Contribution History by GL Account (Summary)",
        "Contribution History by GL Account (Detailed)",
      ],
    ];
    foreach ($instanceSections as $header => $sortOrder) {
      $sortedSections = CRM_Reportorganizer_Utils::insideAccordionSorter('Contribute', $header, $sortOrder, $rows);
      if (!empty($sortedSections)) {
        $rows['Contribute']['accordion'][$header] = $sortedSections;
      }
    }

    $rows = CRM_Reportorganizer_Utils::sortArrayByArray($rows, ["My", "Contribute", "Contact", "Opportunity"]);
    return $rows;
  }

  /**
   * Run this page (figure out the action needed and perform it).
   */
  public function run() {
    CRM_Utils_System::setTitle(ts('CanadaHelps DMS Reports'));
    //Filters by source report template or by component
    $this->ovID = CRM_Utils_Request::retrieve('ovid', 'Positive', $this);
    $this->myReports = CRM_Utils_Request::retrieve('myreports', 'String', $this);
    $this->compID = CRM_Utils_Request::retrieve('compid', 'Positive', $this);
    $this->grouping = CRM_Utils_Request::retrieve('grp', 'String', $this);

    $rows = $this->info();

    $this->assign('list', $rows);
    if ($this->ovID or $this->compID) {
      // link to view all reports
      $reportUrl = CRM_Utils_System::url('civicrm/report/list', "reset=1");
      $this->assign('reportUrl', $reportUrl);
      if ($this->ovID) {
        $this->assign('title', $this->title);
      }
      else {
        CRM_Utils_System::setTitle(ts('%1 Reports', [1 => $this->_compName]));
      }
    }
    // assign link to template list for users with appropriate permissions
    if (CRM_Core_Permission::check('administer Reports')) {
      if ($this->compID) {
        $newButton = ts('New %1 Report', [1 => $this->_compName]);
        $templateUrl = CRM_Utils_System::url('civicrm/report/template/list', "reset=1&compid={$this->compID}");
      }
      else {
        $newButton = ts('New Report');
        $templateUrl = CRM_Utils_System::url('civicrm/report/template/list', "reset=1");
      }
      $this->assign('newButton', $newButton);
      $this->assign('templateUrl', $templateUrl);
      $this->assign('compName', $this->_compName);
      $this->assign('myReports', $this->myReports);
    }
    return parent::run();
  }

  /**
   * Get action links.
   *
   * @param int $instanceID
   * @param string $className
   *
   * @return array
   */
  protected function getActionLinks($instanceID, $className) {
    $urlCommon = 'civicrm/report/instance/' . $instanceID;
    $actions = [
      'copy' => [
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&output=copy'),
        'label' => ts('Save a Copy'),
      ],
      'pdf' => [
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&force=1&output=pdf'),
        'label' => ts('View as pdf'),
      ],
      'print' => [
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&force=1&output=print'),
        'label' => ts('Print report'),
      ],
    ];
    // Hackery, Hackera, Hacker ahahahahahaha a super nasty hack.
    // Almost all report classes support csv & loading each class to call the method seems too
    // expensive. We also have on our later list 'do they support charts' which is instance specific
    // e.g use of group by might affect it. So, lets just skip for the few that don't for now.
    $csvBlackList = [
      'CRM_Report_Form_Contact_Detail',
      'CRM_Report_Form_Event_Income',
    ];
    if (!in_array($className, $csvBlackList)) {
      $actions['csv'] = [
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&force=1&output=csv'),
        'label' => ts('Export to csv'),
      ];
    }
    if (CRM_Core_Permission::check('administer Reports')) {
      $actions['delete'] = [
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&action=delete'),
        'label' => ts('Delete report'),
        'confirm_message' => ts('Are you sure you want delete this report? This action cannot be undone.'),
      ];
    }
    CRM_Utils_Hook::links('view.report.links',
      $className,
      $instanceID,
      $actions
    );

    return $actions;
  }

}
