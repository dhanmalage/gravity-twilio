<?php

GFForms::include_addon_framework();

/**
 * Load twilio
 */
//require 'vendor/autoload.php';

use Twilio\Rest\Client;

class GFRentBeetleAddOn extends GFAddOn {

	protected $_version = GF_RENT_BEETLE_ADDON_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'rentbeetleaddon';
	protected $_path = 'rentbeetleaddon/rentbeetleaddon.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms Rent Beetle Add-On';
	protected $_short_title = 'Send SMS';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFRentBeetleAddOn
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFRentBeetleAddOn();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
        add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'register_sms_meta_box' ), 10, 3 );
	}


	// # SCRIPTS & STYLES -----------------------------------------------------------------------------------------------

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
	public function scripts() {
		$scripts = array(
			array(
				'handle'  => 'my_script_js',
				'src'     => $this->get_base_url() . '/js/my_script.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery' ),
				'strings' => array(
					'first'  => esc_html__( 'First Choice', 'simpleaddon' ),
					'second' => esc_html__( 'Second Choice', 'simpleaddon' ),
					'third'  => esc_html__( 'Third Choice', 'simpleaddon' )
				),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'simpleaddon'
					)
				)
			),

		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Return the stylesheets which should be enqueued.
	 *
	 * @return array
	 */
	public function styles() {
		$styles = array(
			array(
				'handle'  => 'my_styles_css',
				'src'     => $this->get_base_url() . '/css/my_styles.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'field_types' => array( 'poll' ) )
				)
			)
		);

		return array_merge( parent::styles(), $styles );
	}


	// # FRONTEND FUNCTIONS --------------------------------------------------------------------------------------------


	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------


	// # SIMPLE CONDITION EXAMPLE --------------------------------------------------------------------------------------



	// # HELPERS -------------------------------------------------------------------------------------------------------



	// # META BOXES

    /**
     * Add the meta box to the entry detail page.
     *
     * @param array $meta_boxes The properties for the meta boxes.
     * @param array $entry The entry currently being viewed/edited.
     * @param array $form The form object used to process the current entry.
     *
     * @return array
     */
    public function register_sms_meta_box( $meta_boxes, $entry, $form ) {
        // If the form has an active feed belonging to this add-on and the API can be initialized, add the meta box.
        //if ( $this->get_active_feeds( $form['id'] ) && $this->initialize_api() ) {
        if($form['id'] == 3) {
            $meta_boxes[ $this->_slug ] = array(
                'title'    => $this->get_short_title(),
                'callback' => array( $this, 'add_book_showing_sms_meta_box' ),
                'context'  => 'normal',
            );
        }
        if($form['id'] == 1) {
            $meta_boxes[ $this->_slug ] = array(
                'title'    => $this->get_short_title(),
                'callback' => array( $this, 'add_application_sms_meta_box' ),
                'context'  => 'normal',
            );
        }
        //}

        return $meta_boxes;
    }

    /**
     * The callback used to echo the content to the meta box.
     *
     * @param array $args An array containing the form and entry objects.
     */
    public function add_book_showing_sms_meta_box( $args ) {

        $form  = $args['form'];
        $entry = $args['entry'];

        $html   = '';
        $action = $this->_slug . '_process_sms';

        // Retrieve the sms text from the current entry, if available.
        $sms_text = rgar( $entry, 'rentbeetleaddon_sms_text' );

        // Retrieve phone number
        $phone_number = rgar( $entry, '3' );
        $property_address = rgar( $entry, '9' );
        $listing_id = rgar( $entry, '5' );

        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        try {
            $usPhoneNumber = $phoneUtil->parse($phone_number, "US");
        } catch (\libphonenumber\NumberParseException $e) {
            var_dump($e);
        }

        //$form_id = rgar( $entry, 'form_id' );

        if ( empty( $sms_text ) && rgpost( 'action' ) == $action && rgpost('text_message', true) != '' ) {

            $sms_text = rgpost('text_message', true);

            check_admin_referer( 'gforms_save_entry', 'gforms_save_entry' );

            // Because the entry doesn't already have a contact id and the 'Process Feeds' button was clicked process the feeds.
            $entry = $this->sms_process_text( $usPhoneNumber, $sms_text );

            // Retrieve the sms from the updated entry.
            $sms_text = rgar( $entry, 'rentbeetleaddon_text_message' );

            $html .= esc_html__( 'SMS Sent!', 'rentbeetleaddon' ) . '</br></br>';
            $html .= esc_html__( $sms_text, 'rentbeetleaddon' ) . '</br></br>';
        }

        if ( empty( $sms_text ) ) {

            $application_url = 'https://rentbeetle.io/rental-application/?property=' .$listing_id . '&address=' . str_replace(" ", "-", $property_address);

            // Add the 'SMS Text' textarea.
            $html .= sprintf( '<textarea value="%s" class="textarea" name="text_message" id="smsText" rows="3">Here is your application for housing at ' . $property_address . '. Fingers crossed!  ' . $application_url . '</textarea>', esc_attr__( 'Send', 'rentbeetleaddon' ) );

            $html .= esc_html__( '', 'rentbeetleaddon' ) . '</br></br>';

            // Add the 'Process Feeds' button.
            $html .= sprintf( '<input type="submit" value="%s" class="button" onclick="jQuery(\'#action\').val(\'%s\');" />', esc_attr__( 'Send', 'rentbeetleaddon' ), $action );

        } else {

            // Display the contact ID.
            $html .= esc_html__( 'Contact ID', 'rentbeetleaddon' ) . ': ' . $sms_text;

            // You could include a link to the contact profile on the third-party site.
        }

        echo $html;
    }

    function add_application_sms_meta_box( $args )
    {
        $form  = $args['form'];
        $entry = $args['entry'];

        $html   = '';
        $action = $this->_slug . '_process_sms';

        $tenant_phone = rgar( $entry, '9' );

        $renters_phone_arr = array();

        /*
        foreach (rgar( $entry, '37' ) as $renter) {
            $renters_phone_arr[] = $renter['Phone'];
        }
        */

        // Retrieve the sms text from the current entry, if available.
        $sms_text = rgar( $entry, 'rentbeetleaddon_sms_text' );


        if ( empty( $sms_text ) && rgpost( 'action' ) == $action ) {

            //check_admin_referer( 'gforms_save_entry', 'gforms_save_entry' );

            //var_dump(rgpost('text_message[]', true)); die;

            foreach (rgpost('text_message[]', true) as $value => $n) {
                var_dump($n);
            }

            /*
            $j = 1;
            foreach (unserialize(rgar( $entry, '37' )) as $renter) {
                $new_text = rgpost('text_message_' . $j, true);
                if($new_text != '' && isset($new_text)) {
                    // Because the entry doesn't already have a contact id and the 'Process Feeds' button was clicked process the feeds.
                    //$entry = $this->sms_process_text( $renter['Phone'], $new_text );
                    var_dump($new_text);
                    var_dump($renter['Phone']);
                }
                $j++;
            }
            */

            // Retrieve the sms from the updated entry.
            $sms_text = rgar( $entry, 'rentbeetleaddon_text_message' );

            $html .= esc_html__( 'SMS Sent!', 'rentbeetleaddon' ) . '</br></br>';
            $html .= esc_html__( $sms_text, 'rentbeetleaddon' ) . '</br></br>';
        }


        if ( empty( $sms_text ) ) {
            $property_url = "https://api.bridgedataoutput.com/api/v2/OData/riar/Property('".rgar( $entry, '36' )."')?access_token=xxxx";
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $property_url);
            $api_response =  $response->getBody();
            $decoded_response = json_decode($api_response,true);

            //Price calculation
            $rent_count = count(unserialize(rgar( $entry, '37' )));
            $rent_count_total = $rent_count + 1;
            $deposit = $decoded_response['ListPrice'] / $rent_count_total;

            $lease_url = home_url().'/payment-page/?entry='.rgar( $entry, 'id' ).'&address='.rgar( $entry, '35' ).'&deposit='.$deposit.'&property='.rgar( $entry, '36' );

            //$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

            $html .= '<strong>' . rgar( $entry, '8' ) . ' @ ' . rgar( $entry, '9' ) . '</strong></br>';
            // Add the 'SMS Text' textarea.
            $html .= '<textarea class="textarea" name="text_message[]" id="smsText" rows="3">Great news! Your application for housing at ' . rgar( $entry, '35' ) . ' has been accepted, please view your lease at ' . $lease_url . '</textarea>';

            $html .= esc_html__( '', 'rentbeetleaddon' ) . '</br></br>';

            $i = 1;
            foreach (unserialize(rgar( $entry, '37' )) as $renter) {

                /*
                try {
                    $renterPhoneNumber = $phoneUtil->parse($renter['Phone'], "US");
                } catch (\libphonenumber\NumberParseException $e) {
                    //var_dump($e);
                }
                */

                //$renters_phone_arr[] = $renter['Phone'];
                $html .= '<strong>' . $renter['Name'] . ' @ ' . $renter['Phone'] . '</strong></br>';
                // Add the 'SMS Text' textarea.
                $html .= '<textarea class="textarea" name="text_message[]" id="smsText" rows="3">Great news! Your application for housing at ' . rgar( $entry, '35' ) . ' has been accepted, please view your lease at ' . $lease_url . '</textarea>';

                $html .= esc_html__( '', 'rentbeetleaddon' ) . '</br></br>';
            }

            // Add the 'Process Feeds' button.
            $html .= sprintf( '<input type="submit" value="%s" class="button" onclick="jQuery(\'#action\').val(\'%s\');" />', esc_attr__( 'Send', 'rentbeetleaddon' ), $action );

        } else {

            // Display the contact ID.
            $html .= esc_html__( 'Contact ID', 'rentbeetleaddon' ) . ': ' . $sms_text;

            // You could include a link to the contact profile on the third-party site.
        }

        echo $html;
    }

    function sms_process_text($phone_number, $sms_text)
    {
        $sid = 'xxxx';
        $token = 'xxxx';
        $client = new Client($sid, $token);

        try {
            // Send user text
            $send_msg = $client->messages->create(
                $phone_number,
                [
                    'from' => '+14012715633',
                    'body' => $sms_text
                ]
            );
            //$this->updateText($recipient->id, $send_msg->status, $send_msg->sid, $send_msg->errorCode, $send_msg->errorMessage);
            return true;
        } catch (Exception $e) {
            //$this->updateText($recipient->id, "failed", "", "", "");
            return false;
        }

    }

}
