<?php
/**
 * @class Pelf utility class.
 *
 */

class CRM_Pelf
{
  /** @var CRM_Pelf */
  public static $singleton_service;
  /** Holds a cache of things we have to look up. */
  public $custom_fields = [];

  /** Holds a cache of things we have to look up. */
  public $custom_groups = [];

  /** Holds a cache of things we have to look up. */
  public $activity_types = [];
  /**
   * All prospect stages.
   *
   * Nb. the NN number enables easy SQL sorting.
   */
  public static $prospect_stages = [
    "00_speculative"  => "Speculative; Seeking Invitation",
    "10_writing"      => "Writing proposal/tender",
    "20_waiting"      => "Awaiting result",
    "30_negotiating"  => "Negotiating",
    "40_successful"   => "Successful",
    "50_unsuccessful" => "Unsuccessful",
    "60_dropped"      => "Dropped by us",
  ];
  /**
   * List of Prospect statuses considered live.
   */
  public static $prospect_statuses_live = ["00_speculative", "10_writing", "20_waiting", "30_negotiating", "40_successful"];


  /**
   * Singleton.
   */
  public static function service() {
    if (!isset(static::$singleton_service)) {
      static::$singleton_service = new static();
    }
    return static::$singleton_service;
  }
  /**
   * Construct the singleton.
   */
  public function __construct() {

    // Get table details.
    $fields = civicrm_api3('CustomGroup', 'get', [
      'name' => ['IN' => ['pelf_prospect', 'pelf_contract']]]);
    foreach ($fields['values'] as $_) {
      $this->custom_groups[$_['name']] = $_;
    }

    // Single lookup for all our fields.
    $fields = civicrm_api3('CustomField', 'get', ['custom_group_id' => ['IN' => ['pelf_prospect', 'pelf_contract']]]);
    foreach ($fields['values'] as $_) {
      $this->custom_fields[$_['name']] = $_;
    }

    $result = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => "activity_type",
      'name' => array('LIKE' => "pelf%"),
    ]);
    foreach ($result['values'] as $_) {
      $this->activity_types[$_['name']] = $_['value'];
    }

  }

  /**
   * Fetches table name for prospects, contracts.
   *
   * @param string $group_name pelf_prospect|pelf_contract
   * @return string
   *
   */
  public function getTableName($group_name) {
    return $this->custom_groups[$group_name]['table_name'];
  }
  /**
   * Fetches custom group id from name.
   *
   * @throws Exception if something does not look right.
   * @param string $group_name pelf_prospect|pelf_contract
   * @return string
   */
  public function getCustomGroupId($group_name) {
    if (!isset($this->custom_groups[$group_name])) {
      throw new \Exception("Did not find custom group $group_name");
    }
    $_ = (int) $this->custom_groups[$group_name]['id'];
    if (!$_) {
      throw new \Exception("Invalid group id found for $group_name");
    }
    return $_;
  }
  /**
   * Fetches an API-ready code name for one of our custom fields.
   *
   * Custom fields we use:
   * - pelf_stage
   * - pelf_est_amount
   * - pelf_prospect_scale
   * - pelf_total_worth
   *
   */
  public function getApiFieldName($field_name) {
    return 'custom_' . $this->custom_fields[$field_name]['id'];
  }

  /**
   * Fetches the column name for the custom field.
   *
   * @param string $field_name e.g. pelf_stage
   * @return string e.g. stage_7
   */
  public function getFieldColumnName($field_name) {
    return $this->custom_fields[$field_name]['column_name'];
  }

  public function getProspectActivityType() {
    return $this->activity_types['pelf_prospect_activity_type'];
  }
  public function getContractActivityType() {
    return $this->activity_types['pelf_contract_activity_type'];
  }

  /**
   * Get statistical summary.
   *
   * Want to know:
   *
   * - Prospects
   *    - How many live ones
   *    - Total worth, scaled worth
   *    - Total worth, scaled worth by year
   * - Contracts
   *    - How many live ones
   *    - Total worth
   *    - Total worth by year
   *
   * @return array
   */
  public function getSummary() {

    // Prospects.
    $prospect_table = $this->getTableName('pelf_prospect');
    $data =  [];
    $stage = $this->getFieldColumnName('pelf_stage');
    $params = [];
    // We know this is SQL safe because it's defined above in code with this in mind :-)
    $prospect_statuses = "'" . implode("','", static::$prospect_statuses_live) . "'";
    $scale = $this->getFieldColumnName('pelf_prospect_scale');

    $sql = "SELECT financial_year fy, $stage stage, SUM(amount) gross, SUM(amount * $scale / 100) scaled
      FROM $prospect_table p
      INNER JOIN civicrm_pelffunding f ON p.entity_id = f.activity_id
      WHERE $stage IN ($prospect_statuses)
      GROUP BY financial_year, $stage
      ORDER BY financial_year DESC, $stage
      ";
    foreach (CRM_Core_DAO::executeQuery($sql, [])->fetchAll() as $row) {
      $data['prospects_by_fy'][$row['fy']][$row['stage']] = [
        'scaled' => (double) $row['scaled'],
        'gross'  => (double) $row['gross'],
      ];
    }


    // Contracts.
    $contract_table = $this->getTableName('pelf_contract');
    $contract_activity_type = $this->getContractActivityType();
    $params = [];
    $sql = "SELECT financial_year fy, SUM(amount) amount
      FROM civicrm_activity c
      INNER JOIN civicrm_pelffunding f ON c.id = f.activity_id
      WHERE activity_type_id = $contract_activity_type
      GROUP BY financial_year
      ORDER BY financial_year DESC
      ";
error_log(strtr($sql,["\n"=>""]));
    foreach (CRM_Core_DAO::executeQuery($sql, [])->fetchAll() as $row) {
      $data['contracts_by_fy'][$row['fy']] = $row['amount'];
    }

    return $data;
  }
}
