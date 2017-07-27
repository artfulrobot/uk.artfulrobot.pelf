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

  $_ = ['id' => $params['id']];
  $stage       = CRM_Pelf::getFieldId('pelf_stage');
  $est_amount  = CRM_Pelf::getFieldId('pelf_est_amount');
  $scale       = CRM_Pelf::getFieldId('pelf_prospect_scale');
  // Set up default return data.
  $data = [
    'id' => null,
    'activity_type_id' => CRM_Pelf::getProspectActivityType(),
    'contactWith' => [],
    'date' => date('Y-m-d H:i:s'),
    'details' => '',
    'est_amount' => '',
    'funding' => [],
    'related_activities' => [],
    'stage' => 'speculative',
    'subject' => '',
    'field_map' => [
      'stage'      => $stage,
      'est_amount' => $est_amount,
      'scale'      => $scale,
    ],
  ];
  // Send the labels and values for stages.
  $result = civicrm_api3('OptionValue', 'get', array(
    'sequential' => 1,
    'return' => array("value", "label"),
    'option_group_id' => "pelf_stage_opts",
  ));
  $data['stages'] = $result['values'];


  if ($params['id'] == 'add') {
    // Return a new, unsaved prospect object.
    return $data;
  }

  $activity    = civicrm_api3('Activity', 'getsingle', $_);
  $_['return'] = "id,activity_type_id,subject,activity_date_time,details,$stage,$est_amount,$scale";
  if ($activity['activity_type_id'] != CRM_Pelf::getProspectActivityType()) {
    throw new API_Exception("Not a Prospect");
  }

  foreach ([
    'id', 'subject', 'details',
    $stage               => 'stage',
    $est_amount          => 'est_amount',
    $scale               => 'scale',
    'activity_date_time' => 'date',
    ] as $i => $out) {

    if (is_int($i)) {
      $i = $out;
    }
    $data[$out] = isset($activity[$i]) ? $activity[$i] : NULL;
  }

  // Get names of all contacts.
  $data['contactWith'] = [];
  $activity_contacts = civicrm_api3('ActivityContact', 'get', [
    'return' => 'contact_id',
    'activity_id' => $params['id'],
    'record_type_id' => 3, // 'Activity Target',
    'sequential' => TRUE,
  ]);
  if ($activity_contacts) {
    $contact_ids = array_map(function($_) { return $_['contact_id']; }, $activity_contacts['values']);

    $contacts = civicrm_api3('Contact', 'get', [
      'id' => ['IN' => $contact_ids],
      'return' => 'id,display_name',
    ]);
    foreach ($activity_contacts['values'] as $ac) {
      $contact = $contacts['values'][$ac['contact_id']];
      $data['contactWith'][] = [
        'activity_contact_id' => $ac['id'],
        'contact_id'          => $contact['id'],
        'display_name'        => $contact['display_name'],
        ];
    }
  }
  // Load all funding records.
  $result = civicrm_api3('PelfFunding', 'get', ['activity_id' => $activity['id'], 'sequential' => 1]);
  $data['funding'] = $result['values'];

  if (!empty($params['with_activities'])) {
    // Look up activities for these contacts, since the prospect's date.
    $query_params = [];
    $i=1;
    $sql_contact_ids = [];
    foreach ($contact_ids as $contact_id) {
      $sql_contact_ids[] = "%$i";
      $query_params[$i] = [$contact_id, 'Integer'];
      $i++;
    }
    $sql_contact_ids = implode(', ', $sql_contact_ids);
    $query_params[$i] = [$activity['activity_date_time'], 'String'];
    $sql = "SELECT a.id, activity_date_time, subject, activity_type_id "
      . 'FROM civicrm_activity a '
      . "WHERE activity_date_time >= %$i "
      . 'AND EXISTS ( '
      . "  SELECT activity_id FROM civicrm_activity_contact WHERE activity_id = a.id AND contact_id IN ($sql_contact_ids) "
      . ')';
    $dao = CRM_Core_DAO::executeQuery($sql, $query_params);
    $activities = [];
    $activity_types = [];
    while ($dao->fetch()) {
      $activity_types[$dao->activity_type_id] = TRUE;
      $activities[$dao->id] = $dao->toArray() +
        ['targets' => [], 'assignees' => []];
    }
    $dao->free();
    // Remove self!
    unset($activities[$params['id']]);

    // Get the assignee and target contacts for these activities.
    $results = civicrm_api3('ActivityContact', 'get', ['activity_id' => ['IN' => array_keys($activities)]]);
    $unique_contact_ids = [];
    foreach ($results['values'] as $ac) {
      if ($ac['record_type_id'] == 1) {
        $activities[$ac['activity_id']]['assignees'][$ac['contact_id']] = '';
      }
      elseif ($ac['record_type_id'] == 3) {
        if (count($activities[$ac['activity_id']]['targets']) > 19) {
          continue; // ignore more than 20 contcts!
        }
        $activities[$ac['activity_id']]['targets'][$ac['contact_id']] = '';
      }
      $unique_contact_ids[$ac['contact_id']] = TRUE;
    }
    // Batch fetch all unique contacts.
    $results = civicrm_api3('Contact', 'get', ['id' => ['IN' => array_keys($unique_contact_ids)], 'return' => 'id,display_name']);
    $contacts = [];
    foreach ($results['values'] as $_) {
      $contacts[$_['id']] = $_['display_name'];
    }

    // Get activity type definitions.
    $results = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'return' => array("value", "label"),
      'option_group_id' => "activity_type",
      'value' => ['IN' => array_keys($activity_types)]
    ]);
    foreach ($results['values'] as $_) {
      $activity_types[$_['value']] = $_['label'];
    }

    // Merge contact names into activities.
    foreach ($activities as &$activity) {
      // Activity type.
      $activity['activity_type'] = $activity_types[$activity['activity_type_id']];
      // Contacts.
      foreach (['assignees', 'targets'] as $type) {
        foreach (array_keys($activity[$type]) as $contact_id) {
          $activity[$type][$contact_id] = $contacts[$contact_id];
        }
      }
    }

    usort($activities, function($a, $b) {
      return strcasecmp($a['activity_date_time'], $b['activity_date_time']);
    });

    $data['related_activities'] = array_values($activities);

  }


  return $data;
}

