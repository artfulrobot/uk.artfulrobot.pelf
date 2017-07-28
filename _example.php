<?php

ini_set('safe_mode', 0);
eval(cv('php:boot --level=full', 'phpcode'));

/**
 * Call the "cv" command.
 *
 * @param string $cmd
 *   The rest of the command to send.
 * @param string $decode
 *   Ex: 'json' or 'phpcode'.
 * @return string
 *   Response output (if the command executed normally).
 * @throws \RuntimeException
 *   If the command terminates abnormally.
 */
function cv($cmd, $decode = 'json') {
  $cmd = 'cv ' . $cmd;
  $descriptorSpec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => STDERR);
  $oldOutput = getenv('CV_OUTPUT');
  putenv("CV_OUTPUT=json");
  $process = proc_open($cmd, $descriptorSpec, $pipes, __DIR__);
  putenv("CV_OUTPUT=$oldOutput");
  fclose($pipes[0]);
  $result = stream_get_contents($pipes[1]);
  fclose($pipes[1]);
  if (proc_close($process) !== 0) {
    throw new RuntimeException("Command failed ($cmd):\n$result");
  }
  switch ($decode) {
    case 'raw':
      return $result;

    case 'phpcode':
      // If the last output is /*PHPCODE*/, then we managed to complete execution.
      if (substr(trim($result), 0, 12) !== "/*BEGINPHP*/" || substr(trim($result), -10) !== "/*ENDPHP*/") {
        throw new \RuntimeException("Command failed ($cmd):\n$result");
      }
      return $result;

    case 'json':
      return json_decode($result, 1);

    default:
      throw new RuntimeException("Bad decoder format ($decode)");
  }
}

/**
 * Creates a demo 'Funder A' with a prospect.
 */
function pelf_install_demo() {

  // Create demo funder.
  $funder = pelf_get_or_create('Contact',
    [ 'organization_name' => 'Funder A', 'contact_type' => 'Organization']
  );

  $pelf = CRM_Pelf::service();
  // Create prospect.
  $prospect = pelf_get_or_create('Activity',
    [
      'source_contact_id'  => 1,
      "activity_type_id"   => "pelf_prospect_activity_type",
      'activity_date_time' => '2017-05-01',
    ],
    [
      'subject'            => 'Demo prospect',
      'target_id'          => $funder['id'],
      $pelf->getApiFieldName('pelf_prospect_scale') => '20',
      $pelf->getApiFieldName('pelf_est_amount') => '10000',
    ]
  );
  print "Prospect: $prospect[id]\n";

  // Create funding records for prospect.
  pelf_get_or_create('PelfFunding', [
      'activity_id'    => $prospect['id'],
      'amount'         => 5000,
      'financial_year' => "2017-2018",
      'note'           => "tranche 1",
  ]);
  pelf_get_or_create('PelfFunding', [
      'activity_id'    => $prospect['id'],
      'amount'         => 5000,
      'financial_year' => "2018-2019",
      'note'           => "tranche 2",
  ]);


}
pelf_install_demo();
