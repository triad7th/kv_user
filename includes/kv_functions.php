<?php
// GLOBAL FUNCTIONS
    function kv_print($string) {
        echo '<div style="font-family : Courier; font-size : 10px;">'.kv::add_br($string).'</div>';
    }
    
//
// CLASS kv (static function set)
// 
// collection of functions for kevin's php coding

class kv {
    //
    // multiexplode ($dels, $string)
    //
    // multi explode ( from php.net example )
    //
    function multiexplode ($delimiters,$string) {
        
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);
        return  $launch;
    }
    
    //
    // form_load
    //
    // load form from file with arguments
    //
    public static function form_load($fn,array $args) {
    
        $form=file_get_contents($fn);
        if($form !== false) {
            foreach($args as $key => $value) {
                $form = str_replace("\"\$$key\"","\"$value\"",$form);
                $form = str_replace("~$key","$value",$form);
            }
            return $form;
        } else return false;
    }
    
    //
    // form_textbox
    //
    // simple textbox input form
    //
    public static function form_textbox(array $args) {
        
        $args=array_merge(array (
            'action' => '',
            'id' => 'kv_textbox',
            'placeholder' => 'type command here',
            'submit' => 'submit',
            'size' => '80',
            'hidden' => 'hidden',
            'hidden_value' => 'hidden_value'
        ),$args);
        
        $form=kv::form_load('includes/forms/form_textbox.html',$args);
        
        if($form !== false) echo $form;
            else return false;
    }
    
    //
    // form_login
    //
    // simple login form
    //
    public static function form_login(array $args) {
        
        $args=array_merge(array (
            'action' => '',
            'id' => 'kv_id',
            'pw' => 'kv_pw',
            'id_placeholder' => 'id',
            'pw_placeholder' => 'pw',
            'submit' => 'submit',
            'id_size' => '40',
            'pw_size' => '40',
            'hidden' => 'hidden',
            'hidden_value' => 'hidden_value'
        ),$args);
        
        $form=kv::form_load('includes/forms/form_login.html',$args);
        //kv::kprint(htmlentities($form));
        if($form !== false) echo $form;
            else return false;
    }
    
    //
    // form_logout
    //
    // simple logout form
    //
    public static function form_logout(array $args) {
        
        $args=array_merge(array (
            'action' => '',
            'submit' => 'logout',
            'hidden' => 'cmd',
            'hidden_value' => 'logout'
        ),$args);
        
        $form=kv::form_load('includes/forms/form_logout.html',$args);
        //kv::kprint(htmlentities($form));
        if($form !== false) echo $form;
            else return false;
    }
    
    //
    // form_cmdline
    //
    // simple commandline form
    //
    public static function form_cmdline(array $args) {
        
        $args=array_merge(array (
            'id' => 'kv_cmdline',
            'action' => '',
            'submit' => 'submit',
            'placeholder' => 'type command here',
            'size' => '80',
            'hidden' => 'hidden',
            'hidden_value' => 'hidden_value',
            'logout_submit' => 'logout',
            'logout_hidden' => 'cmd',
            'logout_hidden_value' => 'logout'
            ),$args);
        
        $form=kv::form_load('includes/forms/form_cmdline.html',$args);
        if($form !== false) echo $form;
        else return false;
    }
    
    //
    // form_adduser
    //
    // simple adduser form
    //
    public static function form_adduser(array $args) {
        
        $args=array_merge(array (
                                 'action' => '',
                                 'margin' => '5px',
                                 'width' => '500px',
                                 'id_placeholder' => 'id',
                                 'id_size'=> '60%',
                                 'pw_placeholder' => 'password',
                                 'pw_size'=> '30%',
                                 'name_placeholder' => 'name',
                                 'name_size' => '30%',
                                 'email_placeholder' => 'email',
                                 'email_size' => '60%',
                                 'address1_placeholder' => 'address1',
                                 'address1_size' => '91.1%',
                                 'address2_placeholder' => 'address2',
                                 'address2_size' => '91.1%',
                                 'zipcode_placeholder' => 'zipcode',
                                 'zipcode_size' => '30%',
                                 'country_placeholder' => 'country',
                                 'country_size' => '60%',
                                 'submit' => 'submit',
                                 'hidden' => 'cmd',
                                 'hidden_value' => 'adduser_execute'
                                 ),$args);
        
        $form=kv::form_load('includes/forms/form_adduser.html',$args);
        if($form !== false) echo $form;
        else return false;
    }
    
    //
    // duplicate_key(destination array, key array)
    //
    // duplicate value of first occurance in $arr which has key in $keys, adding new keys to $arr
    //
    public static function duplicate_key(array &$arr, array $keys) {
    
        foreach($keys as $key) {
            if(isset($arr[$key])) {
                $copy=$arr[$key];
                break;
            }
        }

        if(isset($copy)) {
            foreach($keys as $key) {
                $arr[$key] = $copy;
            }
        } else return false;
    }
    
    //
    // kprint(string)
    //
    // printout html coded string using fixed width font
    //
    public static function kprint($string) {
        echo '<div style="font-family : Courier; font-size : 10px;">'.kv::add_br($string).'</div>';
    }
    
    //
    // add_br(string)
    //
    // encode string to html style
    // 
    function add_br($string) {
        $string = str_replace("\n","<br>",$string);
        $string = str_replace(" ","&nbsp",$string);

        return $string;
    }
    
    //
    // kvar_dump(string)
    //
    // kv version of var_dump
    // 
    public static function kvar_dump($string) {
        kv::kprint(kv::add_br(var_export($string,true)));
    }

    //
    // console_mode
    //
    // console mode for kv::add_br
    // 
    public static function console_mode() {
        ob_start('kv::add_br');
    }

    //
    // console_mode_flush
    //
    // flush console mode
    // 
    public static function console_mode_flush() {
        ob_end_flush();
    }

    //
    // image(filename)
    //
    // make a image tag with a given filename
    // 
    public static function image($file) {
        $html = '';

        $html = "<br/><img src='$file' width='auto' height='auto' >";
        return $html;
    }
}

?>