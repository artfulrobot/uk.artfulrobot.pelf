<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Pelf_Form_Report_PelfSummary',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Funding Summmary',
      'description' => 'Provides a schedule of actions for live funding.',
      'class_name' => 'CRM_Pelf_Form_Report_PelfSummary',
      'report_url' => 'pelf',
      'component' => '',
    ),
  ),
);
