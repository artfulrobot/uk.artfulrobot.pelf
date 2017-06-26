<?php

class CRM_Pelf_BAO_PelfFunding extends CRM_Pelf_DAO_PelfFunding {

  /**
   * Create a new PelfFunding based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Pelf_DAO_PelfFunding|NULL
   *
  public static function create($params) {
    $className = 'CRM_Pelf_DAO_PelfFunding';
    $entityName = 'PelfFunding';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */
}
