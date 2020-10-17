<?php
/**
 * Payfast ITN Listener.
 */

class Payfast_ITN_Listener {

	/**
	 * Host Url.
	 *
	 * @var string $host Host.
	 */
	private $host;

	/**
	 * Host Url.
	 *
	 * @var string $pass_phrase Passphrase.
	 */
	protected $pass_phrase;

	/**
	 * Caught Errors.
	 */
	private $errors;

	/**
	 * Constructor.
	 */
	public function _construct( $pfhost = 'sandbox.payfast.co.za', $pf_pass_phrase = null ) {
		$this->host        = $pfhost;
		$this->pass_phrase = $pf_pass_phrase;
		$this->define_contansts();
		$this->validate_signature();
		$this->validate_ip( $ip );
		$this->amounts_equal( $amount1, $amount2 );
		$this->validate_data();
		$this->log( "Errors:\n" . print_r( $this->errors, true ), true );
	}

	/**
	 * Defines API Constants.
	 */
	public function define_contansts() {
		// General Defines
		$this->define( 'PF_TIMEOUT', 30 );
		$this->define( 'PF_EPSILON', 0.01 );
		// Messages
		// Error
		$this->define( 'PF_ERR_AMOUNT_MISMATCH', 'Amount mismatch' );
		$this->define( 'PF_ERR_BAD_ACCESS', 'Bad access of page' );
		$this->define( 'PF_ERR_BAD_SOURCE_IP', 'Bad source IP address' );
		$this->define( 'PF_ERR_CONNECT_FAILED', 'Failed to connect to PayFast' );
		$this->define( 'PF_ERR_INVALID_SIGNATURE', 'Security signature mismatch' );
		$this->define( 'PF_ERR_MERCHANT_ID_MISMATCH', 'Merchant ID mismatch' );
		$this->define( 'PF_ERR_NO_SESSION', 'No saved session found for ITN transaction' );
		$this->define( 'PF_ERR_ORDER_ID_MISSING_URL', 'Order ID not present in URL' );
		$this->define( 'PF_ERR_ORDER_ID_MISMATCH', 'Order ID mismatch' );
		$this->define( 'PF_ERR_ORDER_INVALID', 'This order ID is invalid' );
		$this->define( 'PF_ERR_ORDER_NUMBER_MISMATCH', 'Order Number mismatch' );
		$this->define( 'PF_ERR_ORDER_PROCESSED', 'This order has already been processed' );
		$this->define( 'PF_ERR_PDT_FAIL', 'PDT query failed' );
		$this->define( 'PF_ERR_PDT_TOKEN_MISSING', 'PDT token not present in URL' );
		$this->define( 'PF_ERR_SESSIONID_MISMATCH', 'Session ID mismatch' );
		$this->define( 'PF_ERR_UNKNOWN', 'Unknown error occurred' );

		// General
		$this->define( 'PF_MSG_OK', 'Payment was successful' );
		$this->define( 'PF_MSG_FAILED', 'Payment has failed' );
		$this->define(
			'PF_MSG_PENDING',
			'The payment is pending. Please note, you will receive another Instant' .
			' Transaction Notification when the payment status changes to' .
			' "Completed", or "Failed"'
		);
	}

	/**
	 * Defines constant if not defined.
	 *
	 * @param string $name Constant Name.
	 * @param string $value Constant Value.
	 */
	public function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 *
	 * Log function for logging output.
	 *
	 * @param  string $msg Message to log
	 * @param  bool   $close Whether to close the log file or not
	 */
	public function log( $msg = '', $close = false ) {
		static $fh = 0;
		global $module;

		// Only log if debugging is enabled
		if ( true ) {
			if ( $close ) {
				fclose( $fh );
			} else {
				// If file doesn't exist, create it
				if ( ! $fh ) {
					$pathinfo = pathinfo( __FILE__ );
					$fh       = fopen( $pathinfo['dirname'] . '/payfast.log', 'a+' );
				}

				// If file was successfully created
				if ( $fh ) {
					$line = date( 'Y-m-d H:i:s' ) . ' : ' . $msg . "\n";

					fwrite( $fh, $line );
				}
			}
		}
	}

	/**
	 * Get Response Data.
	 */
	public function get_data() {
		// Posted variables from ITN.
		$pfdata = $_POST;

		// Strip any slashes in data.
		foreach ( $pfdata as $key => $val ) {
			$pfdata[ $key ] = stripslashes( $val );
		}

		// Return "false" if no data was received.
		if ( sizeof( $pfdata ) == 0 ) {
			return false;
		} else {
			return $pfdata;
		}
	}

	/**
	 * Payfast Param String.
	 *
	 * @param array $pfdata Posted Data.
	 * @param array $pass_phrase Pass Phrase.
	 */
	public function param_string() {
		$pfdata       = $this->get_data();
		$pass_phrase  = $this->pass_phrase;
		$param_string = '';
		foreach ( $pfdata as $key => $val ) {
			if ( $key != 'signature' ) {
				$param_string .= $key . '=' . urlencode( $val ) . '&';
			} else {
				break;
			}
		}

		$param_string = substr( $param_string, 0, -1 );

		if ( is_null( $pass_phrase ) ) {
			$temp_param_string = $param_string;
		} else {
			$temp_param_string = $param_string . '&passphrase=' . urlencode( $pass_phrase );
		}
		return $temp_param_string;
	}

