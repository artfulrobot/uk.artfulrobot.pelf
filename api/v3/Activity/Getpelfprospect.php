<?php

/**
 * Activity.Getpelfprospect API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_activity_Getpelfprospect_spec(&$spec) {
  $spec['id']['api.required'] = 1;
  $spec['id']['name'] = ts('Activity ID');
  $spec['id']['description'] = ts('The Activity ID of this prospect');
}

/**
 * Activity.Getpelfprospect API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_activity_Getpelfprospect($params) {

  $data = [];
  $_ = ['id' => $params['id']];
  $stage = CRM_Pelf::getFieldId('pelf_stage');
  $est_worth = CRM_Pelf::getFieldId('pelf_est_worth');
  $_['return'] = "id,activity_type_id,subject,$stage";
  $activity = civicrm_api3('Activity', 'getsingle', $_);
  if ($activity['activity_type_id'] != CRM_Pelf::getProspectActivityType()) {
    throw new API_Exception("Not a Prospect");
  }

  foreach ([
    'id', 'subject',
    $stage => 'stage',
    $est_worth => 'est_worth'
    ] as $i => $out) {

    if (is_int($i)) {
      $i = $out;
    }
    $data[$out] = isset($activity[$i]) ? $activity[$i] : NULL;
  }

  // Get names of all contacts.
  $activity_contacts = civicrm_api3('ActivityContact', 'get', [
    'return' => 'contact_id',
    'activity_id' => $params['id'],
    'record_type_id' => 3, // 'Activity Target',
    'sequential' => TRUE,
  ]);
  $contact_ids = array_map(function($_) { return $_['contact_id']; }, $activity_contacts['values']);
  $contacts = civicrm_api3('Contact', 'get', [
    'id' => ['IN' => $contact_ids],
    'return' => 'id,display_name',
    'sequential' => TRUE,
  ]);
  $data['contactWith'] = $contacts['values'];


  // Load all funding records.
  $result = civicrm_api3('PelfFunding', 'get', ['activity_id' => $activity['id'], 'sequential' => TRUE, 'options' => ['sort' => 'financial_year']]);
  $data['funding'] = $result['values'];

  return civicrm_api3_create_success($data, $params, 'Activity', 'GetPelfProspect');
}

