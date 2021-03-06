<?php

require_once 'reportorganizer.civix.php';
require_once 'CRM/Reportorganizer/Utils.php';
// phpcs:disable
use CRM_Reportorganizer_ExtensionUtil as E;
use CRM_Reportorganizer_Utils as R;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function reportorganizer_civicrm_config(&$config) {
  _reportorganizer_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function reportorganizer_civicrm_xmlMenu(&$files) {
  _reportorganizer_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function reportorganizer_civicrm_install() {
  // Add a component ID for CiviContact
  $check = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_component WHERE name = 'CiviContact'");
  if (!$check) {
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_component (name, namespace) VALUES ('CiviContact', 'CRM_Contact')");
    $check = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_component WHERE name = 'CiviContact'");
  }
  // Update report templates to include component ID for CiviContact.
  R::updateReportTemplates($check);
  R::renameReportInstances();
  R::renameReportTemplates();
  $templateCheck = civicrm_api3('OptionGroup', 'get', ['name' => 'component_template_section']);
  if (empty($templateCheck['values'])) {
    civicrm_api3('OptionGroup', 'create', [
      'name' => "component_template_section",
      'title' => "Component Report Template Section",
      'is_reserved' => 1,
      'is_active' => 1,
      'is_locked' => 1,
    ]);
  }
  $instanceCheck = civicrm_api3('OptionGroup', 'get', ['name' => 'component_section']);
  if (empty($instanceCheck['values'])) {
    civicrm_api3('OptionGroup', 'create', [
      'name' => "component_section",
      'title' => "Component Report Instance Section",
      'is_reserved' => 1,
      'is_active' => 1,
      'is_locked' => 1,
    ]);
  }
  _reportorganizer_civix_civicrm_install();
}

function reportorganizer_civicrm_pageRun(&$page) {
  $pageName = $page->getVar('_name');
  if (in_array($pageName, ["CRM_Reportorganizer_Page_ReportInstanceList", "CRM_Reportorganizer_Page_ReportTemplateList"])) {
    CRM_Core_Resources::singleton()->addStyle('
      .crm-accordion-body {
        margin-left: 13px;
      }
    ');
  }
}

function reportorganizer_civicrm_buildForm($formName, &$form) {
  $class = class_parents($formName);
  if (array_search("CRM_Report_Form", $class)) {
    CRM_Core_Region::instance('page-body')->add(array(
      'script' => "
      CRM.$(function($) {
        $('#crm-container').on('change', 'select#task', function() {
          setTimeout(function waitConfirm() {
            $('.crm-confirm').find('#add_to_my_reports').change(function() {
              if ($(this).is(':checked')) {
                $('#add_to_my_reports').prop('checked', true);
              }
              else {
                $('#add_to_my_reports').prop('checked', false);
              }
            });
          }, 250);
        });
      });
      ",
    ));
  }
}

function reportorganizer_civicrm_alterReportVar($varType, &$var, $reportForm) {
  if ($varType == 'actions' && !empty($var['report_instance.copy'])) {
    $confirmFields = json_decode($var['report_instance.copy']['data']['confirm_refresh_fields'], TRUE);
    $confirmFields['add_to_my_reports'] = [
      'selector' => '.crm-report-instanceForm-form-block-add-to-my-reports',
      'prepend' => '',
    ];
    $var['report_instance.copy']['data']['confirm_refresh_fields'] = json_encode($confirmFields);
  }
}

function reportorganizer_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($objectName == "ReportInstance" && !is_numeric($objectRef->owner_id)) {
    // Check to see if we have a match in our table.
    $dao = new CRM_Reportorganizer_DAO_ReportOrganizer();
    $dao->report_instance_id = $objectId;
    $dao->find(TRUE);
    if (empty($dao->id)) {
      // We save this as a custom report.
      // Get the component ID.
      $componentId = CRM_Core_DAO::singleValueQuery("SELECT v.component_id
        FROM civicrm_report_instance r
        INNER JOIN civicrm_option_value v ON v.value = r.report_id
        INNER JOIN civicrm_option_group g ON g.id = v.option_group_id AND g.name = 'report_template'
        WHERE r.id = %1", [1 => [$objectId, 'Integer']]);
      if (!empty($componentId)) {
        $dao->component_id = $componentId;
        $dao->section_id = CRM_Core_DAO::singleValueQuery("SELECT v.value
        FROM civicrm_option_value v
        INNER JOIN civicrm_option_group g ON g.id = v.option_group_id AND g.name = 'component_section'
        WHERE v.component_id = %1 AND v.label LIKE 'Custom%'", [1 => [$componentId, 'Integer']]);
        $dao->report_instance_id = $objectId;
        $dao->save();
      }
    }
  }
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function reportorganizer_civicrm_postInstall() {
  _reportorganizer_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function reportorganizer_civicrm_uninstall() {
  _reportorganizer_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function reportorganizer_civicrm_enable() {
  CRM_Core_Invoke::rebuildMenuAndCaches( );
  _reportorganizer_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function reportorganizer_civicrm_disable() {
  _reportorganizer_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function reportorganizer_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _reportorganizer_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function reportorganizer_civicrm_managed(&$entities) {
  _reportorganizer_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function reportorganizer_civicrm_caseTypes(&$caseTypes) {
  _reportorganizer_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function reportorganizer_civicrm_angularModules(&$angularModules) {
  _reportorganizer_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function reportorganizer_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _reportorganizer_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function reportorganizer_civicrm_entityTypes(&$entityTypes) {
  _reportorganizer_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function reportorganizer_civicrm_themes(&$themes) {
  _reportorganizer_civix_civicrm_themes($themes);
}

/**
 * Implements hook_civicrm_entityRefFilters().
 */
function reportorganizer_civicrm_entityRefFilters(&$filters, &$links) {
  $contactFilters = CRM_Contact_BAO_Contact::getEntityRefFilters();
  $contactLinks = CRM_Contact_BAO_Contact::getEntityRefCreateLinks();
  if ($contactFilters) {
    $filters['Contact'] = $contactFilters;
  }
  if ($contactLinks) {
    $links['Contact'] = $contactLinks;
  }
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function reportorganizer_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function reportorganizer_civicrm_navigationMenu(&$menu) {
//  _reportorganizer_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _reportorganizer_civix_navigationMenu($menu);
//}
