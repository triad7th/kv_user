<?php
require_once "./includes/kv_functions.php";
require_once "./classes/kv_fundamental.php";

//
// CLASS : KvUserDB
//
// create and management small sqlite db mainly for user management

class KvUserDB extends KvFundamental {
    //
    //
    // CONSTANTS
    //
    //
    
    const DEFAULT_PRINT_PAD = 5;
    const DEFAULT_TABLE_NAME = 'users';
    private static $USER_KEYS = array(
                            'kv_order'=>'int',
                            'id'=>'string',
                            'first_name'=>'string',
                            'middle_name'=>'string',
                            'last_name'=>'string',
                            'password'=>'string',
                            'email'=>'string',
                            'address_1'=>'string',
                            'address_2'=>'string',
                            'zipcode'=>'string',
                            'country'=>'string',
                            'last_access'=>'string',
                            'log_state'=>'string'
                            );
    
    //
    //
    // VARIABLES
    //
    //

    protected $db;
    protected $table_name;
    protected $print_pad;


    //
    //
    // PRIVATE FUNCTIONS
    //
    //
    
    private function nameToArray($name) {
    //
    // break down the given name to first,middle,last name and return the produced array
    //
        $nameArr = explode(' ',$name);
        $retArr = array('first_name'=>'','middle_name'=>'','last_name'=>'');
        switch (count($nameArr)) {
            case 0 :    break;

            case 1 :    $retArr['first_name']=$name;
                        break;
            case 2 :    $retArr['first_name']=$nameArr[0];
                        $retArr['last_name']=$nameArr[1];
                        break;
            case 3 :    $retArr['first_name']=$nameArr[0];
                        $retArr['middle_name']=$nameArr[1];
                        $retArr['last_name']=$nameArr[2];
                        break;
        }
        return $retArr;
    }
    private function keyDuplicate(array &$user) {
    //
    // duplicate some keys for the purpose of multiple key accept
    //
        kv::duplicate_key($user,array('id','ID'));
        kv::duplicate_key($user,array('first_name','fn','first name','fname'));
        kv::duplicate_key($user,array('last_name','ln','last name','lname'));
        kv::duplicate_key($user,array('middle_name','mn','middle name','mname'));
        kv::duplicate_key($user,array('password','pw','PW','pass word'));
        kv::duplicate_key($user,array('email','e-mail'));
        kv::duplicate_key($user,array('last_access','last access'));
        kv::duplicate_key($user,array('address_1','address 1','address1'));
        kv::duplicate_key($user,array('address_2','address 2','address2'));
    }
    private function keyDuplicateName(array &$user) {
    //
    // name key duplication
    //
        kv::duplicate_key($user,array('name','Name','NAME'));
    }
    private function nameExplode(array &$user) {
    //
    //  make first,middle,last name array from one 'name' string and put them to $user array
    //
        // name to name array
        $this->keyDuplicateName($user);
        if(isset($user['name'])) $user = array_merge($user,$this->nameToArray($user['name'])); 
    }
    private function nameImplode(array &$user) {
    //
    //  make one 'name' value using first,middle,last name and put into $user array
    //
        // duplicate keys
        $this->keyDuplicate($user);
        
        $name='';
        // name array to name
        if(isset($user['first_name'])) if (!empty($user['first_name'])) $name.=$user['first_name'];
        if(isset($user['middle_name'])) if (!empty($user['middle_name'])) $name.=' '.$user['middle_name'];
        if(isset($user['last_name'])) if (!empty($user['last_name'])) $name.=' '.$user['last_name'];
        
        $user['name'] = $name;
        // name duplication
        $this->keyDuplicateName($user);
    }
    private function serializeUserValues(array $user) {
        //
        // serialize user()
        //
        // serialize all user values based on USER_KEYS
        // [return]
        // serialized string
        // false : error
        
        $output = '';
        
        // if id isn't set, return false
        if(!isset($user['id'])) return false;
        
        foreach(KvUserDB::$USER_KEYS as $key=>$type) {
            
            if(isset($user[$key])) {
                switch ($type) {
                    case 'int'      : $output.="{$user[$key]},";
                        break;
                        
                    case 'string'   : $output.="\"{$user[$key]}\",";
                        break;
                }
            } else {
                switch ($type) {
                    case 'int'      : $output.=",";
                        break;
                    case 'string'   : $output.="\"\",";
                        break;
                }
            }
        }
        $output = substr($output,0,-1);
        
        return $output;
    }
    private function serializeUserKeyValues(array $user) {
        //
        // serialize user()
        //
        // serialize all user key and values based on USER_KEYS
        // [return]
        // serialized string
        // false : error
        
        $output = '';
        
        // if id isn't set, return false
        if(!isset($user['id'])) return false;
        
        foreach(KvUserDB::$USER_KEYS as $key=>$type) {
            // skip 'kv_order'
            if($key=='kv_order') continue;
            
            if(isset($user[$key])) {
                switch ($type) {
                    case 'int'      : $output.="\"$key\" = {$user[$key]},";
                        break;
                        
                    case 'string'   : $output.="\"$key\" = \"{$user[$key]}\",";
                        break;
                }
            }
        }
        $output = substr($output,0,-1);
        
        return $output;
    }
    
