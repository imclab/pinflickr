<?php
/*
Plugin Name: Pinflickr
Plugin URI: https://github.com/brbcoding/pinflickr
Description: flickr api + pinterest-like layout
Version: 0.01Beta
Author: Cody G. Henshaw
Author URI: http://codyhenshaw.com
License: WTFPL
*/

// define('WP_DEBUG', true);
class PinflickrSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Pinflickr Admin', 
            'Pinflickr Settings', 
            'manage_options', 
            'pinflickr-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'pinflickr_options_name' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Pinflickr Settings</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'pinflickr_options_group' );   
                do_settings_sections( 'pinflickr-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'pinflickr_options_group', // Option group
            'pinflickr_options_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'My Custom Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'pinflickr-admin' // Page
        );  

        add_settings_field(
            'api_key', // ID
            'API Key', // Title
            array( $this, 'api_key_callback' ), // Callback
            'pinflickr-admin', // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            'app_secret', 
            'App Secret', 
            array( $this, 'app_secret_callback' ), 
            'pinflickr-admin', 
            'setting_section_id'
        );      
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {

        if( !empty( $input['app_secret'] ) )
            $input['app_secret'] = sanitize_text_field( $input['app_secret'] );

        return $input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function api_key_callback()
    {
        printf(
            '<input type="text" id="api_key" name="pinflickr_options_name[api_key]" value="%s" />',
            esc_attr( $this->options['api_key'])
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function app_secret_callback()
    {
        printf(
            '<input type="text" id="app_secret" name="pinflickr_options_name[app_secret]" value="%s" />',
            esc_attr( $this->options['app_secret'])
        );
    }
}

if( is_admin() ) {
    $my_settings_page = new PinflickrSettingsPage();
}


function pinflickr_install() {


}
// load the required scripts
function pinflickr_includes() {
	// register the scripts first, then we will enqueue them
	wp_register_script('jquery-1.10.2', plugin_dir_url(__FILE__) . 'js/jquery-1.10.2.min.js');
	wp_register_script('freetile_js', plugin_dir_url(__FILE__) . 'js/jquery.freetile.js');
	wp_register_script('fancybox_js', plugin_dir_url(__FILE__) . 'js/fancybox/source/jquery.fancybox.js');
	wp_register_script('fancybox_pack', plugin_dir_url(__FILE__) . 'js/fancybox/source/jquery.fancybox.pack.js');
	wp_register_script('pinflickr_js', plugin_dir_url(__FILE__) . 'js/pinflickr.js');
	wp_enqueue_script('jquery-1.10.2');
	wp_enqueue_script('freetile_js');
	wp_enqueue_script('pinflickr_js');
	wp_enqueue_script('fancybox_js');
	wp_enqueue_script('fancybox_pack');

	wp_register_style('pinflickr_styles', plugin_dir_url(__FILE__) . 'css/styles.css');
	wp_enqueue_style('pinflickr_styles');


	wp_register_style('fancybox_styles', plugin_dir_url(__FILE__) . 'js/fancybox/source/jquery.fancybox.css');
	wp_enqueue_style('fancybox_styles');
}

add_action('wp_enqueue_scripts', 'pinflickr_includes');


// hooks
add_action('init', 'pinflickr_init');
/***************************************************************************************/
/* SHORTCODES
****************************************************************************************/

// pass tags and user as the parms of the shortcode
function pinflickr_shortcode( $attrs ) {

  // get the options from our options page
  $opts    = get_option('pinflickr_options_name');
  $API_KEY = $opts['api_key']; // change this when in prod
  $SECRET  = $opts['app_secret']; // change this in prod too
  $user    = $attrs['user_id'];
  $tags    = $attrs['tags'];

  // store our output for error checking
  $output  = getFlickrData($SECRET, $API_KEY, $user, $tags);
  if($output) {
    echo $output;
  } else {
    echo 'No Data Available';
  }
}

add_shortcode('pinflickr', 'pinflickr_shortcode');
/***************************************************************************************/
/* FUNCTIONS
****************************************************************************************/
function pinflickr_init() {
  // do stuff
  // get my secret and API key from the database
//	$API_KEY = "ffdc6e7cef69d201a7c79bc80477a0ec"; // change this when in prod
//	$SECRET	 = "a48a5c5114b7ec99"; // change this in prod too
  //	getFlickrData($SECRET, $API_KEY);
  	// print_r('init function called');
}

function getFlickrData($SECRET, $API_KEY, $user_id, $tags) {
	// built the request url
	$url = "http://api.flickr.com/services/rest/?method=flickr.photos.search&api_key=" . $API_KEY . "&user_id=" . $user_id;
	
	// tags should be passed as a comma separated list
	if($tags != ""){
		$url .= "&tags=" . $tags;
	}
	// format it as json
	$url .= "&format=json";
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_URL, $url);
	$res = curl_exec($curl);
	curl_close($curl);
	// need to strip this invalid json callback crap
	$dat = str_replace( 'jsonFlickrApi(', '', $res );
	$dat = substr( $dat, 0, strlen( $dat ) - 1 ); //strip out last paren
	$dat = json_decode($dat, TRUE);
	// return the decoded json response
	return getFlickrUrls($dat);
}

// title is stored in $pic['title']
function getFlickrUrls($dat){
	$urls = array();
	$nums = 0;
	if($dat){
		foreach($dat['photos']['photo'] as $pic){  
			// build the url
			$photo_url	  = 'http://farm' . $pic['farm'] . '.staticflickr.com/' . $pic['server'] . 
							'/' . $pic['id'] . '_' . $pic['secret'] . ".jpg";
			// create a temporary array with the title and the photo's url
            // it should contain the title and the photo's url
			$urls[$nums]['title'] = $pic['title'];		
			$urls[$nums]['url'] = $photo_url;
			$nums++;
		}
	} else {
		echo "Flickr Was Unreachable.";
	}
	return buildPinflickrHtml($urls);
}


// build an html string to output to the page
// it should contain the title of the image as
// well as the url in image form
function buildPinflickrHtml($urls) {
	$html = "<div id='container'>";
	if($urls){
		foreach($urls as $url) {
			// t_html for temporary string builder
			// set to "" after each iteration
			$t_html = "";
			$t_html .= '<div class="item subtle rotate"><a class="fancybox" href="' . 
				$url['url']   .'"><img class="pin" src="' .
				$url['url']   . '" alt="' .
				$url['title'] .'" title="' .
				$url['title'] .'"></a><br /><span class="image-title">' .
				$url['title'] . '...</span></div>';
			$html .= $t_html;
		}

		// close the #container
		$html .= "</div>";
		return $html;		
	}

}

?>