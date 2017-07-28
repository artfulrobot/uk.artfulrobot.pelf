<?php

/**
 * Pelf.GetConfig API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_pelf_GetConfig_spec(&$spec) {
}

/**
 * Pelf.GetConfig API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_pelf_GetConfig($params) {
  $pelf = CRM_Pelf::service();
  $data = [
    'prospect' => [
      'stages' => CRM_Pelf::$prospect_stages,
    ]
  ];
  foreach ($pelf->custom_fields as $name => $details) {
    $data['prospect']['apiFieldNames'][$name] = $pelf->getApiFieldName($name);
  }
  return $data;
}

