<?php
/*
Plugin Name: eduTrac Authentication
Plugin URI: http://www.7mediaws.org/extend/plugins/edutrac-authentication/
Description: This plugin bypasses the native WordPress register, login, and password retrieval system and is replaced by the eduTrac RESTful API for authenticating users.
Version: 1.0.2
Author: Joshua Parker
Author URI: http://www.7mediaws.org/
*/

function et_auth_activate() {
    add_option('et_url',"","eduTrac URL Install");
    add_option('et_auth_token',"","eduTrac API Auth Token");
    add_option('et_error_msg',"","Custom login message");
}

function et_auth_init(){
    register_setting('et_auth','et_url');
    register_setting('et_auth','et_auth_token');
    register_setting('et_auth','et_error_msg');
}

//page for config menu
function et_auth_add_menu() {
    add_options_page("eduTrac API Settings", "eduTrac API Settings", 'manage_options', __FILE__, "et_auth_display_options");
}

//actual configuration screen
function et_auth_display_options() {
?>
    <div class="wrap">
    <h2><?php _e( 'eduTrac RESTful API Auth Settings' ); ?></h2>        
    <form method="post" action="options.php">
    <?php settings_fields('et_auth'); ?>
        <h3><?php _e( 'eduTrac Authentication Settings' ); ?></h3>
          <strong><?php _e( 'Make sure your WP admin account exists in your eduTrac install prior to saving these settings and logging out.'); ?></strong>
        <table class="form-table">
        <tr valign="top">
            <th scope="row"><label><?php _e( 'eduTrac Site URL' ); ?></label></th>
                <td><input type="text" name="et_url" value="<?php echo get_option('et_url'); ?>" class="regular-text code" /> <br />
                <span class="description"><strong style="color:red;"><?php _e( 'required' ); ?></strong>; <?php _e( 'URL to the root install. Make sure to include "/" trailing slash.' ); ?></span> </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php _e( 'eduTrac Auth Token' ); ?></label></th>
                <td><input type="text" name="et_auth_token" value="<?php echo get_option('et_auth_token'); ?>" class="regular-text code" /> <br />
                <span class="description"><strong style="color:red;"><?php _e( 'required' ); ?></strong>; <?php _e( 'Enter the auth_token from your eduTrac install.' ); ?></span></td>
        </tr>
        </table>
        <h3><?php _e( 'Other' ); ?></h3>
        <table class="form-table">
        <tr valign="top">
                <th scope="row"><?php _e( 'Custom login message' ); ?></th>
                <td><textarea name="et_error_msg" cols="40" rows="4" class="large-text code"><?php echo htmlspecialchars(get_option('et_error_msg'));?></textarea> <br />
                <span class="description"><?php _e( 'Shows up in login box, e.g., to tell them where to get an account. You can use HTML in this text.' ); ?></td>
        </tr>        
    </table>
    
    <p class="submit">
    <input type="submit" name="submit" class="button-primary" value="Save Changes" />
    </p>
    </form>
    </div>
<?php
}

function et_hash_password($password) {
    // By default, use the portable hash from phpass
    $et_hasher = new PasswordHash(8, FALSE);

        return $et_hasher->HashPassword($password);
}
 
function et_check_password($password, $hash, $user_id = '') {

    // If the hash is still md5...
    if ( strlen($hash) <= 32 ) {
        $check = ( $hash == md5($password) );
    if ( $check && $user_id ) {
        // Rehash using new hash.
        et_set_password($password, $user_id);
        $hash = et_hash_password($password);
    }

    return apply_filters('check_password', $check, $password, $hash, $user_id);
    }

    // If the stored hash is longer than an MD5, presume the
    // new style phpass portable hash.
    $et_hasher = new PasswordHash(8, FALSE);

    $check = $et_hasher->CheckPassword($password, $hash);

        return apply_filters('check_password', $check, $password, $hash, $user_id);
}

/** 
 * The actual meat of the plugin: essentially, you're setting $username and $password to pass on to the system. 
 * You check from the RESTful API and insert/update users into the WP system just before WP actually 
 * authenticates with its own database.
 */
