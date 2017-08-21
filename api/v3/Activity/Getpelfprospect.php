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
  // $spec['id']['api.required'] = 1;
  $spec['id']['name'] = ts('Activity ID');
  $spec['id']['description'] = ts('The Activity ID of this prospect. Without this a collection is fetched.');
  $spec['with_activities'] = [
    'name' => ts('With Activities'),
    'description' => ts('Include activities related to the linked orgs since the start date?'),
    'type' => 'boolean',
  ];
}

/**
 * Activity.Getpelfprospect API
 *
 * Returns various info about a single prospect activity.
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_activity_Getpelfprospect($params) {

  if (empty($params['id'])) {
    // Collection.
    $activities = CRM_Pelf_Prospect::factoryCollection($params);
    $return_values = [];
    foreach ($activities as $activity_id => $activity) {
      $return_values[$activity_id] = $activity->data;
    }
    if (empty($params['sequential']) || !$params['sequential']) {
      $return_values = array_values($return_values);
    }
    return civicrm_api3_create_success($return_values, 'Activity', 'GetPelfProspect', $params);
  }
  elseif ($params['id'] == 'add') {
    // Return a new, unsaved prospect object.
    $activity = CRM_Pelf_Prospect::factorySingle();
  }
  else {
    // Single.
    $activity = CRM_Pelf_Prospect::factorySingle($params['id']);
    if (!empty($params['with_activities'])) {

//      $activity->loadRelatedActivities();
    }
    return $activity->data;
  }
}

