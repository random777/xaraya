<?php
/**
 * xarPrivilege: class for the privileges object
 *
 * Represents a single privileges object
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
*/
sys::import('modules.privileges.class.mask');

class xarPrivilege extends xarMask
{

    public $pid;           //the id of this privilege
    public $name;          //the name of this privilege
    public $realm;         //the realm of this privilege
    public $module;        //the module of this privilege
    public $component;     //the component of this privilege
    public $instance;      //the instance of this privilege
    public $level;         //the access level of this privilege
    public $description;   //the long description of this privilege
    public $parentid;      //the pid of the parent of this privilege

    public $dbconn;
    public $privilegestable;
    public $privmemberstable;
    public $realmstable;

    /**
     * xarPrivilege: constructor for the class
     *
     * Just sets up the db connection and initializes some variables
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   array of values
     * @return  the privilege object
    */
    function __construct($pargs)
    {
        extract($pargs);

        $this->dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();
        $this->privilegestable = $xartable['privileges'];
        $this->privmemberstable = $xartable['privmembers'];
        $this->rolestable = $xartable['roles'];
        $this->acltable = $xartable['security_acl'];
        $this->realmstable = $xartable['security_realms'];

// CHECKME: pid and description are undefined when adding a new privilege
        if (empty($pid)) {
            $pid = 0;
        }
        if (empty($description)) {
            $description = '';
        }

        $this->pid          = (int) $pid;
        $this->name         = $name;
        $this->realm        = $realm;
        $this->module       = $module;
        $this->component    = $component;
        $this->instance     = $instance;
        $this->level        = (int) $level;
        $this->description  = $description;
        $this->parentid     = (int) $parentid;
    }

    /**
     * add: add a new privileges object to the repository
     *
     * Creates an entry in the repository for a privileges object that has been created
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
    */
   function add()
   {

        if(empty($this->name)) {
            $msg = xarML('You must enter a name.','privileges');
            throw new DuplicateException(null,$msg);
            xarSessionSetVar('errormsg', _MODARGSERROR);
            return false;
        }

        // create the insert query
        $realmid = null;
        if($this->realm != 'All') {
            $stmt = $this->dbconn->prepareStatement('SELECT xar_rid FROM '. $this->realmstable .' WHERE xar_name=?');
            $result = $stmt->executeQuery(array($this->realm),ResultSet::FETCHMODE_ASSOC);
            if($result->next()) $realmid = $result->getInt('xar_rid');
        }
        $query = "INSERT INTO $this->privilegestable
                    (xar_name, xar_realmid, xar_module, xar_component, xar_instance, xar_level)
                  VALUES (?,?,?,?,?,?)";
        $bindvars = array($this->name, $realmid, $this->module,
                          $this->component, $this->instance, $this->level);
        //Execute the query, bail if an exception was thrown
        $this->dbconn->Execute($query,$bindvars);

        // the insert created a new index value
        // retrieve the value
        $this->pid = $this->dbconn->getLastId($this->privilegestable);

        // make this privilege a child of its parent
        if($this->parentid !=0) {
            sys::import('modules.privileges.class.privileges');
            $perms = new xarPrivileges();
            $parentperm = $perms->getprivilege($this->parentid);
            $parentperm->addMember($this);
        }
        // create this privilege as an entry in the repository
        return $this->makeEntry();
    }

    /**
     * makeEntry: sets up a privilege without parents
     *
     * Sets up a privilege as a root entry (no parent)
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
     * @todo    check to make sure the child is not a parent of the parent
    */
    function makeEntry()
    {
        if ($this->isRootPrivilege()) return true;
        $query = "INSERT INTO $this->privmemberstable VALUES (?,?)";
        $this->dbconn->Execute($query,array($this->getID(),0));
        return true;
    }

