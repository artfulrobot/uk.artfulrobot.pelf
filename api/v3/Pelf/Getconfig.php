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

  // Generate a list of financial years for SELECT elements etc. starting 10
  // years back, going 10 years forward.  Strictly speaking this being loaded
  // once per session could be a bother to someone putting in ancient or very
  // future data, but with 10 years scope it's probably more efficient this
  // way.
  $earliest_year = $latest_year = null;
  $sql = "SELECT MIN(financial_year) min_fy, MAX(financial_year) max_fy FROM civicrm_pelffunding f";
  $range = CRM_Core_DAO::executeQuery($sql, [])->fetchAll();
  if ($range) {
    $earliest_year = substr($range[0]['min_fy'], 0, 4);
    $latest_year   = substr($range[0]['max_fy'], 0, 4);
  }
  $y = date('Y') - 10;
  if ($earliest_year !== null && $y > $earliest_year) {
    // Oh, our data goes back further than 10 years ago. Go back another 10 to be safe.
    $y -= 10;
  }
  $i = 20;
  if ($latest_year !== null && $y + $i < $latest_year) {
    // Oh, our data goes further than 10 years hence. Go forward another 10 to be safe.
    $i = $latest_year - $y + 10;
  }
  $result = civicrm_api3('Setting', 'get', ['sequential' => 1, 'return' => ["fiscalYearStart"]]);
  $fy_spans_calendar_year = !($result['values'][0]['fiscalYearStart']['M'] == 1 && $result['values'][0]['fiscalYearStart']['d'] == 1);
  $data['financial_years_all'] = [];
  $data['financial_years'] = [];
  while ($i-- > 0) {
    $fy = (string) (($fy_spans_calendar_year) ? "$y-" . ($y+1) : $y);
    $data['financial_years_all'][] = $fy;
    if ($earliest_year !== null && $y >= $earliest_year
        && $latest_year !== null && $y <= $latest_year) {
      $data['financial_years'][] = $fy;
    }
    $y++;
  }

  return $data;
}

