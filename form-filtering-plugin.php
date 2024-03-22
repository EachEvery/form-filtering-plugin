<?php
/*
 * Plugin Name: Form Filtering Plugin
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

 Class EE_Form_Filter {

    private $defaultErrorMessages;
    // Sets default array for first time when activating the plugin and there is no option already
    private $personal = ["126.com", "163.com", "21cn.com", "alice.it", "aliyun.com", "aol.com", "aol.it", "arnet.com.ar", "att.net", "bell.net", "bellsouth.net", "bk.ru", "blueyonder.co.uk", "bol.com.br", "bt.com", "btinternet.com", "charter.net", "comcast.net", "cox.net", "daum.net", "earthlink.net", "email.com", "email.it", "facebook.com", "fastmail.fm", "fibertel.com.ar", "foxmail.com", "free.fr", "games.com", "globo.com", "globomail.com", "gmail.com", "gmx.com", "gmx.de", "gmx.fr", "gmx.net", "googlemail.com", "hanmail.net", "hotmail.be", "hotmail.ca", "hotmail.co.uk", "hotmail.com", "hotmail.com.ar", "hotmail.com.br", "hotmail.com.mx", "hotmail.de", "hotmail.es", "hotmail.fr", "hotmail.it", "hush.com", "hushmail.com", "icloud.com", "ig.com.br", "iname.com", "inbox.com", "inbox.ru", "juno.com", "keemail.me", "laposte.net", "lavabit.com", "libero.it", "list.ru", "live.be", "live.co.uk", "live.com", "live.com.ar", "live.com.mx", "live.de", "live.fr", "live.it", "love.com", "mac.com", "mail.com", "mail.ru", "me.com", "msn.com", "nate.com", "naver.com", "neuf.fr", "ntlworld.com", "oi.com.br", "online.de", "orange.fr", "orange.net", "outlook.com", "outlook.com.br", "pobox.com", "poste.it", "prodigy.net.mx", "protonmail.ch", "protonmail.com", "qq.com", "r7.com", "rambler.ru", "rocketmail.com", "rogers.com", "safe-mail.net", "sbcglobal.net", "sfr.fr", "shaw.ca", "sina.cn", "sina.com", "sky.com", "skynet.be", "speedy.com.ar", "sympatico.ca", "t-online.de", "talktalk.co.uk", "telenet.be", "teletu.it", "terra.com.br", "tin.it", "tiscali.co.uk", "tiscali.it", "tuta.io", "tutamail.com", "tutanota.com", "tutanota.de", "tvcablenet.be", "uol.com.br", "verizon.net", "virgilio.it", "virgin.net", "virginmedia.com", "voo.be", "wanadoo.fr", "web.de", "wow.com", "ya.ru", "yahoo.ca", "yahoo.co.id", "yahoo.co.in", "yahoo.co.jp", "yahoo.co.kr", "yahoo.co.uk", "yahoo.com", "yahoo.com.ar", "yahoo.com.br", "yahoo.com.mx", "yahoo.com.ph", "yahoo.com.sg", "yahoo.de", "yahoo.fr", "yahoo.it", "yandex.by", "yandex.com", "yandex.com", "yandex.kz", "yandex.ru", "yandex.ua", "yeah.net", "ygm.com", "ymail.com", "zipmail.com.br", "zoho.com"];
    private $competitors = null;
    private $nbResults = [];
    private $nb_api = null;
    private $page = 'form-filtering-plugin';
    private $useNB = false;

    public function __construct() {
        //sets defines
        define( 'EE_FILTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

        // Include our updater file
        include_once( plugin_dir_path( __FILE__ ) . '/updater/updater.php');
        $updater = new EE_Updater( __FILE__ ); // instantiate our class
        $updater->set_username( 'EachEvery' ); // set username
        $updater->set_repository( 'form-filtering-plugin' ); // set repo
        $updater->initialize(); // initialize the updater

        /**
         * Setup autoloading for NeverBounce
         */
        require_once __DIR__ . '/neverbounce/bootstrap.php';

        /**
         * Checks for neverbounce API. Does try catch and gives back specific erros if we are logging in gravity forms.
         */
        if(get_option('ee-neverbounce-api')){
            $this->nb_api = get_option('ee-neverbounce-api');
            \NeverBounce\Auth::setApiKey($this->nb_api);
            // Sets use NB to true. If this isn't set to true we will just skip code below.
            $this->useNB = true;
            try {
                // This just an easy way to check if NB will work and return errors
                $info = \NeverBounce\Account::info();
            } catch (\NeverBounce\Errors\AuthException $e) {
                // The API credentials used are bad, have you reset them recently?
                GFCommon::log_debug( __METHOD__ . '(): AuthException  '. print_r($e, true));
                $this->useNB = false;
            } catch (\NeverBounce\Errors\BadReferrerException $e) {
                // The script is being used from an unauthorized source, you may need to
                // adjust your app's settings to allow it to be used from here
                GFCommon::log_debug( __METHOD__ . '(): BadReferrerException  '. print_r($e, true));
                $this->useNB = false;
            } catch (\NeverBounce\Errors\ThrottleException $e) {
                // Too many requests in a short amount of time, try again shortly or adjust
                // your rate limit settings for this application in the dashboard
                GFCommon::log_debug( __METHOD__ . '(): ThrottleException  '. print_r($e, true));
                $this->useNB = false;
            } catch (\NeverBounce\Errors\HttpClientException $e) {
                // An error occurred processing the request, something may be wrong with
                // the Curl PHP extension or your network
                GFCommon::log_debug( __METHOD__ . '(): HttpClientException  '. print_r($e, true));
                $this->useNB = false;
            } catch (\NeverBounce\Errors\GeneralException $e) {
                // A non recoverable API error occurred check the message for details
                GFCommon::log_debug( __METHOD__ . '(): GeneralException  '. print_r($e, true));
                $this->useNB = false;
            } catch (Exception $e) {
                // An error occurred unrelated to the API
                GFCommon::log_debug( __METHOD__ . '(): unrelated to api  '. print_r($e, true));
                $this->useNB = false;
            }
        }

        $this->personal = get_option('ee-ff-personal-domains') ? get_option('ee-ff-personal-domains')  :  $this->personal;
        
        $this->competitors = $this->competitors == null ? get_option('ee-ff-competitors-domains') : false;
        $this->nbResults = $this->nbResults == null  ? get_option('ee-ff-nb-results') : false;

        // sets default error messages
        $this->defaultErrorMessages = [
            'not-email' => 'An Email Address needs an @',
            'disposable' => 'This is a disposable email address',
            'first-last' => 'The first name and last name must be different',
            'name-number' => 'A name cannot contain a number',
            'one-char' => 'A name cannot be just one character',
            'two-char' => 'A name cannot be 2 characters that are the same letter or both vowels',
            'profanity' => 'Profanity is not allowed',
            'personal' => 'Please use a business email.',
            'invalid' => 'This is not a valid email address',
            'server' => 'The server cannot be reached'
        ];

        // activation hook
		register_activation_hook( __FILE__, array( &$this, 'set_cron' ) );
		// deactivation hook
		// register_deactivation_hook( __FILE__, array( &$this, 'deactivation_hook' ) );
        // Cron to get list.
        add_action( 'ee_get_list',  array( &$this, 'get_disposable_emails') );

        
        //creates menu page
        add_action( 'admin_menu',  array( &$this, 'ee_form_filter_menu') );

        // enqueue script
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

        // Gravity Forms validation
        add_filter( 'gform_field_validation', array( &$this, 'form_filter_validation'), 10, 4 );
        add_filter( 'gform_entry_is_spam', array( &$this, 'filter_gform_entry_is_spam_user_agent'), 11, 3 );
        add_filter( 'gform_entry_is_spam', array( &$this,'filter_gform_competitors'), 12, 3 );
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
            'errors' => stripslashes(json_encode(get_option('ee-ff-error')))
        ));
    }

    public function set_cron() {
        // Set default error message array
        if(get_option('ee-ff-error') == null){
            update_option('ee-ff-error', $this->defaultErrorMessages);
        }
        if(get_option('ee-ff-personal-domains') == null){
            update_option('ee-ff-personal-domains', $this->personal);
        }
        // Schedule Cron
		if ( ! wp_next_scheduled( 'ee_get_list' ) ) {
            wp_schedule_event( time(), 'daily', 'ee_get_list' );
        }
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
        add_menu_page( 'Form Filtering', 'Form Filtering', 'manage_options', $this->page, array(&$this, 'ee_filtering_admin_page'), 'dashicons-forms', 6  );
    }

    /**
     * admin page to show competitors list, personal list, and error messages
     */
    public function ee_filtering_admin_page(){

        //Get the active tab from the $_GET param
        $default_tab = null;
        $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

        $this->ee_form_filter_save_options();
        $competitors = get_option('ee-ff-competitors-domains') ?: '';
        $nbResults = get_option('ee-ff-nb-results') ?: '';
        // from https://help.gong.io/hc/en-us/articles/13363982628749-Public-email-domains-exclusion-list
        $personal = get_option('ee-ff-personal-domains') ?: '';
        $error = get_option('ee-ff-error');
        $neverbounce = get_option('ee-neverbounce-api');
        // $neverbouncePriceLimit = get_option('ee-neverbounce-price-limit');
        ?>
        <div class="wrap">
            <!-- Print the page title -->
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <!-- Here are our tabs -->
            <nav class="nav-tab-wrapper">
                <a href="?page=<?php echo $this->page; ?>" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Settings</a>
                <a href="?page=<?php echo $this->page; ?>&tab=error-message" class="nav-tab <?php if($tab==='error-message'):?>nav-tab-active<?php endif; ?>">Error Message</a>
                <a href="?page=<?php echo $this->page; ?>&tab=neverbounce" class="nav-tab <?php if($tab==='neverbounce'):?>nav-tab-active<?php endif; ?>">NeverBounce</a>
                <!-- <a href="?page=<?php echo $this->page; ?>&tab=ee-updater" class="nav-tab <?php if($tab==='ee-updater'):?>nav-tab-active<?php endif; ?>">Each+Every Updater</a> -->
            </nav>

            <div class="tab-content">
                <form class="main-form-section" action="" method="post">
                <?php switch($tab) :
                    case 'neverbounce': 
                        ?>
                        <h3>NeverBounce API Key</h3>
                        <input style="width: 100%;" type="password" name="ee-neverbounce-api" id="ee-neverbounce-api" value="<?php echo $neverbounce; ?>" />
                        <h3>NeverBounce Blocks</h3>
                        <textarea name="ee-ff-nb-results" id="ee-ff-nb-results" rows="15" columns="50"><?php echo $this->convert_array_to_textarea_data($nbResults); ?></textarea>
                        <?php
                        break;
                    case 'error-message':
                        ?>
                        <h2>Form Filtering Options</h2>
                            <div style="display: flex; gap: 20px;">
                                <div style="width: 100%">
                                <h3>Error Messages</h3>
                                <div style="display: flex; flex-direction:column; gap: 10px">
                                    <h4>Not an Email Message</h4>
                                    <small>This error shows if the user doesn't add in an email address(If it doesn't have an @ symbol)</small>
                                    <input required style="width: 100%;" type="text" name="ee-error-not-email" id="ee-error-not-email" value="<?php echo $error['not-email']; ?>" />
                                    <hr/>
                                    <h4>Disposable Email Message</h4>
                                    <small>This error shows if a user is using a temporary or disposable email address</small>
                                    <input required style="width: 100%;" type="text" name="ee-error-disposable" id="ee-error-disposable" value="<?php echo $error['disposable']; ?>" />
                                    <hr/>
                                    <h4>First and Last names are the same Message</h4>
                                    <small>This error shows if the user has the first name and last name as the same name or as "first" and "last"</small>
                                    <input required style="width: 100%;" type="text" name="ee-error-first-last" id="ee-error-first-last" value="<?php echo stripslashes(htmlspecialchars($error['first-last'])); ?>" />
                                    <hr/>
                                    <h4>Names containing numbers Message</h4>
                                    <small>This error shows if the name has a number in it</small>
                                    <input required style="width: 100%;" type="text" name="ee-error-name-number" id="ee-error-name-number" value="<?php echo stripslashes(htmlspecialchars($error['name-number'])); ?>" />
                                    <hr/>
                                    <h4>One Character Message</h4>
                                    <small>This error shows if the user tries to have a first name as only one character</small>
                                    <input required style="width: 100%;" type="text" name="ee-error-one-char" id="ee-error-one-char" value="<?php echo htmlspecialchars($error['one-char']); ?>" />
                                    <hr/>
                                    <h4>Two Character Message</h4>
                                    <small>This error shows if the user only has 2 characters as a first name that are both vowels or both the same letter</small>
                                    <input required style="width: 100%;" type="text" name="ee-error-two-char" id="ee-error-two-char" value="<?php echo htmlspecialchars($error['two-char']); ?>" />
                                    <hr/>
                                    <h4>Profanity Error Message</h4>
                                    <small>This error shows if the user adds profanity as a name</small>
                                    <input required style="width: 100%;" type="text" name="ee-error-profanity" id="ee-error-profanity" value="<?php echo htmlspecialchars($error['profanity']); ?>" />
                                    <hr/>
                                    <h4>Personal Email Address Message</h4>
                                    <small>This error shows if the user tries to use a personal email address (IE: @gmail.com)</small>
                                    <input required style="width: 100%;" type="text" name="ee-error-personal" id="ee-error-personal" value="<?php echo htmlspecialchars($error['personal']); ?>" />
                                    <hr/>
                                    <h4>Invalid Email Address Message</h4>
                                    <small>This error shows if the email address provided is determined to be an invalid email address.</small>
                                    <input required style="width: 100%;" type="text" name="ee-error-invalid" id="ee-error-invalid" value="<?php echo htmlspecialchars($error['invalid']); ?>" />
                                    <hr/>
                                    <h4>Server Cannot be Reached Message</h4>
                                    <small>This error shows if the email address server cannot be reached.</small>
                                    <input required style="width: 100%;" type="text" name="ee-error-server" id="ee-error-server" value="<?php echo htmlspecialchars($error['server']); ?>" />
                                    <hr/>
                                </div>
                                </div>
                            </div>
                        <?php
                        break;
                    case 'ee-updater':
                        ?>
                        <h2>Each+Every Updater</h2>
                            <div style="display: flex; gap: 20px;">
                                <div style="width: 100%">
                                <h3>Error Messages</h3>
                                <div style="display: flex; flex-direction:column; gap: 10px">
                                    <h4>Not an Email Message</h4>
                                    <input required style="width: 100%;" type="text" name="ee-error-not-email" id="ee-error-not-email" value="<?php echo $error['not-email']; ?>" />
                                    <h4>Disposable Email Message</h4>
                                    <input required style="width: 100%;" type="text" name="ee-error-disposable" id="ee-error-disposable" value="<?php echo $error['disposable']; ?>" />
                                    <h4>First and Last names are the same Message</h4>
                                    <input required style="width: 100%;" type="text" name="ee-error-first-last" id="ee-error-first-last" value="<?php echo stripslashes(htmlspecialchars($error['first-last'])); ?>" />
                                    <h4>Names containing numbers Message</h4>
                                    <input required style="width: 100%;" type="text" name="ee-error-name-number" id="ee-error-name-number" value="<?php echo stripslashes(htmlspecialchars($error['name-number'])); ?>" />
                                    <h4>One Character Message</h4>
                                    <input required style="width: 100%;" type="text" name="ee-error-one-char" id="ee-error-one-char" value="<?php echo htmlspecialchars($error['one-char']); ?>" />
                                    <h4>Two Character Message</h4>
                                    <input required style="width: 100%;" type="text" name="ee-error-two-char" id="ee-error-two-char" value="<?php echo htmlspecialchars($error['two-char']); ?>" />
                                   
                                    
                                </div>
                                </div>
                            </div>
                        <?php
                        break;
                    default:
                        ?>
                        <h2>Form Filtering Options</h2>
                            <div >
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

                            </div>
                            <?php
                        break;
                    endswitch; ?>
                    <div class="submit-sticky">
                        <?php submit_button( 'Save' ); ?>
                    </div>
                    <?php wp_nonce_field( 'ee-filtering-admin-save', 'ee-filtering-admin-save-nonce' ); ?>
                </form>
            </div>
        </div>
        <style>
            #wpbody-content {
                position: relative;
            }
            textarea {
                width: 100%;
                min-height: 250px;
            }
            .submit-sticky {
                position: sticky;
                bottom: 0;
                left: 0;
                width: 100%;
                background-color: #f0f0f1;
                padding: 5px 0;
            }
            .main-form-section {
                margin-bottom: 50px;
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
        // If the user doesn't have permission to save, then display an error message
        if ( ! $this->ee_user_can_save( $action, $nonce ) ) {
            return;
        }
        if ( isset( $_POST['ee-ff-personal-domains'] ) ) {
            $text = sanitize_textarea_field($_POST['ee-ff-personal-domains']);
            $domains = preg_split('/\r\n|[\r\n]/', $text);
            update_option('ee-ff-personal-domains', json_encode($domains));
		} 
        if ( isset( $_POST['ee-ff-nb-results'] ) ) {
            $text = sanitize_textarea_field($_POST['ee-ff-nb-results']);
            $domains = preg_split('/\r\n|[\r\n]/', $text);
            update_option('ee-ff-nb-results', json_encode($domains));
		} 

        if ( isset( $_POST['ee-ff-competitors-domains'] ) ) {
            $text = sanitize_textarea_field($_POST['ee-ff-competitors-domains']);
            $domains = preg_split('/\r\n|[\r\n]/', $text);
            update_option('ee-ff-competitors-domains', json_encode($domains));
		}
        if ( isset( $_POST['ee-neverbounce-api'] ) ) {
            $text = sanitize_text_field($_POST['ee-neverbounce-api']);
            update_option('ee-neverbounce-api', $text);
		}
        // if ( isset( $_POST['ee-neverbounce-price-limit'] ) ) {
        //     $text = sanitize_text_field($_POST['ee-neverbounce-price-limit']);
        //     update_option('ee-neverbounce-price-limit', $text);
		// } 
      
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
 * add nb emails to a list to prevent them from running the same email again
 */
public function nb_filteredList($email) {
    if($this->nbResults == false) {
        $this->nbResults = [];
    }
    // GFCommon::log_debug( __METHOD__ . '(): $info => ' . print_r($this->nbResults) );
    $this->nbResults[] = $email;
    update_option('ee-ff-nb-results', json_encode($this->nbResults));
}

/**
 * 
 */
public function form_filter_validation( $result, $value, $form, $field ) {
    
    $field_types_to_check = array(
        'text',
        'textarea',
    );
 
    $text_to_check = array();
    if(in_array($field->type, $field_types_to_check)){
 
        $text_to_check[] = $value;
 
        if ( empty( $text_to_check ) ) {
            return false;
        }
 
        $args = array(
            'text' => urlencode( implode( "\r\n", $text_to_check ) ),
        );
    
        $response = wp_remote_get( add_query_arg( $args, 'https://www.purgomalum.com/service/containsprofanity' ) );
    
        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            // GFCommon::log_debug( __METHOD__ . '(): profanity response => ' . print_r( $response, true ) );
    
            return false;
        }
        // set the form validation to false
        if( wp_remote_retrieve_body( $response ) === 'true' ) {
            $error = get_option('ee-ff-error');
            $result['is_valid'] = false;
            $result['message'] = $error['profanity'];
        }
    }
    if($field->type == 'email'){
        $value = is_array($value) ? $value : array($value);

        $credits = false; 
        // Checks if NB is to be used.
        if($this->useNB){
            // checks if there are any credits left
            $info = \NeverBounce\Account::info();
            if($info->credits_info['paid_credits_remaining'] > 0) {
                $credits = true;
            }
        }
        
        foreach($value as $email) {
            // Exit out if no email or email is blank.
            if(!isset($email) || $email == '' ) continue;
            // remove extra spaces
            $email = trim($email);
            $emailArray = explode("@", $email);
            // Check personal if domain is on personal email list
            if($this->personal !== null && $this->personal !== false){
                if($this->isJson($this->personal)) {
                    $this->personal = json_decode($this->personal);
                }
                if(in_array($emailArray[1], $this->personal) ){
                    $error = get_option('ee-ff-error');
                    $result['is_valid'] = false;
                    $result['message'] = $error['personal'];
                }
            }
            // If have credits. and nb is to be used AND not already invalid because of personal use NB
            if($credits && $this->useNB && $result['is_valid'] !== false){
                if($this->isJson($this->nbResults)) {
                    $this->nbResults = json_decode($this->nbResults);
                }
                // Checks to see if this email address is stored as an email that is already "blocked" by NB
                if(is_array($this->nbResults) && in_array($email, $this->nbResults)){
                    $error = get_option('ee-ff-error');
                    $result['is_valid'] = false;
                    $result['message'] = $error['invalid'];
                } 
                else {

                    $verification = \NeverBounce\Single::check($email, true, true);
                    if($verification->result_integer == 1) {
                        $error = get_option('ee-ff-error');
                        $result['is_valid'] = false;
                        $result['message'] = $error['invalid'];
                        $this->nb_filteredList($email); 
                    }
                    if($verification->result_integer == 2) {
                        $error = get_option('ee-ff-error');
                        $result['is_valid'] = false;
                        $result['message'] = $error['disposable'];
                        $this->nb_filteredList($email); 
                    }
                    if($verification->result_integer == 4) {
                        $error = get_option('ee-ff-error');
                        $result['is_valid'] = false;
                        $result['message'] = $error['server'];
                        $this->nb_filteredList($email); 
                    }
                    // Get verified email
                    // GFCommon::log_debug( __METHOD__ . '(): Email Verified: ' . $verification->email );
                    // GFCommon::log_debug( __METHOD__ . '(): Numeric Code: ' . $verification->result_integer);
                    // GFCommon::log_debug( __METHOD__ . '(): Text Code: ' . $verification->result);
                    // GFCommon::log_debug( __METHOD__ . '(): Has DNS: ' . (string) $verification->hasFlag('has_dns'));
                    // GFCommon::log_debug( __METHOD__ . '(): Is free mail: ' . (string) $verification->hasFlag('free_email_host'));
                    // GFCommon::log_debug( __METHOD__ . '(): Suggested Correction: ' . $verification->suggested_correction);
                    // GFCommon::log_debug( __METHOD__ . '(): Is unknown: ' . (string) $verification->is('unknown'));
                    // GFCommon::log_debug( __METHOD__ . '(): Isn\'t valid or catchall: ' . (string) $verification->not(['valid', 'catchall']));
                    $credits = ($verification->credits_info->paid_credits_used
                        + $verification->credits_info->free_credits_used);
                    GFCommon::log_debug( __METHOD__ . '(): Credits used: ' . $credits);
                }
            }
            
        }
        
    }
    
    //Assign modified $form object back to the validation result
    $result['form'] = $form;
    return $result;
      
}

