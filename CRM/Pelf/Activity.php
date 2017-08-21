<?php
/**
 * @file
 * Holds code in common between CRM_Pelf_Prospect and CRM_Pelf_Contract.
 *
 */
class CRM_Pelf_Activity {

  /**
   * Holds all our data.
   */
  public $data;

  /**
   * @var int The activity type id for this activity. Set in constructor.
   */
  public $activity_type_id;

  /**
   * Returns a single object, either an unsaved one or a loaded one.
   */
  public static function factorySingle($id=NULL) {
    $obj = new static();

    if ($id !== NULL) {
      $obj->loadFromDatabase($id);
      $activities = [$id => $obj];
      static::loadContactsIntoCollection($activities);
      static::loadFundingRecordsIntoCollection($activities);
    }

    return $obj;
  }

  public static function factoryCollection($params) {
    // Fetch a collection.
    // @todo for now, fetch all!
    $api_params = [
      'options' => ['limit' => 0],
    ] + static::getBaseApiParams();
    $result = civicrm_api3('Activity', 'get', $api_params);
    $activities = [];
    foreach ($result['values'] as $_) {
      $activities[$_['id']] = static::factorySingle()->importBaseData($_);
    }
    static::loadContactsIntoCollection($activities);
    static::loadFundingRecordsIntoCollection($activities);
    return $activities;
  }

  /**
   * Look up contacts for these activities.
   *
   * In each activity's data it populates contactWith and contactAssigned with
   * - activity_contact ID
   * - contact_id
   * - display_name
   *
   * @param array of CRM_Pelf_Activity objects, keyed by activity_id
   * @return array
   */
  public static function loadContactsIntoCollection($activities) {
    // Collect Ids.
    $activity_ids = array_keys($activities);
    // Reset.
    foreach ($activities as $activity) {
      $activity->data['contactWith'] = [];
      $activity->data['contactAssigned'] = [];
    }

    // Get names of all contacts.
    $data['contactWith'] = [];
    $activity_contacts = civicrm_api3('ActivityContact', 'get', [
      'activity_id' => ['IN' => $activity_ids],
      //'record_type_id' => 3, // 'Activity Target',
      'sequential' => TRUE,
      'options' => ['limit' => 0],
    ]);
    if ($activity_contacts['count']) {

      // Extract all unique contact ids from the activity_contact records.
      $contact_ids = array_unique(array_map(function($_) { return $_['contact_id']; }, $activity_contacts['values']));
      $contacts = civicrm_api3('Contact', 'get', [
        'id' => ['IN' => $contact_ids],
        'return' => 'id,display_name',
      ]);

      // Assign all this to the correct activities.
      foreach ($activity_contacts['values'] as $ac) {
        if ($ac['record_type_id'] == 3) {
          $type = 'contactWith';
        }
        elseif ($ac['record_type_id'] == 1) {
          $type = 'contactAssigned';
        }
        else {
          // Don't care who added it.
          continue;
        }
        $contact = $contacts['values'][$ac['contact_id']];

        $activities[$ac['activity_id']]->data[$type][] = [
          'activity_contact_id' => $ac['id'],
          'contact_id'          => $contact['id'],
          'display_name'        => $contact['display_name'],
        ];
      }
    }
    return $activities;
  }
  /**
   * Load all funding records for the collection.
   *
   * @param array of CRM_Pelf_Activity objects, keyed by activity_id
   * @return array
   */
  public static function loadFundingRecordsIntoCollection($activities) {
    $activity_ids = array_keys($activities);
    $result = civicrm_api3('PelfFunding', 'get', [
      'activity_id' => ['IN' => $activity_ids],
      'sequential' => 1,
    ]);
    // Reset.
    foreach ($activities as $activity) {
      $activity->data['funding'] = [];
    }
    // Load.
    $activities[$f['activity_id']]['funding'] = $f;
    foreach ($result['values'] as $f) {
      $activities[$f['activity_id']]->data['funding'][] = $f;
    }
    return $activities;
  }
  /**
   * Shared by factory methods.
   */
  public static function getBaseApiParams() {
    throw new Exception("Implement this");
  }

  /**
   * Load an activity into this object.
   */
  public function loadFromDatabase($id) {

    $api_params = static::getBaseApiParams();
    $api_params['id'] = $id;
    $activity    = civicrm_api3('Activity', 'getsingle', $api_params);
    if ($activity['activity_type_id'] != $this->activity_type_id) {
      throw new API_Exception("Activity not valid for " . get_class($this));
    }
    $this->importBaseData($activity);
    return $this;

  }

  /**
   * Import the result of an API get activity request into this object.
   */
  public function importBaseData($activity) {
    $pelf = CRM_Pelf::service();
    $stage      = $pelf->getApiFieldName('pelf_stage');
    $est_amount = $pelf->getApiFieldName('pelf_est_amount');
    $scale      = $pelf->getApiFieldName('pelf_prospect_scale');
    $this->data = $this->getDefaultData();
    // Map the API result to something more manageable.
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
      $this->data[$out] = isset($activity[$i]) ? $activity[$i] : NULL;
    }
    return $this;
  }
  /**
   * Look up activities for these contacts, since the prospect's date.
   *
   * @return CRM_Pelf_Activity ($this)
   */
  public function loadRelatedActivities() {
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
    $rel_activities = [];
    $activity_types = [];
    while ($dao->fetch()) {
      $activity_types[$dao->activity_type_id] = TRUE;
      $rel_activities[$dao->id] = $dao->toArray() +
        ['targets' => [], 'assignees' => []];
    }
    $dao->free();
    // Remove self!
    unset($rel_activities[$activity['id']]);

    // Get the assignee and target contacts for these activities.
    if ($rel_activities) {
      $results = civicrm_api3('ActivityContact', 'get', ['activity_id' => ['IN' => array_keys($rel_activities)]]);
      $unique_contact_ids = [];
      foreach ($results['values'] as $ac) {
        if ($ac['record_type_id'] == 1) {
          $rel_activities[$ac['activity_id']]['assignees'][$ac['contact_id']] = '';
        }
        elseif ($ac['record_type_id'] == 3) {
          if (count($rel_activities[$ac['activity_id']]['targets']) > 19) {
            continue; // ignore more than 20 contcts!
          }
          $rel_activities[$ac['activity_id']]['targets'][$ac['contact_id']] = '';
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
      foreach ($rel_activities as &$activity) {
        // Activity type.
        $activity['activity_type'] = $activity_types[$activity['activity_type_id']];
        // Contacts.
        foreach (['assignees', 'targets'] as $type) {
          foreach (array_keys($activity[$type]) as $contact_id) {
            $activity[$type][$contact_id] = $contacts[$contact_id];
          }
        }
      }

      usort($rel_activities, function($a, $b) {
        return strcasecmp($a['activity_date_time'], $b['activity_date_time']);
      });

    }

    $data['related_activities'] = array_values($rel_activities);
  }
}

