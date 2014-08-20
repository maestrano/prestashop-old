<?php

/**
 * Configure App specific behavior for 
 * Maestrano SSO
 */
class MnoSsoUser extends MnoSsoBaseUser
{
  /**
   * Database connection
   * @var PDO
   */
  public $connection = null;
  
  
  /**
   * Extend constructor to inialize app specific objects
   *
   * @param OneLogin_Saml_Response $saml_response
   *   A SamlResponse object from Maestrano containing details
   *   about the user being authenticated
   */
  public function __construct(OneLogin_Saml_Response $saml_response, &$session = array(), $opts = array())
  {
    // Call Parent
    parent::__construct($saml_response,$session);
    
    // Assign new attributes
    $this->connection = $opts['db_connection'];
  }
  
  
  /**
   * Sign the user in the application. 
   * Parent method deals with putting the mno_uid, 
   * mno_session and mno_session_recheck in session.
   *
   * @return boolean whether the user was successfully set in session or not
   */
  protected function setInSession()
  {
    // First get the employee
    $employee = new Employee($this->local_id);
    
    if ($employee && $employee->id) {
  			// Update cookie
  			$cookie = Context::getContext()->cookie;
  			$cookie->id_employee = $employee->id;
  			$cookie->email = $employee->email;
  			$cookie->profile = $employee->id_profile;
  			$cookie->passwd = $employee->passwd;
  			$cookie->remote_addr = ip2long(Tools::getRemoteAddr());
  			$cookie->write();
        
        return true;
    } else {
        return false;
    }
  }
  
  
  /**
   * Used by createLocalUserOrDenyAccess to create a local user 
   * based on the sso user.
   * If the method returns null then access is denied
   *
   * @return the ID of the user created, null otherwise
   */
  protected function createLocalUser()
  {
    $lid = null;
    
    if ($this->accessScope() == 'private') {
      // First build the user
      $user = $this->buildLocalUser();
      
      // Save the user and get the user id
      $user->save();
      $lid = $user->id;
      
      // Link the user to its shop
      $data = Array(
        'id_shop'     => $this->getShopId(),
        'id_employee' => $lid
      );
      $this->connection->insert('employee_shop',$data);
    }
    
    return $lid;
  }
  
  
  
  /**
   * Build a local user for creation 
   *
   * @return returns a prestashop employee object
   */
  protected function buildLocalUser()
  {
    $employee = new Employee();
    $employee->lastname = $this->surname;
    $employee->firstname = $this->name;
    $employee->email = $this->email;
    $employee->passwd = $this->generatePassword();
    $employee->id_profile = $this->getRoleId();
    $employee->id_lang = 1; //english
    $employee->bo_theme = 'default';
    
    // Encrypt password
    $employee->setWsPasswd();
    
    return $employee;
  }
  
  /**
   * Return the shop id to link to the user 
   *
   * @return integer the shop id
   */
  protected function getShopId()
  {
    return 1;
  }
  
  /**
   * Return which role should be given to the user
   *
   * @return returns a prestashop employee object
   */
  protected function getRoleId()
  {
    $role_id = 5; // Salesman
    
    if ($this->app_owner) {
      $role_id = 1; // SuperAdmin
    } else {
      foreach ($this->organizations as $organization) {
        if ($organization['role'] == 'Admin' || $organization['role'] == 'Super Admin') {
          $role_id = 1;
        } else {
          $role_id = 5;
        }
      }
    }
    
    return $role_id;
  }
  
  /**
   * Get the ID of a local user via Maestrano UID lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByUid()
  {
    $arg = $this->connection->escape($this->uid);
    $q = new DbQuery;
    $q->select('id_employee')->from('employee')->where("mno_uid = '$arg'");
    $result = $this->connection->query($q);
    $result = $this->connection->nextRow($result);
    
    if ($result && $result['id_employee']) {
      return $result['id_employee'];
    }
    
    return null;
  }
  
  /**
   * Get the ID of a local user via email lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByEmail()
  {
    $arg = $this->connection->escape($this->email);
    $q = new DbQuery;
    $q->select('id_employee')->from('employee')->where("email = '$arg'");
    $result = $this->connection->query($q);
    $result = $this->connection->nextRow($result);
    
    if ($result && $result['id_employee']) {
      return $result['id_employee'];
    }
    
    return null;
  }
  
  /**
   * Set all 'soft' details on the user (like name, surname, email)
   * Implementing this method is optional.
   *
   * @return boolean whether the user was synced or not
   */
   protected function syncLocalDetails()
   {
     if($this->local_id) {
       $arg = $this->connection->escape($this->local_id);
       $data = Array(
         'firstname' => $this->name,
         'lastname'  => $this->surname,
         'email'     => $this->email
       );
       $upd = $this->connection->update('employee',$data, "id_employee = $arg");
       
       return $upd;
     }
     
     return false;
   }
  
  /**
   * Set the Maestrano UID on a local user via id lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function setLocalUid()
  {
    if($this->local_id) {
      $arg = $this->connection->escape($this->local_id);
      $data = Array(
        'mno_uid' => $this->uid,
      );
      $upd = $this->connection->update('employee',$data, "id_employee = $arg");
      
      return $upd;
    }
    
    return false;
  }
}