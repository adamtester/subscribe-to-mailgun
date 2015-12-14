<?php

require 'vendor/autoload.php';
use Mailgun\Mailgun;

/**
 *
 * Send a confirmation email using Mailgun
 *
 * @author Adam Tester <adam.tester@vevvu.co.uk>
 *
 */
class App {

	/**
	 *
	 * Set default variables
	 *
	 */
	private $publicKey;
	private $privateKey;
	private $domain;
	private $mailingList;
	private $recipientAddress;

	/**
	 *
	 * Set the order of functions
	 *
	 */
	public function boot()
	{
		$this->setCORS();
		$this->setConfig();
		$this->subscribe();

		echo 'OK';
	}

	/**
	 *
	 * Allow POST from anywhere with CORS
	 *
	 */
	private function setCORS() {

		// Allow from any origin
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
			header('Access-Control-Allow-Credentials: true');
			header('Access-Control-Max-Age: 86400');    // cache for 1 day
		}

		// Access-Control headers are received during OPTIONS requests
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
				header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
				header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

			exit(0);
		}
	}

	/**
	 *
	 * Set the config from the server vars
	 *
	 */
	private function setConfig()
	{
		$this->publicKey = getenv('MAILGUN_PUBLIC_KEY');
		$this->privateKey = getenv('MAILGUN_PRIVATE_KEY');

		$error = false;

		if(!$this->publicKey) {
			$error = true;
		}

		if(!$this->privateKey) {
			$error = true;
		}

		if(isset($_POST['domain'])) {
			$this->domain = $_POST['domain'];
		} else {
			$error = true;
		}

		if(isset($_POST['mailingList'])) {
			$this->mailingList = $_POST['mailingList'];
		} else {
			$error = true;
		}

		if(isset($_POST['recipientAddress'])) {
			$this->recipientAddress = $_POST['recipientAddress'];
		} else {
			$error = true;
		}

		if($error) {
			die('Config not properly set');
		}
	}

	/**
	 *
	 * Perform the subscription
	 *
	 */
	private function subscribe()
	{
		// First, instantiate the SDK with the API credentials, domain, and required parameters. 
		$mg = new Mailgun($this->privateKey);
		$mgValidate = new Mailgun($this->publicKey);

		// Let's validate the customer's email address, using Mailgun's validation endpoint.
		$result = $mgValidate->get('address/validate', array('address' => $this->recipientAddress));

		if($result->http_response_body->is_valid == true) {
			// Now, let's send a confirmation to the recipient with our link.
			$mg->sendMessage($this->domain, [
				'from'    => $this->mailingList, 
				'to'      => $this->recipientAddress, 
				'subject' => 'Thank You!', 
				'html'    => "<html><body>Hello,<br><br>Thank you for subscribing to the " . $this->mailingList . " mailing list. We will let you know about any updates.</body></html>"
			]);

			// Finally, let's add the subscriber to a Mailing List.
			$mg->post("lists/" . $this->mailingList . "/members", [
				'address'    => $this->recipientAddress, 
				'subscribed' => 'yes',
				'upsert'     => 'yes'
			]);
		}
	}
}

$v = new App;
$v->boot();
