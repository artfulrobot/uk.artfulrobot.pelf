<?php
/**
 * @file
 *
 */
class CRM_Pelf_Prospect extends CRM_Pelf_Activity {

  public function __construct() {
    $this->data = $this->getDefaultData();
    $this->activity_type_id = $this->data['activity_type_id'];
  }

  public function getDefaultData() {
    // Set up default data.
    return [
      'id' => null,
      'activity_type_id' => CRM_Pelf::service()->getProspectActivityType(),
      'contactWith' => [],
      'date' => date('Y-m-d H:i:s'),
      'details' => '',
      'est_amount' => '',
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
    $est_amount = $pelf->getApiFieldName('pelf_est_amount');
    $scale      = $pelf->getApiFieldName('pelf_prospect_scale');

    // Fetch row(s).
    $api_params = [
      'return' => "id,activity_type_id,subject,activity_date_time,details,$stage,$est_amount,$scale",
      'activity_type_id' => $pelf->getProspectActivityType(),
    ];
    return $api_params;
  }

}
