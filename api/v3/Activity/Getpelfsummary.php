<?php

/**
 * Activity.Getpelfsummary API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_activity_Getpelfsummary_spec(&$spec) {
}

/**
 * Activity.Getpelfsummary API
 *
 * Returns various info.
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_activity_Getpelfsummary($params) {
  $data = CRM_Pelf::service()->getSummary();
  return $data;
}


