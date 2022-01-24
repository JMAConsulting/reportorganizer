<?php
use CRM_Reportorganizer_ExtensionUtil as E;

/**
 * Job.Updatesections API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_job_Updatesections_spec(&$spec) {
}

/**
 * Job.Updatesections API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_job_Updatesections($params) {
  $returnValues = CRM_Reportorganizer_Utils::updateSections();
  return civicrm_api3_create_success($returnValues, $params, 'Job', 'Updatesections');
}
