<?php
/**
 * @class Pelf utility class.
 */

class CRM_Pelf
{
  /** Holds a cache of things we have to look up. */
  public static $custom_fields = [];

  /** Holds a cache of things we have to look up. */
  public static $activity_types = [];

  /**
   * Fetches an API-ready code name for one of our custom fields.
   *
   * Custom fields we use:
   * - pelf_stage
   * - pelf_est_worth
   * - pelf_total_worth
   *
   */
  public static function getFieldId($field_name) {

    if (!isset(static::$custom_fields[$field_name])) {
      // Single lookup for all our fields.
      $fields = civicrm_api3('CustomField', 'get', ['custom_group_id' => ['IN' => ['pelf_prospect', 'pelf_contract']]]);
      foreach ($fields['values'] as $_) {
        static::$custom_fields[$_['name']] = 'custom_' . $_['id'];
      }
    }

    return static::$custom_fields[$field_name];
  }

  public static function getProspectActivityType() {
    if (!isset(static::$activity_types['pelf_prospect_activity_type'])) {
      static::loadActivityTypes();
    }
    return static::$activity_types['pelf_prospect_activity_type'];
  }
  public static function getContractActivityType() {
    if (!isset(static::$activity_types['pelf_contract_activity_type'])) {
      static::loadActivityTypes();
    }
    return static::$activity_types['pelf_contract_activity_type'];
  }
  public static function loadActivityTypes() {
    $result = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => "activity_type",
      'name' => array('LIKE' => "pelf%"),
    ]);
    foreach ($result['values'] as $_) {
      static::$activity_types[$_['name']] = $_['value'];
    }
  }


}