    //
    //
    // PUBLIC FUNCTIONS
    //
    //
    
    //
    // constructor / distructor
    //
    function __construct($fn) {
        //
        // create DB and table 'users'
        //
        // initialize
        $this->logged_user_id = '';
        $this->print_pad = KvUserDB::DEFAULT_PRINT_PAD;
        $this->table_name = KvUserDb::DEFAULT_TABLE_NAME;
        
        // create DB
        $this->db = new PDO('sqlite:db/'.$fn);
        
        $q = $this->db->query("SELECT 'id' FROM sqlite_master WHERE type = 'table' AND name = '{$this->table_name}'");
        if($q->fetch() === false) {
            //
            // 'user' table doesn't exists, do add it
            //
            
            // sql to create table
            $sql = file_get_contents('classes/queries/create_table.sql');
            
            // table 'users' creation
            $this->db->exec(trim($sql));
        }
    }
    function __destruct() {
        unset($this->db);
    }
    
    //
    // 'is' series
    //
    public function isConnected() {
    //
    // check is this db is connected
    //
        
        return isset($this->db);
    }
    public function isUserExists(array $user) {
        //
        // isUserExists(array)
        //
        // [return]
        // true : user exists
        // false : user not exist
        
        $user = $this->getUser($user);
        if($user !== false) return true;
        else return false;
    }
    public function isUserExistsById( $id ) {
        return $this->isUserExists(array('id'=>$id));
    }
    
