<?php
use \ArtfulRobot as ARL;
use \ArtfulRobot\CiviCRM as ARLCRM;

class CRM_Pelf_Form_Report_PelfSummary extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array();
  protected $_customGroupGroupBy = FALSE;
  function __construct() {
    // This is not needed; there are no options.
    if (FALSE) {
      $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'first_name' => array(
            'title' => ts('First Name'),
            'no_repeat' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'last_name' => array(
            'title' => ts('Last Name'),
            'no_repeat' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'operator' => 'like',
          ),
          'id' => array(
            'no_display' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_membership' => array(
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => array(
          'membership_type_id' => array(
            'title' => 'Membership Type',
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'join_date' => array('title' => ts('Join Date'),
            'default' => TRUE,
          ),
          'source' => array('title' => 'Source'),
        ),
        'filters' => array(
          'join_date' => array(
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'owner_membership_id' => array(
            'title' => ts('Membership Owner ID'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ),
          'tid' => array(
            'name' => 'membership_type_id',
            'title' => ts('Membership Types'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ),
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_membership_status' => array(
        'dao' => 'CRM_Member_DAO_MembershipStatus',
        'alias' => 'mem_status',
        'fields' => array(
          'name' => array(
            'title' => ts('Status'),
            'default' => TRUE,
          ),
        ),
        'filters' => array(
          'sid' => array(
            'name' => 'id',
            'title' => ts('Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
          ),
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => array(
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => array('title' => ts('State/Province')),
          'country_id' => array('title' => ts('Country')),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array('email' => NULL),
        'grouping' => 'contact-fields',
      ),
    );
    }
    //$this->_groupFilter = TRUE;
    //$this->_tagFilter = TRUE;
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Membership Detail Report'));
    parent::preProcess();
  }

  function select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
               INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_membership']}.contact_id AND {$this->_aliases['civicrm_membership']}.is_test = 0
               LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                          ON {$this->_aliases['civicrm_membership_status']}.id =
                             {$this->_aliases['civicrm_membership']}.status_id ";


    //used when address field is selected
    if ($this->_addressField) {
      $this->_from .= "
             LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                       ON {$this->_aliases['civicrm_contact']}.id =
                          {$this->_aliases['civicrm_address']}.contact_id AND
                          {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
    //used when email field is selected
    if ($this->_emailField) {
      $this->_from .= "
              LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                        ON {$this->_aliases['civicrm_contact']}.id =
                           {$this->_aliases['civicrm_email']}.contact_id AND
                           {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_membership']}.membership_type_id";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_membership']}.membership_type_id";
  }

  function postProcess() {

    $this->beginPostProcess();

    $prop_deets_table = civicrm_api3('CustomGroup', 'getvalue', ['return' => "table_name", 'name' => "pelf_prospect"]);
    $prop_stage = civicrm_api3('CustomField', 'getvalue', ['return' => "column_name", 'name' => "pelf_stage"]);
    $prop_worth  = civicrm_api3('CustomField', 'getvalue', ['return' => "column_name", 'name' => "pelf_est_worth"]);

    $contract_deets_table = civicrm_api3('CustomGroup', 'getvalue', ['return' => "table_name", 'name' => "pelf_contract"]);
    $contract_worth = civicrm_api3('CustomField', 'getvalue', ['return' => "column_name", 'name' => "pelf_total_worth"]);

    // get activity type ids...
    $activity_proposal = civicrm_api3('OptionValue', 'getvalue', ['return' => "id", 'name' => "pelf_prospect_activity_type", 'option_group_id' => 'activity_type']);
    $activity_contract = civicrm_api3('OptionValue', 'getvalue', ['return' => "id", 'name' => "pelf_contract_activity_type", 'option_group_id' => 'activity_type']);
    // $activity_git      = ARLCRM\OptionGroup::getValueValue('activity_type','Get in touch');

    $status_scheduled  = civicrm_api3('OptionValue', 'getvalue', ['return' => "id", 'name' => "Scheduled", 'option_group_id' => "activity_status"]);
    // xxx
    $status_live       = civicrm_api3('OptionValue', 'getvalue', ['return' => "id", 'name' => "Live", 'option_group_id' => "activity_status"]);

    $sql = 
        "SELECT 
        IF(funder.id, CONCAT_WS('~',funder.id,funder.display_name), 'Missing Funder!') funder, 
        CONCAT_WS('~',a.id, IF(a.subject!='', a.subject, 'Missing Project Name') ) title,
        if( a.activity_type_id = $activity_contract,
            FORMAT($contract_worth,0),
            FORMAT($prop_worth,0)) worth,
        IF( a.activity_type_id = $activity_contract,
                'Contract',
                prop_deets.$prop_stage ) stage,
        CONCAT_WS('~',assignee.id,assignee.display_name) assigned,
        ( SELECT git.id 
          FROM civicrm_activity git
          WHERE /*git.activity_type_id = activity_git */
          AND git.status_id = $status_scheduled
          AND git.parent_id = a.id
          ORDER BY git.activity_date_time
          LIMIT 1 ) `next`

        FROM civicrm_activity a
            LEFT JOIN civicrm_activity_contact cat ON (cat.activity_id = a.id AND cat.record_type_id = 3)
            LEFT JOIN civicrm_contact funder ON cat.contact_id = funder.id
            LEFT JOIN civicrm_activity_contact caa ON (caa.activity_id = a.id AND caa.record_type_id = 1)
            LEFT JOIN civicrm_contact assignee ON caa.contact_id = assignee.id
            LEFT JOIN $prop_deets_table prop_deets ON prop_deets.entity_id = a.id
            LEFT JOIN $contract_deets_table contract_deets ON contract_deets.entity_id = a.id

            WHERE
            ( 
             (  a.activity_type_id = $activity_proposal 
                AND prop_deets.$prop_stage IN ('speculative','writing','waiting','negotiating') )
             OR
             (  a.activity_type_id = $activity_contract
                AND a.status_id IN($status_scheduled, $status_live) ) 
            )
            ORDER BY funder.display_name";

    /*
       Add in assigned to (Staff)

       Add in next action:
       SELECT date, assigned to
       FROM activity
       WHERE activity_type = GIT
       AND status='Scheduled'
       AND parent_id = activity.id // only follow-ups 
       ORDER BY date
       LIMIT 1

     */

    // note2: register columns you want displayed-
    $this->_columnHeaders =
        array( 'funder' => array( 'title' => 'Funder' ),
                'worth'  => array( 'title' => 'Worth £' ),
                'title'  => array( 'title' => 'Project' ),
                'stage' => array( 'title' => 'Stage' ),
                'assigned'  => array('title' => 'Assigned to' ),
                'next'  => array('title' => 'Next action' ),
             );
    // note3: let report do the fetching of records for you
    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();
    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (CRM_Utils_Array::value($colName, $checkList) &&
            is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }/*}}}*/


    /** add in links etc. to results
     */
    public function formatDisplay(&$rows, $pager = TRUE)
    {
        foreach ($rows as &$row) {
            // assignee
            $row['assigned'] = preg_replace(
                    '/^(\d+)~(.+)$/',
                    '<a href="/civicrm/contact/view/?reset=1&amp;cid=$1" >$2</a>',
                    $row['assigned']);

            // funder
            $funder_id = preg_replace( '/^(\d+)~.*$/','$1', $row['funder']);
            $row['funder'] = preg_replace(
                    '/^(\d+)~(.+)$/',
                    '<a href="/civicrm/contact/view/?reset=1&amp;cid=$1" >$2</a>',
                    $row['funder']);

            // activity
            $row['title'] = preg_replace(
                    '/^(\d+)~(.+)$/',
                    '<a href="/civicrm/activity/?action=view&amp;reset=1&amp;id=$1&amp;context=activity&amp;cid=' .$funder_id. '" >$2</a>',
                    $row['title']);

            // next activity
            if (!($next_thing = (int) $row['next'])) {
                continue;
            }

            // look up this activity. Going to need Assignee(s), date and subject
            $sql = "SELECT a.id activity_id, subject, 
                        a.activity_date_time datetime,
                        assignee.id assignee_id, assignee.display_name assignee_name
                FROM civicrm_activity a
                     LEFT JOIN civicrm_activity_contact caa ON (caa.activity_id = a.id AND caa.record_type_id = 1)
                     LEFT JOIN civicrm_contact assignee ON caa.contact_id = assignee.id
                         WHERE a.id = $next_thing
                         ";

            $dao = CRM_Core_DAO::executeQuery($sql);
            if (! $dao->fetch() ) {
                unset($dao);
                continue;
            }

            $due = strtotime($dao->datetime);
            if (!$due) {
                $due = '';
            } else {
                $interval = $due - time();
                if ($interval < 0) {
                    $interval = format_interval(-$interval) . " ago";
                } else {
                    $interval = "in " . format_interval($interval) ;
                }
                $due = date('j M Y', $due) . " ($interval)";
            }
            $row['next'] = 
                "<a href='/civicrm/activity/?action=view&amp;reset=1&amp;id=$dao->activity_id&amp;context=activity&amp;cid=$funder_id' >"
                . htmlspecialchars("$due $dao->subject")
                . ($dao->assignee_name
                        ? htmlspecialchars(" ($dao->assignee_name)")
                        : '' )
                . '</a>';
        }
    }
}
