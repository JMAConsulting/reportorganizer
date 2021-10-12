<?php
use CRM_Reportorganizer_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Reportorganizer_Upgrader extends CRM_Reportorganizer_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   *
  public function install() {
  $this->executeSqlFile('sql/myinstall.sql');
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   */
  public function postInstall() {
    // Add entries in component report instance section.
    $contribComponent = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_component WHERE name = 'CiviContribute'");
    $contactComponent = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_component WHERE name = 'CiviContact'");
    $opportunityComponent = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_component WHERE name = 'CiviGrant'");

    // Add entries in component report template section.
    $templateSections = [
      $contribComponent => [
        "General Contribution Reports" => [
          "Contributions (Summary)",
          "Contributions (Detailed)",
          "Repeat Contributions",
          "Top Donors",
          "SYBUNT",
          "LYBNT",
          "Contributions by Organization",
          "Contributions by Household",
          "Constributions by Relationship",
          "Contributions for Bookkeeping",
          "Contributions (Extended, Summary)",
          "Contributions (Detailed)",
          "Contributions (Extended, Pivot Chart)",
          "Contributions (Extended, Extra Fields)",
          "Contributions for Bookkeeping (Detailed)",
        ],
        "Recurring Contribution Reports" => [
          "Recurring Contributions (Summary)",
          "Recurring Contributions (Detailed)",
          "Recurring Contributions (Extended, Pivot Chart)",
          "Recurring Contributions (Detailed)",
        ],
        "Receipt Reports" => [
          "Tax Receipts (Issued)",
          "Tax Receipts (Not Yet Issued)",
        ],
      ],
      $contactComponent => [
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
      ],
    ];
    foreach ($templateSections as $component => $sectionHeader) {
      foreach($sectionHeader as $header => $reportTemplate) {
        $optionVal = civicrm_api3('OptionValue', 'create', [
          'option_group_id' => 'component_template_section',
          'label' => $header,
          'component_id' => $component,
        ]);
        // Fetch the report template by label.
        $template = civicrm_api3("ReportTemplate", "get", [
          "sequential" => 1,
          "label" => $reportTemplate,
        ]);
        if (!empty($optionVal['id']) && !empty($template['id'])) {
          $dao = new CRM_Reportorganizer_BAO_ReportTemplateOrganizer();
          $dao->component_id = $component;
          $dao->section_id = $optionVal['values'][$optionVal['id']]['value'];
          $dao->report_template_id = $template['id'];
          $dao->find(TRUE);
          $dao->save();
          $dao->free();
        }
      }
    }

    $instanceSections = [
      $contribComponent => [
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
        "Custom Contribution Reports" => [],
      ],
      $contactComponent => [
        "Custom Contact Reports" => []
      ],
      $opportunityComponent => [
        "Custom Opportunity Reports" => [],
      ]
    ];
    foreach ($instanceSections as $component => $sectionHeader) {
      foreach ($sectionHeader as $header => $instanceTitle) {
        $optionVal = civicrm_api3('OptionValue', 'create', [
          'option_group_id' => 'component_section',
          'label' => $header,
          'component_id' => $component,
        ]);
        if (!empty($instanceTitle)) {
          $instance = civicrm_api3("ReportInstance", "get", [
            "sequential" => 1,
            "title" => $instanceTitle,
          ]);
          if (!empty($instance['id']) && $optionVal['id']) {
            $dao = new CRM_Reportorganizer_DAO_ReportOrganizer();
            $dao->component_id = $component;
            $dao->section_id = $optionVal['values'][$optionVal['id']]['value'];
            $dao->report_instance_id = $instance['id'];
            $dao->find(TRUE);
            $dao->save();
            $dao->free();
          }
        }
      }
    }

    // Now do the actual entries for the sections.

  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   */
  // public function uninstall() {
  //  $this->executeSqlFile('sql/myuninstall.sql');
  // }

  /**
   * Example: Run a simple query when a module is enabled.
   */
  // public function enable() {
  //  CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  // }

  /**
   * Example: Run a simple query when a module is disabled.
   */
  // public function disable() {
  //   CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  // }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4200() {
  //   $this->ctx->log->info('Applying update 4200');
  //   CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
  //   CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
  //   return TRUE;
  // }


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4201() {
  //   $this->ctx->log->info('Applying update 4201');
  //   // this path is relative to the extension base dir
  //   $this->executeSqlFile('sql/upgrade_4201.sql');
  //   return TRUE;
  // }


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4202() {
  //   $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

  //   $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
  //   $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
  //   $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
  //   return TRUE;
  // }
  // public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  // public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  // public function processPart3($arg5) { sleep(10); return TRUE; }

  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4203() {
  //   $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

  //   $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
  //   $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
  //   for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
  //     $endId = $startId + self::BATCH_SIZE - 1;
  //     $title = E::ts('Upgrade Batch (%1 => %2)', array(
  //       1 => $startId,
  //       2 => $endId,
  //     ));
  //     $sql = '
  //       UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
  //       WHERE id BETWEEN %1 and %2
  //     ';
  //     $params = array(
  //       1 => array($startId, 'Integer'),
  //       2 => array($endId, 'Integer'),
  //     );
  //     $this->addTask($title, 'executeSql', $sql, $params);
  //   }
  //   return TRUE;
  // }

}