/**
 * Sends to spam by user agent
 */
 public function filter_gform_entry_is_spam_user_agent( $is_spam, $form, $entry ) {
    if ( $is_spam ) {
        return $is_spam;
    }
 
    $user_agent = rgar( $entry, 'user_agent' );
 
    if ( empty( $user_agent ) ) {
        if ( method_exists( 'GFCommon', 'set_spam_filter' ) ) {
            $reason = 'User-Agent is empty.';
            // GFCommon::set_spam_filter( rgar( $form, 'id' ), 'User-Agent Check', $reason );
            // GFCommon::log_debug( __METHOD__ . '(): ' . $reason );
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
                // GFCommon::set_spam_filter( rgar( $form, 'id' ), 'User-Agent Check', $reason );
                // GFCommon::log_debug( __METHOD__ . '(): ' . $reason );
            }
 
            return true;
        }
    }
 
    return false;
}

/**
 * Sends to spam if on the competitors list.
 */
public function filter_gform_competitors( $is_spam, $form, $entry ) {
    if ( $is_spam ) {
        return $is_spam;
    }
    // GFCommon::log_debug( __METHOD__ . '(): ' . print_r($entry) );
 
    foreach($entry as $el) {
        if(empty($el)) continue;
        if($this->competitors !== null && $this->competitors !== false){
            if($this->isJson($this->competitors)) {
                $this->competitors = json_decode($this->competitors);
            }
            if(str_contains($el, '@')){
                $emailArray = explode("@", $el);
                if(in_array($emailArray[1], $this->competitors) ){
                    $is_spam = true;
                    break;
                }
            }
        }
    }
 
    return $is_spam;
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