	/**
	 * Checks if Valid Signature.
	 */
	public function validate_signature() {
		$pfdata       = $this->get_data();
		$param_string = $this->param_string( $pfdata, $this->pass_phrase );

		$signature = md5( $param_string );

		$result = ( $pfdata['signature'] == $signature );

		if ( ! $result ) {
			$this->errors[] = PF_ERR_INVALID_SIGNATURE;
		}
		$this->log( "Signature Validation:\n" . ($result) ? 'Valid Signature.' : 'Invalid Signature.' );
		return $result;
	}

	/**
	 * Data Validation.
	 *
	 * @param  string $proxy  Address of proxy to use or NULL if no proxy
	 */
	public function validate_data( $proxy = null ) {
		$host         = $this->host;
		$param_string = $this->param_string();

		// Use cURL (if available)
		if ( function_exists( 'curl_init' ) ) {
			// Variable initialization
			$url = $host . '/eng/query/validate';

			// Create default cURL object
			$ch = curl_init();

			// Set cURL options - Use curl_setopt for freater PHP compatibility
			// Base settings
			curl_setopt( $ch, CURLOPT_USERAGENT, PF_USER_AGENT );  // Set user agent
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );      // Return output as string rather than outputting it
			curl_setopt( $ch, CURLOPT_HEADER, false );             // Don't include header in output
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

			// Standard settings
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $param_string );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
			if ( ! empty( $proxy ) ) {
				curl_setopt( $ch, CURLOPT_PROXY, $proxy );
			}

			// Execute CURL
			$response = curl_exec( $ch );
			curl_close( $ch );

		} else { // Use fsockopen
			// Variable initialization
			$header      = '';
			$res         = '';
			$header_done = false;

			// Construct Header.
			$header  = "POST /eng/query/validate HTTP/1.0\r\n";
			$header .= 'Host: ' . $host . "\r\n";
			$header .= 'User-Agent: ' . PF_USER_AGENT . "\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= 'Content-Length: ' . strlen( $param_string ) . "\r\n\r\n";

			$this->pflog( 'Used Socket = ' . $param_string );

			// Connect to server.
			$socket = fsockopen( 'ssl://' . $host, 443, $errno, $errstr, 15 );

			// Send command to server.
			fputs( $socket, $header . $param_string );

			// Read the response from the server.
			while ( ! feof( $socket ) ) {
				$line = fgets( $socket, 1024 );

				// Check if we are finished reading the header yet.
				if ( strcmp( $line, "\r\n" ) == 0 ) {
					// read the header.
					$header_done = true;
				}
				// If header has been processed.
				elseif ( $header_done ) {
					// Read the main response.
					$response .= $line;
				}
			}
		}

		$this->log( "Response:\n" . print_r( $response, true ) );

		// Interpret Response
		$lines         = explode( "\r\n", $response );
		$verify_result = trim( $lines[0] );

		$is_valid = ( strcasecmp( $verify_result, 'VALID' ) == 0 ) ? true : false;

		if ( ! $is_valid ) {
			$this->errors[] = PF_ERR_BAD_ACCESS;
		}
		$this->log( "Data Validation:\n" . ($is_valid) ? 'Received Data is valid.' : 'Received Data is invalid.' );
		return $is_valid;
	}

	/**
	 * IP Validation.
	 *
	 * @param  string $ip Source IP address.
	 * @return bool
	 */
	public function validate_ip( $ip ) {
		// Variable initialization
		$valid_hosts = array(
			'www.payfast.co.za',
			'sandbox.payfast.co.za',
			'w1w.payfast.co.za',
			'w2w.payfast.co.za',
		);

		$valid_ips = array();

		foreach ( $valid_hosts as $pfHostname ) {
			$ips = gethostbynamel( $pfHostname );

			if ( $ips !== false ) {
				$valid_ips = array_merge( $valid_ips, $ips );
			}
		}

		// Remove duplicates
		$valid_ips = array_unique( $valid_ips );

		$is_valid_ip = in_array( $ip, $valid_ips );
		if ( ! $is_valid_ip ) {
			$this->errors[] = PF_ERR_BAD_SOURCE_IP;
		}
		$this->log( "IP Validation:\n" . ($is_valid_ip) ? 'Valid' : 'Invalid' );
		return $is_valid_ip;
	}

	/**
	 * Checks to see whether the given amounts are equal using a proper floating
	 * point comparison with an Epsilon which ensures that insignificant decimal
	 * places are ignored in the comparison.
	 *
	 * eg. 100.00 is equal to 100.0001
	 *
	 * @param  float $amount1 1st amount for comparison
	 * @param  float $amount2 2nd amount for comparison
	 */
	public function amounts_equal( $amount1, $amount2 ) {
		$is_equal = ( abs( floatval( $amount1 ) - floatval( $amount2 ) ) > PF_EPSILON ) ? false : true;
		if ( ! $is_equal ) {
			$this->error[] = PF_ERR_AMOUNT_MISMATCH;
		}
		$this->log( "IP Validation:\n" . ($is_equal) ? 'Okay' : 'Amount Mismatch' );
		return $is_equal;
	}

	public function has_error() {
		return empty( $this->errors ) ? false : $this->errors;
	}

	public function get_status() {
		$pfdata = $this->get_data();
		if ( isset( $pfdata['payment_status'] ) ) {
			return $pfdata['payment_status'];
		}
	}

}