function et_auth_check_login($username,$password) {
    require_once('./wp-includes/registration.php');
    require_once('./wp-includes/user.php');
    require_once('./wp-includes/pluggable.php');
    require_once('./wp-includes/class-phpass.php');
    
    $enc = rand(22,999999*1000000);
    $salt = substr(hash('sha512',$enc),0,22);
    
    if($username !== null) {
        $url = file_get_contents(get_option('et_url')."api/erp/person/uname/".$username.".json?auth_token=".get_option('et_auth_token'));
        $json = json_decode($url, true);
    
        foreach ($json as $k => $v) {
            $array[] = $v;
        }
    }
    
    $cookie = sprintf("data=%s&auth=%s", urlencode($username), urlencode(et_hash_password($username.$password)));
    $mac = hash_hmac("sha512", $cookie, $enc);
    $auth = $cookie . '&digest=' . urlencode($mac);
        
    if ($v['uname'] == $username) {    //create/update wp account from external database if login/pw exact match exists in that db      
                                    
        //only continue with user update/creation if login/pw is valid AND, if used, proper role perms
        if(et_check_password( $password, $v['password'] )) {
            
            $userarray = array();
            $userarray['user_login'] = $username;
            $userarray['user_pass'] = $password;                    
            $userarray['first_name'] = $v['fname'];
            $userarray['last_name'] = $v['lname'];        
            $userarray['user_email'] = $v['email'];
            $userarray['display_name'] = $v['fname']." ".$v['lname'];           
            
            //also if no extended data fields
            if ($userarray['display_name'] == " ") $userarray['display_name'] = $username;
            
            //looks like wp functions clean up data before entry, so I'm not going to try to clean out fields beforehand.
            if ($id = username_exists($username)) {   //just do an update
                 $userarray['ID'] = $id;
                 wp_update_user($userarray);
            }
            else wp_insert_user($userarray); //otherwise create
        } 
    }                 
    else {  //username exists but wrong password...         
        global $et_error;
        $et_error = "wrongpw";              
        $username = NULL;
    }
}


//gives warning for login - where to get "source" login
function et_auth_warning() {
   echo "<p class=\"message\">".get_option('et_error_msg')."</p>";
}

function et_errors() {
    global $error;
    global $et_error;
    if ($et_error == "notindb")
        return "<strong>ERROR:</strong> Username not found.";
    else if ($et_error == "wrongrole")
        return "<strong>ERROR:</strong> You don't have permissions to log in.";
    else if ($et_error == "wrongpw")
        return "<strong>ERROR:</strong> Invalid password.";
    else
        return $error;
}

//hopefully grays stuff out.
function et_warning() {
    echo '<h3>Login Message</h3>
           <table class="form-table">
           <tr>
            <td>
            <label><strong style="color:#A52A2A;">Any changes made below *WILL NOT* be preserved when you login again. 
            You must update your profile per the instruction found @ 
            <a href="' . get_option('et_url') . '">'.get_option('et_url').'</a>.</strong></label>
           </td>
           </tr>
           </table>';
}

//disables the (useless) password reset option in WP when this plugin is enabled.
function et_show_password_fields() {
    return 0;
}


/*
 * Disable functions. Idea taken from http auth plugin.
 */
function disable_function_register() {  
    $errors = new WP_Error();
    $errors->add('registerdisabled', __('User registration is not available from this site, so you can\'t create an account or retrieve your password from here. See the message above.'));
    ?></form><br /><div id="login_error"><?php _e( 'User registration is not available from this site, so you can\'t create an account or retrieve your password from here. See the message above.' ); ?></div>
        <p id="backtoblog"><a href="<?php bloginfo('url'); ?>/" title="<?php _e('Are you lost?') ?>"><?php printf(__('&larr; Back to %s'), get_bloginfo('title', 'display' )); ?></a></p>
    <?php
    exit();
}

function disable_function() {   
    $errors = new WP_Error();
    $errors->add('registerdisabled', __('User registration is not available from this site, so you can\'t create an account or retrieve your password from here. See the message above.'));
    login_header(__('Log In'), '', $errors);
    ?>
    <p id="backtoblog"><a href="<?php bloginfo('url'); ?>/" title="<?php _e('Are you lost?') ?>"><?php printf(__('&larr; Back to %s'), get_bloginfo('title', 'display' )); ?></a></p>
    <?php
    exit();
}

// i18n
$plugin_dir = basename(dirname(__FILE__)). '/languages';
load_plugin_textdomain( 'edutrac-authentication', WP_PLUGIN_DIR.'/'.$plugin_dir, $plugin_dir );


add_action('admin_init', 'et_auth_init' );
add_action('admin_menu', 'et_auth_add_menu');
add_action('wp_authenticate', 'et_auth_check_login', 1, 2 );
add_action('lost_password', 'disable_function');
add_action('register_form', 'disable_function_register');
add_action('retrieve_password', 'disable_function');
add_action('password_reset', 'disable_function');
add_action('profile_personal_options','et_warning');
add_filter('login_errors','et_errors');
add_filter('show_password_fields','et_show_password_fields');
add_filter('login_message','et_auth_warning');

register_activation_hook( __FILE__, 'et_auth_activate' );