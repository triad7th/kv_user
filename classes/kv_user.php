<?php
require_once "./includes/kv_functions.php";
require_once "kv_user_db.php";

//
// CLASS : KvUser
//
// user login/logout class using cookie

class KvUser extends KvUserDB {
    
    //
    //
    // CONSTANTS
    //
    //

    const DEFAULT_DB_NAME = 'kv_user';
    const DEFAULT_SECRET_KEY = 'hahaotalk';
    const DEFAULT_COOKIE_NAME = 'kv_login';
    
    //
    //
    // VARIABLES
    //
    //
    
    protected $kv_secret_key;
    protected $kv_user; // array ( 'state' : logged/logout , 'id' : id )
    
    //
    //
    // PRIVATE FUNCTIONS
    //
    //
    
    private function validate($id, $pw) {
    // 
    // validate(id, password)
    //
    // [return]
    // true : valid 
    // false : invalid
    //
        // get user by id
        if( ($user = $this->getUserById($id)) === false) {
            $this->errMsg("[KvUser::validate] can't find id");
            return false;
        }
        
        $db_pw = $user['password'];
        $db_last_access = $user['last_access'];
        
        if($pw == $db_pw) {
            // if id matches with pw
            $now = time();
            if (($now - $db_last_access) > (1*60)) {
                // timeout reached
                $this->errMsg("[KvUser::validate] logged out by timout");
                $this->updateUserStateById($id,'logout');
                return false;
            } else {
                // update the last access time
                if( $this->updateUserAccessTimeById($id) === false ) {
                    $this->errMsg("[KvUser::validate] can't update last_access of user :$id");
                    return false;
                } else return true;
            }
        } else {
            $this->errMsg("[KvUser::validate] id and pw mismatch");
            return false;
        }

        return false;
    }
    
    //
    //
    // PUBLIC FUNCTIONS
    //
    //
    
    //
    // constructor / destructor
    //
    public function __construct($db_name= KvUser::DEFAULT_DB_NAME ,$secret_key= KvUser::DEFAULT_SECRET_KEY ) {
    //
    // constructor
    //
        $this->kv_secret_key=$secret_key;
        $this->kv_user = array('state'=>'init');
        
        parent::__construct($db_name);
    }
    
    //
    // login series
    //
    public function login($id, $pw) {
    //
    // login
    //
        // update access time of given id if that id is not logged on
        if($this->getUserStateById($id) !== 'logged') {
            if ( $this->updateUserAccessTimeById($id) === false ) {
                $this->errMSg("[KvUser::login] update access time error");
                return false;
            }
        }
        
        if($this->validate($id, $pw)) {
            setcookie(KvUser::DEFAULT_COOKIE_NAME, $id.','.md5($pw.$this->kv_secret_key));
            $this->kv_user = array('state'=>'logged','id'=>$id);
            if ($this->updateUserStateById($id,'logged') === false) {
                $this->errMsg("[KvUser::login] Update User State : DB access Error");
            }
            return true;
        } else {
            $this->errMsg("[KvUser::login] validate error");
            return false;
        }
        return false;
    }
    public function loggedUser() {
    //
    // loggedUser()
    //
    // Verify logged user using cookie
    // [return]
    // logged user id
    // false : error
    //
        switch ($this->kv_user['state']) {
            case 'logged'   :   return $this->kv_user['id'];
                                break;
            case 'logout'   :   return false;
                                break;
        }
            
        
        if(isset($_COOKIE[KvUser::DEFAULT_COOKIE_NAME])) {
        // if cookie exists
            list($id,$hash) = split(',',$_COOKIE[KvUser::DEFAULT_COOKIE_NAME]);
            // get password
            $pw=$this->getPasswordById($id);
            if( md5($pw.$this->kv_secret_key) == $hash ) {
            // if hash test passed
                if($this->validate($id,$pw) === true) { 
                    $this->kv_user = array('state'=>'logged','id'=>$id);
                    if( $this->updateUserStateById($id,'logged') === false ) {
                        $this->errMsg("[KvUser::loggedUser] Update User State : DB access Error");
                    }
                    return $id;
                } else {
                    $this->errMSg("[KvUser::loggedUser] Validation Error");
                }
            } else {
                $this->errMsg("[KvUser::loggedUser] Bad Cookie");
                return false;
            }
        } else {
            $this->errMsg("[KvUser::loggedUser] No Cookie Found");
            return false;
        }
        return false;
    }
    public function logout() {
    //
    // logout
    //
    // [return]
    // true : logout succeed
    // false : no logged user found
    //
        if(($id=$this->loggedUser()) !== false ) {
        // if there is loggeduser
            setcookie(KvUser::DEFAULT_COOKIE_NAME,'',1);
            $this->kv_user = array("state"=>"logout");
            if( $this->updateUserStateById($id,"logout") === false) {
                $this->errMsg("[KvUser::logout] Update User State : DB access Error");
            }
            return true;
        } else {
            $this->errMsg("[KvUser::logout] No Logged User");
            return false;
        }
        return false;
    }
    

    //
    // END OF CLASS
    //
}
?>