    /**
     * addMember: adds a privilege to a privilege
     *
     * Make a privilege a member of another privilege.
     * A privilege can have any number of parents or children..
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   privilege object
     * @return  boolean
     * @todo    check to make sure the child is not a parent of the parent
    */
    function addMember($member)
    {
        $query = "INSERT INTO $this->privmemberstable VALUES (?,?)";
        $bindvars = array($member->getID(), $this->getID());
        //Execute the query, bail if an exception was thrown
        $this->dbconn->Execute($query,$bindvars);

// empty the privset cache
//        $privileges = new xarPrivileges();
//        $privileges->forgetprivsets();

        return true;
    }

    /**
     * removeMember: removes a privilege from a privilege
     *
     * Removes a privilege as an entry of another privilege.
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
    */
    function removeMember($member)
    {
    sys::import('modules.roles.class.xarQuery');
        $q = new xarQuery('SELECT', $this->privmemberstable, 'COUNT(*) AS count');
        $q->eq('xar_pid', $member->getID());
        if (!$q->run()) return;
        $total = $q->row();
        if($total['count'] == 0) return true;

        if($total['count'] > 1) {
            $q = new xarQuery('DELETE');
            $q->eq('xar_parentid', $this->getID());
        } else {
            $q = new xarQuery('UPDATE');
            $q->addfield('xar_parentid', 0);
        }
        $q->addtable($this->privmemberstable);
        $q->eq('xar_pid', $member->getID());
        if (!$q->run()) return;

// empty the privset cache
//        $privileges = new xarPrivileges();
//        $privileges->forgetprivsets();

        return true;
    }

    /**
     * update: updates a privilege in the repository
     *
     * Updates a privilege in the privileges repository
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
    */
    function update()
    {
        $realmid = null;
        if($this->realm != 'All') {
            $stmt = $this->dbconn->prepareStatement('SELECT xar_rid FROM '. $this->realmstable .' WHERE xar_name=?');
            $result = $stmt->executeQuery(array($this->realm),ResultSet::FETCHMODE_ASSOC);
            if($result->next()) $realmid = $result->getInt('xar_rid');
        }

        $query =    "UPDATE " . $this->privilegestable .
                    ' SET xar_name = ?,     xar_realmid = ?,
                          xar_module = ?,   xar_component = ?,
                          xar_instance = ?, xar_level = ?
                      WHERE xar_pid = ?';
        
        $bindvars = array($this->name, $realmid, $this->module,
                          $this->component, $this->instance, $this->level,
                          $this->getID());
        //Execute the query, bail if an exception was thrown
        $this->dbconn->Execute($query,$bindvars);
        return true;
    }

    /**
     * remove: deletes a privilege in the repository
     *
     * Deletes a privilege's entry in the privileges repository
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
     * @todo    reverse the order of deletion, i.e. first delete the related parts then the master (foreign key compat)
     * @todo    even better, do it in a transaction.
    */
    function remove()
    {

        // set up the DELETE query
        $query = "DELETE FROM $this->privilegestable WHERE xar_pid=?";
        //Execute the query, bail if an exception was thrown
        $this->dbconn->Execute($query,array($this->pid));

        // set up a query to get all the parents of this child
        $query = "SELECT xar_parentid FROM $this->privmemberstable
              WHERE xar_pid=?";
        //Execute the query, bail if an exception was thrown
        $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($this->getID()));

        // remove this child from all the parents
        $perms = new xarPrivileges();
        while($result->next()) {
            list($parentid) = $result->fields;
            if ($parentid != 0) {
                $parentperm = $perms->getPrivilege($parentid);
                $parentperm->removeMember($this);
            }
        }

        // remove this child from the root privilege too
        $query = "DELETE FROM $this->privmemberstable WHERE xar_pid=? AND xar_parentid=?";
        $stmt = $this->dbconn->prepareStatement($query);
        $stmt->executeUpdate(array($this->pid,0));

        // get all the roles this privilege was assigned to
        $roles = $this->getRoles();
        // remove the role assignments for this privilege
        foreach ($roles as $role) {
            $this->removeRole($role);
        }

        // get all the child privileges
        $children = $this->getChildren();
        // remove the child privileges from this parent
        foreach ($children as $childperm) {
            $this->removeMember($childperm);
        }

