<?php

require_once 'pelf.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function pelf_civicrm_config(&$config) {
  _pelf_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function pelf_civicrm_xmlMenu(&$files) {
  _pelf_civix_civicrm_xmlMenu($files);
}


/**
 * Helper function for creating data structures.
 *
 * @param string $entity - name of the API entity.
 * @param Array $params_min parameters to use for search.
 * @param Array $params_extra these plus $params_min are used if a create call
 *              is needed.
 */
function pelf_get_or_create($entity, $params_min, $params_extra=[]) {
  $params_min += ['sequential' => 1];
  $result = civicrm_api3($entity, 'get', $params_min);
  if (!$result['count']) {
    // Couldn't find it, create it now.
    $result = civicrm_api3($entity, 'create', $params_extra + $params_min);
  }
  return $result['values'][0];
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function pelf_civicrm_install() {
  _pelf_civix_civicrm_install();

  // Ensure we have the activity type set up for Prospects.
  $prospect = pelf_get_or_create('OptionValue', [
    'option_group_id' => "activity_type",
    'name' => "pelf_prospect_activity_type",
  ],
  [ 'label' => 'Prospect']);

  // Ensure we have the custom field group we need for prospects.
  $prospect_customgroup = pelf_get_or_create('CustomGroup', [
    'name'                        => "pelf_prospect",
    'extends'                     => "Activity",
    'extends_entity_column_value' => $prospect['value'],
  ],
  ['title' => 'Prospect Details']);

  // Add our 'Stage' field.
  // ...This is a drop-down select field, first we need to check the option
  //    group exists, and its values.
  $stage_opts_group = pelf_get_or_create('OptionGroup',
    ['name' => 'pelf_stage_opts'],
    ['title' => 'Stage', 'is_active' => 1]);
  $weight = 0;
  foreach (CRM_Pelf::$prospect_stages as $name => $label) {
    pelf_get_or_create('OptionValue',
      [ 'option_group_id' => "pelf_stage_opts", 'name' => $name, ],
      [ 'label' => $label, 'value' => $name, 'weight' => $weight++, ]);
  }
  // ... Now we can check the Stage Select field.
  $prospect_field_stage = pelf_get_or_create('CustomField', [
    'name'            => "pelf_stage",
    'custom_group_id' => $prospect_customgroup['id'],
    'data_type'       => "String",
    ],[
    'html_type'       => "Select",
    'is_required'     => "1",
    'is_searchable'   => "1",
    'default_value'   => "00_speculative",
    'text_length'     => "30",
    'option_group_id' => $stage_opts_group['id'],
    'label'           => 'Stage',
  ]);

  // Add the prospect_scale field.
  $prospect_field_worth = pelf_get_or_create('CustomField', [
      'name'            => "pelf_prospect_scale",
      'custom_group_id' => $prospect_customgroup['id'],
      'data_type'       => "Float",
      'html_type'       => "Text",
      'is_required'     => "1",
      'default_value'   => "20",
    ],
    [
      'label'    => 'Liklihood %',
      'help_pre' => "If you know you're going to get "
        . "this it's 100. If you know that you normally win about 15% of bids like "
        . "this, it's 15. The prospect amounts are multiplied by this percentage to "
        . "give the estimated worth of the prospect."
    ]);

  // Check the Contract Activity
  $contract = pelf_get_or_create('OptionValue',
    ['option_group_id' => "activity_type", 'name' => "pelf_contract_activity_type"],
    ['label' => "Contract"]);

  // Ensure we have the custom field group we need for contracts.
  $contract_customgroup = pelf_get_or_create('CustomGroup', [
    'name'                        => "pelf_contract",
    'extends'                     => "Activity",
    'extends_entity_column_value' => $contract['value'],
  ],
  ['title' => 'Contract Details']);

  // Add the Total Worth field.
  $contract_field_worth = pelf_get_or_create('CustomField', [
      'name'            => "pelf_total_worth",
      'custom_group_id' => $contract_customgroup['id'],
      'data_type'       => "Float",
      'html_type'       => "Text",
      'is_required'     => "1",
      'default_value'   => "0",
    ],
    ['label' => 'Total Worth', 'help_pre' => "Enter the amount in your currency."]);
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function pelf_civicrm_uninstall() {

  $fields = civicrm_api3('CustomGroup', 'get', ['name' => ['IN' => ['pelf_prospect', 'pelf_contract']]]);
  foreach ($fields['values'] as $_) {
    civicrm_api3('CustomGroup', 'delete', ['id' => (int) $_['id']]);
  }
  _pelf_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function pelf_civicrm_enable() {
  _pelf_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function pelf_civicrm_disable() {
  _pelf_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function pelf_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _pelf_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function pelf_civicrm_managed(&$entities) {
  _pelf_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function pelf_civicrm_caseTypes(&$caseTypes) {
  _pelf_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function pelf_civicrm_angularModules(&$angularModules) {
_pelf_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function pelf_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _pelf_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Define our entities.
 *
 * This seems required despite the file at
 * xml/schema/CRM/Pelf/PelfFunding.entityType.php
 */
function pelf_civicrm_entityTypes(&$entityTypes) {
  $entityTypes[] = [
    'name' => 'PelfFunding',
    'class' => 'CRM_Pelf_DAO_PelfFunding',
    'table' => 'civicrm_pelffunding',
  ];
}
/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function pelf_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function pelf_civicrm_navigationMenu(&$menu) {
  _pelf_civix_insert_navigation_menu($menu, NULL, array(
    'label' => ts('The Page', array('domain' => 'uk.artfulrobot.pelf')),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _pelf_civix_navigationMenu($menu);
} // */
