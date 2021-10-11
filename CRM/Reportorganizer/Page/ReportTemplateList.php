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
LEFT JOIN civicrm_report_template_organizer r
        ON r.report_template_id = v.id AND r.component_id = v.component_id
LEFT  JOIN civicrm_component comp
        ON v.component_id = comp.id
";

    if (!$all) {
      $sql .= " WHERE v.is_active = 1 {$compClause}";
    }
    $sql .= " ORDER BY  v.weight ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $rows = [];
    $config = CRM_Core_Config::singleton();
    $sectionLabels = CRM_Core_OptionGroup::values('component_template_section');
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
      if ($report_sub_grouping) {
        $rows[$dao->component_name]['accordion'][$report_sub_grouping][$dao->value]['title'] = ts($dao->label);
        $rows[$dao->component_name]['accordion'][$report_sub_grouping][$dao->value]['description'] = ts($dao->description);
        $rows[$dao->component_name]['accordion'][$report_sub_grouping][$dao->value]['url'] = CRM_Utils_System::url('civicrm/report/' . trim($dao->value, '/'), 'reset=1');
        if ($dao->instance_id) {
          $rows[$dao->component_name]['accordion'][$report_sub_grouping][$dao->value]['instanceUrl'] = CRM_Utils_System::url('civicrm/report/list',
            "reset=1&ovid={$dao->id}"
          );
        }
      }
      else {
        $rows[$dao->component_name]['no_accordion'][$dao->value]['title'] = ts($dao->label);
        $rows[$dao->component_name]['no_accordion'][$dao->value]['description'] = ts($dao->description);
        $rows[$dao->component_name]['no_accordion'][$dao->value]['url'] = CRM_Utils_System::url('civicrm/report/' . trim($dao->value, '/'), 'reset=1');
        if ($dao->instance_id) {
          $rows[$dao->component_name]['no_accordion'][$dao->value]['instanceUrl'] = CRM_Utils_System::url('civicrm/report/list',
            "reset=1&ovid={$dao->id}"
          );
        }
      }
    }

    foreach ($rows as &$row) {
      if (!empty($row['accordion'])) {
        $accordion = $row['accordion'];
        unset($row['accordion']);
        $row = ['accordion' => $accordion] + $row;
      }
    }
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