        // CHECKME: re-assign all child privileges to the roles that the parent was assigned to ?

        return true;
    }

    /**
     * isassigned: check if the current privilege is assigned to a role
     *
     * This function looks at the acl table and returns true if the current privilege.
     * is assigned to a given role .
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   role object
     * @return  boolean
    */
    function isassigned($role)
    {
        static $stmt = null;
        
        $query = "SELECT xar_partid FROM $this->acltable WHERE
                xar_partid = ? AND xar_permid = ?";
        $bindvars = array($role->getID(), $this->getID());
        if(!isset($stmt)) $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        return $result->first();
    }

    /**
     * getRoles: returns an array of roles
     *
     * Returns an array of roles this privilege is assigned to
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
     * @todo    seems to me this belong in roles module instead?
    */
    function getRoles()
    {
        // set up a query to select the roles this privilege
        // is linked to in the acl table
        $query = "SELECT r.xar_uid, r.xar_name, r.xar_type,
                         r.xar_uname, r.xar_email, r.xar_pass,
                         r.xar_auth_modid
                  FROM $this->rolestable r, $this->acltable acl
                  WHERE r.xar_uid = acl.xar_partid AND
                        acl.xar_permid = ?";
        $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($this->pid));

        // make objects from the db entries retrieved
        sys::import('modules.roles.class.roles');
        $roles = array();
        //      $ind = 0;
        while($result->next()) {
            list($uid,$name,$type,$uname,$email,$pass,$auth_modid) = $result->fields;
            //          $ind = $ind + 1;

            $role = new xarRole(array('uid' => $uid,
                                      'name' => $name,
                                      'type' => $type,
                                      'uname' => $uname,
                                      'email' => $email,
                                      'pass' => $pass,
                                      // NOTE: CHANGED since 1.x! to and ID,
                                      // but i dont think it matters, auth module should probably
                                      // be phased out of this table completely
                                      'auth_module' => $auth_modid,
                                      'parentid' => 0));
            $roles[] = $role;
        }
        // done
        return $roles;
    }

    /**
     * removeRole: removes a role
     *
     * Removes a role this privilege is assigned to
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   role object
     * @return  boolean
    */
    function removeRole($role)
    {
        // use the equivalent method from the roles object
        return $role->removePrivilege($this);
    }

    /**
     * getParents: returns the parent objects of a privilege
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  array of privilege objects
    */
    function getParents()
    {
        static $stmt = null;
        
        // create an array to hold the objects to be returned
        $parents = array();

        // perform a SELECT on the privmembers table
        $query = "SELECT p.*, pm.xar_parentid
                  FROM $this->privilegestable p, $this->privmemberstable pm
                  WHERE p.xar_pid = pm.xar_parentid
                    AND pm.xar_pid = ?";
        if(!isset($stmt)) $stmt = $this->dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array($this->getID()));

        // collect the table values and use them to create new role objects
        while($result->next()) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description,$parentid) = $result->fields;
            $pargs = array('pid'=>$pid,
                            'name'=>$name,
                            'realm'=>$realm,
                            'module'=>$module,
                            'component'=>$component,
                            'instance'=>$instance,
                            'level'=>$level,
                            'description'=>$description,
                            'parentid' => $parentid);
            array_push($parents, new xarPrivilege($pargs));
        }
        // done
        return $parents;
    }

    /**
     * getAncestors: returns all objects in the privileges hierarchy above a privilege
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  array of privilege objects
    */
    function getAncestors()
    {
        // if this is the root return an empty array
        if ($this->getID() == 1) return array();

        // start by getting an array of the parents
        $parents = $this->getParents();

        //Get the parent field for each parent
        $masks = new xarMasks();
        while (list($key, $parent) = each($parents)) {
            $ancestors = $parent->getParents();
            foreach ($ancestors as $ancestor) {
                array_push($parents,$ancestor);
            }
        }

        //done
        $ancestors = array();
        $parents = array_merge($ancestors,$parents);
        return $ancestors;
    }

    /**
     * getChildren: returns the child objects of a privilege
     *
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  array of privilege objects
    */
    function getChildren()
    {
        $cacheId = $this->getID();

        // we retrieve and cache everything at once now
        if (xarVarIsCached('Privileges.getChildren', 'cached')) {
            if (xarVarIsCached('Privileges.getChildren', $cacheId)) {
                return xarVarGetCached('Privileges.getChildren', $cacheId);
            } else {
                return array();
            }
        }

        // create an array to hold the objects to be returned
        $children = array();

        // if this is a user just perform a SELECT on the rolemembers table
        $query = "SELECT p.*, pm.xar_parentid
                    FROM $this->privilegestable p, $this->privmemberstable pm
                    WHERE p.xar_pid = pm.xar_pid";
        // retrieve all children of everyone at once
        //              AND pm.xar_parentid = " . $cacheId;
        // Can't use caching here. The privs have changed
        $result = $this->dbconn->executeQuery($query);

        // collect the table values and use them to create new role objects
        while($result->next()) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description,$parentid) = $result->fields;
            if (!isset($children[$parentid])) $children[$parentid] = array();
            $pargs = array('pid'=>$pid,
                            'name'=>$name,
                            'realm'=>$realm,
                            'module'=>$module,
                            'component'=>$component,
                            'instance'=>$instance,
                            'level'=>$level,
                            'description'=>$description,
                            'parentid' => $parentid);
            array_push($children[$parentid], new xarPrivilege($pargs));
        }
        // done
        foreach (array_keys($children) as $parentid) {
            xarVarSetCached('Privileges.getChildren', $parentid, $children[$parentid]);
        }
        xarVarSetCached('Privileges.getChildren', 'cached', 1);
        if (isset($children[$cacheId])) {
            return $children[$cacheId];
        } else {
            return array();
        }
    }

    /**
     * getDescendants: returns all objects in the privileges hierarchy below a privilege
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  array of privilege objects
    */
    function getDescendants()
    {
        // start by getting an array of the parents
        $children = $this->getChildren();

        //Get the child field for each child
        $masks = new xarMasks();
        while (list($key, $child) = each($children)) {
            $descendants = $child->getChildren();
            foreach ($descendants as $descendant) {
                $children[] =$descendant;
            }
        }

        //done
        $descendants = array();
        $descendants = array_merge($descendants,$children);
        return $descendants;
    }

    /**
     * isEqual: checks whether two privileges are equal
     *
     * Two privilege objects are considered equal if they have the same pid.
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   Object $privilege ???
     * @return  boolean
    */
    function isEqual($privilege)
    {
        return $this->getID() == $privilege->getID();
    }

    /**
     * getID: returns the ID of this privilege
     *
     * This overrides the method of the same name in the parent class
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
    */
    function getID()
    {
        return $this->pid;
    }

    /**
     * isEmpty: returns the type of this privilege
     *
     * This methods returns true if the privilege is an empty container
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
    */
    function isEmpty()
    {
        return $this->module == 'empty';
    }

    /**
     * isParentPrivilege: checks whether a given privilege is a parent of this privilege
     *
     * This methods returns true if the privilege is a parent of this one
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   Object $privileg ???
     * @return  boolean
    */
    function isParentPrivilege($privilege)
    {
        $privs = $this->getParents();
        foreach ($privs as $priv) {
            if ($privilege->isEqual($priv)) return true;
        }
        return false;
    }

    /**
     * isRootPrivilege: checks whether this privilege is root privilege
     *
     * This methods returns true if this privilege is a root privilege
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @return  boolean
    */
    function isRootPrivilege()
    {
        sys::import('modules.roles.class.xarQuery');
        $q = new xarQuery('SELECT');
        $q->addtable($this->privilegestable,'p');
        $q->addtable($this->privmemberstable,'pm');
        $q->join('p.xar_pid','pm.xar_pid'); // inner join i presume?
        $q->eq('pm.xar_pid',$this->getID());
        $q->eq('pm.xar_parentid',0);
        if(!$q->run()) return;
        return ($q->output() != array());
    }
}
?>