<?php
/*
 * Plugin Name: Form Filtering Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}
// Include our updater file
include_once( plugin_dir_path( __FILE__ ) . '/updater/updater.php');

// $updater = new EE_Updater( __FILE__ ); // instantiate our class
// $updater->set_username( 'rayman813' ); // set username
$updater->set_repository( 'form-filtering-plugin' ); // set repo
$updater->initialize(); // initialize the updater


 Class EE_Form_Filter {

    private $defaultErrorMessages;
    private $personal = null;
    private $competitors = null;
    private $nb_api = null;

    public function __construct() {
        /**
         * Authentication is setup in bootstrap file
         */
        require_once __DIR__ . '/vendor/autoload.php';
        // require_once __DIR__ . '/.api-key.php';
        if(get_option('ee-neverbounce-api')){
            $this->nb_api = get_option('ee-neverbounce-api');
            \NeverBounce\Auth::setApiKey($this->nb_api);
        }
        if (empty($this->nb_api)) {
            // throw new Exception(
            //     'The API key was not defined before running the '
            //     . 'examples. Create a `.env` file in the root directory '
            //     . 'of this package and specify the API_KEY before running '
            //     . 'the examples.'
            // );
        }

        // sets default error messages
        $this->defaultErrorMessages = [
            'not-email' => 'An Email Address needs an @',
            'disposable' => 'This is a disposable email address.',
            'first-last' => 'The First name and/or Last name cannot be "first" or "last"',
            'one-char' => 'A name cannot be just one character',
            'two-char' => 'A name cannot be 2 characters that are the same letter or both vowels',
        ];
        
        $this->personal = get_option('ee-ff-personal-domains');
        //sets defines
        define( 'EE_FILTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

        // activation hook
		register_activation_hook( __FILE__, array( &$this, 'set_cron' ) );
		// deactivation hook
		// register_deactivation_hook( __FILE__, array( &$this, 'deactivation_hook' ) );
        // Cron to get list.
        // add_action( 'ee_get_list',  array( &$this, 'get_disposable_emails') );

        
        //creates menu page
        add_action( 'admin_menu',  array( &$this, 'ee_form_filter_menu') );

        // enqueue script
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

        // Gravity Forms validation
        add_filter( 'gform_field_validation', array( &$this, 'form_filter_validation'), 10, 4 );
        add_filter( 'gform_entry_is_spam', array( &$this, 'filter_gform_entry_is_spam_user_agent'), 11, 3 );
    }
 
  

    /**
     * Enqueues JS And CSS and allows JS to access the blacklist and the errors.
     */
    public function enqueue() {
        $upload_dir = wp_upload_dir();
        wp_enqueue_script('ee-ff', EE_FILTER_PLUGIN_URL . 'assets/app.js', array('jquery'), time(), true);
        wp_enqueue_style('ee-ff', EE_FILTER_PLUGIN_URL . 'assets/app.css', array(), '');
        wp_localize_script('ee-ff', 'formFilter', array(
            'ajax' => admin_url('admin-ajax.php'),
            'list' => $upload_dir['baseurl'] . "/ee-form-filter/black-list.json",
            'errors' => json_encode(get_option('ee-ff-error'))
        ));
    }

    public function set_cron() {
        // Set default error message array
        // add_option( 'ee-ff-error', 'Plugin-Slug' );
        if(get_option('ee-ff-error') == null){
            update_option('ee-ff-error', $this->defaultErrorMessages);
        }
        // Schedule Cron
		// if ( ! wp_next_scheduled( 'ee_get_list' ) ) {
        //     wp_schedule_event( time(), 'daily', 'ee_get_list' );
        // }
    }
   

    /**
     * Gets list of disposable emails converts to json and saves as file.
     */
    public function get_disposable_emails() {
        //your function...
        $url = 'https://raw.githubusercontent.com/disposable/disposable-email-domains/master/domains.txt';
        $requests_response = WpOrg\Requests\Requests::get( $url );
        if(isset($requests_response->body)) {
            $blacklist = explode("\n", $requests_response->body);
            $blacklist = json_encode($blacklist);
            $upload_dir = wp_upload_dir();
            wp_mkdir_p( $upload_dir['basedir'] . "/ee-form-filter/" );
            if ( ! file_exists( $upload_dir['basedir'] . "/ee-form-filter/black-list.json" ) ) {
                $handle = fopen( $upload_dir['basedir'] . "/ee-form-filter/black-list.json", 'w');
                fwrite($handle, $blacklist);
                fclose($handle);
            }
            if ( $handle = fopen( $upload_dir['basedir'] . "/ee-form-filter/black-list.json", 'w') ) {
                fwrite($handle, $blacklist);
                fclose($handle);
            }
        }
        return false;
    }

    /**
     * sets up admin page.
     */
    public function ee_form_filter_menu() {
        add_menu_page( 'Form Filtering', 'Form Filtering', 'manage_options', 'form-filtering-plugin', array(&$this, 'ee_filtering_admin_page'), 'dashicons-forms', 6  );
    }

    /**
     * admin page to show competitors list, personal list, and error messages
     */
    public function ee_filtering_admin_page(){
        $this->ee_form_filter_save_options();
        $competitors = get_option('ee-ff-competitors-domains') ?: '';
        $personal = get_option('ee-ff-personal-domains') ?: ["gmail.com", "yahoo.com"];
        $error = get_option('ee-ff-error');
        $neverbounce = get_option('ee-neverbounce-api');
        $neverbouncePriceLimit = get_option('ee-neverbounce-price-limit');
        print_r($neverbounce);
        // $blacklist = $this->get_disposable_emails();
        ?>
        <div class="wrap">
            <h2>Form Filtering Options</h2>
            <form action="" method="post">
                <div style="display: flex; gap: 20px;">
                    <div>
                        <h3>Personal Domains</h3>
                        <div>
                            <small>Put in the domains you want to filter out one per line.</small>
                        </div>
                        <textarea name="ee-ff-personal-domains" id="ee-ff-personal-domains" rows="15" columns="50"><?php echo $this->convert_array_to_textarea_data($personal); ?></textarea>
                        <h3>Competitors Domains</h3>
                        <div>
                            <small>Put in the domains you want to filter out one per line.</small>
                        </div>
                        <textarea name="ee-ff-competitors-domains" id="ee-ff-competitors-domains" rows="15" columns="50"><?php echo $this->convert_array_to_textarea_data($competitors); ?></textarea>

                        <h3>NeverBounce API Key</h3>
                        <input style="width: 100%;" type="password" name="ee-neverbounce-api" id="ee-neverbounce-api" value="<?php echo $neverbounce; ?>" />
                    </div>
                    <div style="width: 100%">
                    <h3>Error Messages</h3>
                    <h4>Not an Email Message</h4>
                    <div style="display: flex; flex-direction:column; gap: 10px">
                        <input style="width: 100%;" type="text" name="ee-error-not-email" id="ee-error-not-email" value="<?php echo $error['not-email']; ?>" />
                        <input style="width: 100%;" type="text" name="ee-error-disposable" id="ee-error-disposable" value="<?php echo $error['disposable']; ?>" />
                        <input style="width: 100%;" type="text" name="ee-error-first-last" id="ee-error-first-last" value="<?php echo htmlspecialchars($error['first-last']); ?>" />
                        <input style="width: 100%;" type="text" name="ee-error-one-char" id="ee-error-one-char" value="<?php echo htmlspecialchars($error['one-char']); ?>" />
                        <input style="width: 100%;" type="text" name="ee-error-two-char" id="ee-error-two-char" value="<?php echo htmlspecialchars($error['two-char']); ?>" />
                        
                    </div>
                    </div>
                </div>
                <?php submit_button( 'Save' ); ?>
		        <?php wp_nonce_field( 'ee-filtering-admin-save', 'ee-filtering-admin-save-nonce' ); ?>
            </form>
        </div>
        <style>
            textarea {
                width: 100%;
                max-width: 500px;
                min-height: 250px;
            }
        </style>
        <?php
    }

    /**
     * Checks if is Json for json decode of text area.
     */
    private function isJson($string) {
        if (!is_string($string)) {
            return false;
        }

        try {
            json_decode($string, false,512, JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException $e) {
            return false;
        }
    }

    /**
     * Converts json to text area with each line as a separate element
     */
    private function convert_array_to_textarea_data($array){
        if($this->isJson($array)){
            $array = json_decode($array);
        }
        if( !is_array($array) ) return $array;
        return implode("\r\n", $array);
    }

    /**
     * saves options.
     * inputs that start with ee-error- are group together in an array
     */
    private function ee_form_filter_save_options() {
        
        $action       = 'ee-filtering-admin-save';
        $nonce        = 'ee-filtering-admin-save-nonce';
        print_r($_POST);
        // If the user doesn't have permission to save, then display an error message
        if ( ! $this->ee_user_can_save( $action, $nonce ) ) {
            return;
        }
        if ( isset( $_POST['ee-ff-personal-domains'] ) ) {
            $text = sanitize_textarea_field($_POST['ee-ff-personal-domains']);
            $domains = explode("\r\n", $text);
            update_option('ee-ff-personal-domains', json_encode($domains));
		} else {
            delete_option( 'ee-ff-personal-domains' );
		}

        if ( isset( $_POST['ee-ff-competitors-domains'] ) ) {
            $text = sanitize_textarea_field($_POST['ee-ff-competitors-domains']);
            $domains = explode("\r\n", $text);
            update_option('ee-ff-competitors-domains', json_encode($domains));
		} else {
            delete_option( 'ee-ff-competitors-domains' );
		}
        if ( isset( $_POST['ee-neverbounce-api'] ) ) {
            $text = sanitize_text_field($_POST['ee-neverbounce-api']);
            update_option('ee-neverbounce-api', $text);
		} else {
            delete_option( 'ee-neverbounce-api' );
		}
        if ( isset( $_POST['ee-neverbounce-price-limit'] ) ) {
            $text = sanitize_text_field($_POST['ee-neverbounce-price-limit']);
            update_option('ee-neverbounce-price-limit', $text);
		} else {
            delete_option( 'ee-neverbounce-price-limit' );
		}
      
        $this->defaultErrorMessages = get_option('ee-ff-error');
        
        foreach( $_POST as $key => $value ) {
            if(str_starts_with(strtolower($key), 'ee-error-')) {
                if(isset($value) && $value !== ''){
                    $strKey = str_replace('ee-error-', '', $key);
                    $this->defaultErrorMessages[$strKey] = sanitize_text_field($value);
                }
            }
            
        }
        update_option('ee-ff-error', $this->defaultErrorMessages);
}

/**
 * 
 */
public function form_filter_validation( $result, $value, $form, $field ) {
   
    GFCommon::log_debug( __METHOD__ . '(): $ee-ff-personal-domains => ' . print_r( json_decode(get_option('ee-ff-personal-domains')), true ) );
    $form = $result['form'];
    
    $field_types_to_check = array(
        'text',
        'textarea',
    );
 
    $text_to_check = array();
    if(in_array($field->type, $field_types_to_check)){

   
    // foreach ( $form['fields'] as $field ) {
        GFCommon::log_debug( __METHOD__ . '(): $field => ' . print_r( $field, true ) );
        GFCommon::log_debug( __METHOD__ . '(): $value => ' . print_r( $value, true ) );
        // Skipping fields which are administrative or the wrong type.
        // if ( $field->is_administrative() || ! in_array( $field->get_input_type(), $field_types_to_check ) ) {
        //     break;
        // }
 
        // // Skipping fields which don't have a value.
        // $value = rgpost( $field );
        // if ( empty( $value ) ) {
        //     break;
        // }
 
        $text_to_check[] = $value;
    // }
 
    if ( empty( $text_to_check ) ) {
        return false;
    }
 
    $args = array(
        'text' => urlencode( implode( "\r\n", $text_to_check ) ),
    );
 
    $response = wp_remote_get( add_query_arg( $args, 'https://www.purgomalum.com/service/containsprofanity' ) );
 
    GFCommon::log_debug( __METHOD__ . '(): profanity response => ' . print_r( $response, true ) );
    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        GFCommon::log_debug( __METHOD__ . '(): profanity response => ' . print_r( $response, true ) );
 
        return false;
    }
    // set the form validation to false
    GFCommon::log_debug( __METHOD__ . '(): profanity ' . wp_remote_retrieve_body( $response ) === 'true' );;
    if( wp_remote_retrieve_body( $response ) === 'true' ) {
        $result['is_valid'] = false;
        $result['message'] = 'Profanity is not allowed.';
    }
}
if($field->type == 'email'){
    GFCommon::log_debug( __METHOD__ . '(): $EMAIL => ' . print_r( $value, true ) );
    $value = is_array($value) ? $value : array($value);
    // Verify a single email
        

       
    foreach($value as $email) {
        if(!isset($email) || $email == '' ) return;
        $emailArray = explode("@", $email);
        if(in_array($emailArray[1], json_decode($this->personal)) ){
            $result['is_valid'] = false;
            $result['message'] = 'Please use a business email.';
            return;
        }
        $verification = \NeverBounce\Single::check($email, true, true);
        if($verification->result_integer == 1) {
            $result['is_valid'] = false;
            $result['message'] = 'Email address is not valid';
        }
        if($verification->result_integer == 2) {
            $result['is_valid'] = false;
            $result['message'] = 'Email is a temporary, disposable email address';
        }
        if($verification->result_integer == 4) {
            $result['is_valid'] = false;
            $result['message'] = 'The server cannot be reached';
        }
        // Get verified email
        GFCommon::log_debug( __METHOD__ . '(): Email Verified: ' . $verification->email );
        GFCommon::log_debug( __METHOD__ . '(): Numeric Code: ' . $verification->result_integer);
        GFCommon::log_debug( __METHOD__ . '(): Text Code: ' . $verification->result);
        GFCommon::log_debug( __METHOD__ . '(): Has DNS: ' . (string) $verification->hasFlag('has_dns'));
        GFCommon::log_debug( __METHOD__ . '(): Is free mail: ' . (string) $verification->hasFlag('free_email_host'));
        GFCommon::log_debug( __METHOD__ . '(): Suggested Correction: ' . $verification->suggested_correction);
        GFCommon::log_debug( __METHOD__ . '(): Is unknown: ' . (string) $verification->is('unknown'));
        GFCommon::log_debug( __METHOD__ . '(): Isn\'t valid or catchall: ' . (string) $verification->not(['valid', 'catchall']));
        $credits = ($verification->credits_info->paid_credits_used
            + $verification->credits_info->free_credits_used);
        GFCommon::log_debug( __METHOD__ . '(): Credits used: ' . $credits);
        
    }
    
}
    //supposing we don't want input 1 to be a value of 86
    // if ( rgpost( 'input_1' ) == 86 ) {
    
    //     // set the form validation to false
    //     $validation_result['is_valid'] = false;
    
    //     //finding Field with ID of 1 and marking it as failed validation
    //     foreach( $form['fields'] as &$field ) {
    
    //         //NOTE: replace 1 with the field you would like to validate
    //         if ( $field->id == '1' ) {
    //             $field->failed_validation = true;
    //             $field->validation_message = 'This field is invalid!';
    //             break;
    //         }
    //     }
    
    // }
    
    //Assign modified $form object back to the validation result
    $result['form'] = $form;
    return $result;
      
}


 public function filter_gform_entry_is_spam_user_agent( $is_spam, $form, $entry ) {
    if ( $is_spam ) {
        return $is_spam;
    }
 
    $user_agent = rgar( $entry, 'user_agent' );
 
    if ( empty( $user_agent ) ) {
        if ( method_exists( 'GFCommon', 'set_spam_filter' ) ) {
            $reason = 'User-Agent is empty.';
            GFCommon::set_spam_filter( rgar( $form, 'id' ), 'User-Agent Check', $reason );
            GFCommon::log_debug( __METHOD__ . '(): ' . $reason );
        }
 
        return true;
    }
 
    // List of tokens that when found in the user agent will mark the entry as spam.
    $tokens = array(
        'YaBrowser',
        'Yowser',
    );
 
    foreach ( $tokens as $token ) {
        if ( stripos( $user_agent, $token ) !== false ) {
            if ( method_exists( 'GFCommon', 'set_spam_filter' ) ) {
                $reason = sprintf( 'User-Agent "%s" contains "%s".', $user_agent, $token );
                GFCommon::set_spam_filter( rgar( $form, 'id' ), 'User-Agent Check', $reason );
                GFCommon::log_debug( __METHOD__ . '(): ' . $reason );
            }
 
            return true;
        }
    }
 
    return false;
}

/**
 * Determines if the user has permission to save the information from the submenu
 * page.
 *
 * @since    1.0.0
 * @access   private
 *
 * @param    string    $action   The name of the action specified on the submenu page
 * @param    string    $nonce    The nonce specified on the submenu page
 *
 * @return   bool                True if the user has permission to save; false, otherwise.
 */
    private function ee_user_can_save( $action, $nonce ) {
        $is_nonce_set   = isset( $_POST[ $nonce ] );
        $is_vEE_Form_Filteralid_nonce = false;
        if ( $is_nonce_set ) {
            $is_valid_nonce = wp_verify_nonce( $_POST[ $nonce ], $action );
        }
        return ( $is_nonce_set && $is_valid_nonce );
    }
}
new EE_Form_Filter();