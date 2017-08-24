<?php
/**
 * @file
 *
 */
class CRM_Pelf_Prospect extends CRM_Pelf_Activity {

  public function getDefaultData() {
    // Set up default data.
    return [
      'id' => null,
      'activity_type_id' => CRM_Pelf::service()->getProspectActivityType(),
      'contactWith' => [],
      'date' => date('Y-m-d H:i:s'),
      'details' => '',
      'funding' => [],
      'related_activities' => [],
      'stage' => '00_speculative',
      'subject' => '',
    ];

  }

  /**
   * Shared by factory methods.
   */
  public static function getBaseApiParams() {
    $pelf = CRM_Pelf::service();
    $stage      = $pelf->getApiFieldName('pelf_stage');
    $scale      = $pelf->getApiFieldName('pelf_prospect_scale');

    // Fetch row(s).
    $api_params = [
      'return' => "id,activity_type_id,subject,activity_date_time,details,$stage,$scale",
      'activity_type_id' => $pelf->getProspectActivityType(),
    ];
    return $api_params;
  }

  /**
   * Import the result of an API get activity request into this object.
   */
  public function importBaseData($activity) {
    $pelf = CRM_Pelf::service();
    $stage      = $pelf->getApiFieldName('pelf_stage');
    $scale      = $pelf->getApiFieldName('pelf_prospect_scale');
    $this->data = $this->getDefaultData();
    // Map the API result to something more manageable.
    foreach ([
      'id', 'subject', 'details',
      $stage               => 'stage',
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
}