    //
    // 'get' series
    //
    public function getCount() {
    //
    // get count of '{$this->table_name}' table
    //
        
        if($this->isConnected()) {
        //
        // if db is connected
        //
            $sql = "SELECT * FROM '{$this->table_name}'";
            $q = $this->db->query($sql);
            $rs = $q->fetchAll();
            
            return count($rs);
        } else return false;
    }
    public function getUser(array $user) {
        //
        // get a user
        //
        // check array has contents
        if(count($user) == 0) {
            return false;
        }
        // name explode
        $this->nameExplode($user);
        
        // multiple key accept
        $this->keyDuplicate($user);
        
        // find out first occurance
        $find_key ='';
        $find_value ='';
        foreach($user as $key => $value) {
            $find_key = $key;
            $find_value = $value;
            break;
        }
        
        if($this->isConnected()) {
            //
            // if db is connected
            //
            $sql = "SELECT * FROM {$this->table_name} WHERE \"$find_key\" LIKE \"$find_value\"";
            $rows= $this->db->query($sql);
            $found = false;
            
            // fetch 1st row
            $row = $rows->fetch();
            
            // if found anything
            if($row !== false) {
                $this->keyDuplicate($row);
                $this->nameImplode($row);
                $this->keyDuplicateName($row);
                return $row;
            } else return false;
        } else return false;
    }
    public function getUserById($id) {
        //
        // get user by id
        //
        $user = $this->getUser(array('id'=>$id));
        if($user !== false) return $user;
        else return false;
    }
    public function getPrimekeyById($id) {
        //
        // get primekey by id
        //
        $ret = $this->getUser(array('id'=>$id));
        if($ret !== false) return $ret['kv_order'];
        else false;
    }
    public function getUserStateById($id) {
        //
        // get user state by id
        //
        $ret = $this->getUser(array('id'=>$id));
        if($ret !== false) return $ret['log_state'];
        else false;
    }
    public function getPasswordById($id) {
        //
        // literally, get password by ID
        //
        if( $row = $this->getUser(array('id'=>$id)) ) {
            return $row['password'];
        } else return false;
    }
    public function getLargestPrimekey() {
    //
    // retrieve largest prime key
    //
        if($this->isConnected()) {
            $sql = "SELECT kv_order FROM {$this->table_name} ORDER BY kv_order DESC LIMIT 1";
            $rows = $this->db->query($sql);
            
            if($rows === false) return false;
            
            // fetch 1st row
            $row = $rows->fetch();
            if($row === false) return false;
            
            // return largest prime key
            return $row['kv_order'];
        }
    }
    
    //
    // 'add' series
    //
    public function addUser(array $user) {
    //
    // add a user ( id, firstname, middlename, lastname, password )
    //
        // name explode
        $this->nameExplode($user);
        
        // multiple key accept
        $this->keyDuplicate($user);
        
        if($this->isConnected()) {
            //
            // if db is connected
            //
            if( ($count = $this->getLargestPrimekey()) === false) return false;
            
            if($count !== false ) {
                // add count
                $count++;
                $user['kv_order']=$count;
                
                // serialize user
                if( $serialized = $this->serializeUserValues($user) ) {
                    // sql to insert table
                    $sql = "INSERT INTO {$this->table_name} VALUES (".$serialized.")";
                    if($this->db->exec($sql)) return true;
                    else return $this->returnFalse("[KvUserDb:addUser] db execution error");
                } else return $this->returnFalse("[KvUserDb:addUser] serializeUserValues error");
            } else return false;
        } else return false;
    }
    public function addUserStrict(array $user) {
    //
    // add a user with new id
    //
        
        // multiple key accept
        $this->keyDuplicate($user);
        
        if(isset($user['id'])) {
            if($this->isUserExistsById($user['id']) === false) {
                return $this->addUser($user);
            } else return false;
        } else return false;
    }
    
    //
    // 'update' series
    //
    public function updateUser(array $user) {
    //
    // update a user 
    //
        // multiple key accept
        $this->keyDuplicate($user);
        
        if($this->isConnected()) {
        // if db is connected
            $gotten_user = $this->getUser($user);
            if($gotten_user !== false) {
                $kv_order = $gotten_user['kv_order'];
                $user['kv_order']=$kv_order;
                
                if( $serialized = $this->serializeUserKeyValues($user) ) {
                    $q = "UPDATE {$this->table_name} SET ".$serialized." WHERE kv_order LIKE ?";
                    $sql = $this->db->prepare($q);
                    if($sql) {
                        if ($sql->execute(array($kv_order))) return $kv_order;
                            else return false;                        
                    } else return false;
                } else return false;                
            } else return false;
        } else return false;
    }
    public function updateUserAccessTimeById($id) {
    //
    // update the last_access column of given user into current time
    //
    // [return]
    // succeed : primekey
    // failed : false
        
        $ret=$this->updateUser(array(
                         'id'=>$id,
                         'last_access'=>time()
                         ));
        if($ret !== false) return $ret;
        else return false;
    }
    public function updateUserStateById($id,$state) {
        //
        // update the log_state column of given user into given state
        //
        // [return]
        // succeed : primekey
        // failed : false
        
        $ret=$this->updateUser(array(
                              'id'=>$id,
                              'log_state'=>$state
                              ));
        if($ret !== false) return $ret;
        else return false;
    }
    
