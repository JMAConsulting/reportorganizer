<?php
use CRM_Reportorganizer_ExtensionUtil as E;

class CRM_Reportorganizer_BAO_ReportTemplateOrganizer extends CRM_Reportorganizer_DAO_ReportTemplateOrganizer {

  /**
   * Create a new ReportTemplateOrganizer based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Reportorganizer_DAO_ReportTemplateOrganizer|NULL
   *
  public static function create($params) {
    $className = 'CRM_Reportorganizer_DAO_ReportTemplateOrganizer';
    $entityName = 'ReportTemplateOrganizer';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

}