    //
    // 'delete' series
    //
    public function deleteByPrimekey($pk) {
    //
    // Obviously,, delete row by primekey
    //
        if($this->isConnected()) {
        //
        // if db is connected
        //    
            $sql = "DELETE FROM {$this->table_name} WHERE kv_order = \"$pk\"";
            if($this->db->exec($sql)) return true;
                else return false;            
        } else return false;
    }
    
    //
    // 'print' series
    //
    public function printUsers($direction = 'horizontal', $pad = KvUserDB::DEFAULT_PRINT_PAD) {
        
        $this->setPrintPad($pad);
        switch(strtolower($direction)) {
            case 'vertical' :
                $this->printUsersVertical();
                break;
            case 'horizontal' :
                $this->printUsersHorizontal();
                break;
            default :
                $this->printUsersHorizontal();
                break;
        }
    }
    public function printUsersVertical() {
    //
    // print 'users' table vertically
    //
        if($this->isConnected()) {
        // if db is connected
            $sql = "SELECT * FROM '{$this->table_name}'";
            $q = $this->db->query($sql);
            $rs = $q->fetchAll();
            
            if(count($rs)>0) {
                // get keys
                $keys = array_keys($rs[0]);
                // unset numbered keys
                $count_keys = count($keys);
                for($i=1;$i<$count_keys;$i+=2) unset($keys[$i]); // placeholder code
                
                foreach ($keys as $key) {
                    $output = '';
                    $output .= str_pad(
                                       substr($key,0,$this->print_pad),
                                       $this->print_pad,
                                       ' '
                                       ).'|';
                    foreach ($rs as $r) {
                        $output.= str_pad(
                                          substr($r[$key],0,$this->print_pad),
                                          $this->print_pad,
                                          ' '
                                          ).'|';
                    }
                    kv::kprint($output."\n");
                    if($key=='kv_order') kv::kprint(str_pad(
                                                            '-',
                                                            ($this->print_pad + 1)*(count($rs)+1),
                                                            '-'
                                                            )
                                                    ."\n");
                }
            } else return false;
        } else return false;
        return true;
    }
    public function printUsersHorizontal() {
    //
    // print 'users' table ( id, firstname, middlename, lastname, password )
    //
        if($this->isConnected()) {
        // if db is connected
            $sql = "SELECT * FROM '{$this->table_name}'";
            $q = $this->db->query($sql);
            $rs = $q->fetchAll();
            
            if(count($rs)>0) {
                // printout keys
                $keys = array_keys($rs[0]);
                $output = '';
                $barline = '';
                foreach ($keys as $n => $key) {
                    if(($n % 2) == 1) continue; // rough code
                    $output.= str_pad(substr($key,0,$this->print_pad),$this->print_pad,' ');
                    $barline.= str_pad('',$this->print_pad+1,'-');
                    $output.='|';
                }
                kv::kprint($output);
                
                // printout barline
                kv::kprint($barline);
                
                // printout rows
                foreach ($rs as $i => $r) {
                    $output = '';
                    $n=0;
                    foreach($r as $c) {    
                        if(($n++ % 2) == 1) continue; // rough code
                        $output.= str_pad(substr($c,0,$this->print_pad),$this->print_pad,' ');
                        $output.='|';
                    }
                    kv::kprint($output);
                }
            }
        } else return false;
        
        return true;
    }
    public function setPrintPad($pad) {
        if($pad>30) $pad=30;
        $this->print_pad = $pad;
    }
    
    //
    // END OF CLASS
    //
}
?>