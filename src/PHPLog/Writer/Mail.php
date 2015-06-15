<?php

namespace PHPLog\Writer;

use PHPLog\WriterAbstract;
use PHPLog\Event;
use PHPLog\Level;
use PHPLog\Layout\Pattern;
use PHPLog\Configuration;

/**
 * A writer which outputs a block of log entries to email.
 * This class can be configured to only allow certain log levels (overriding the loggers level)
 * because of how intrusive emails can be.
 */
class Mail extends WriterAbstract {

	/* send emails using an SMTP server */
	const TRANSPORT_TYPE_SMTP 	   = 'Smtp';

	/* send emails by communicating with a locally installed MTA -- such as sendmail */
	const TRANSPORT_TYPE_SENDMAIL  = 'Sendmail';

	/* sends emails delegating to PHP's internal mail() function */
	const TRANSPORT_TYPE_MAIL      = 'Mail';

	/* the subject of the message */
	protected $subject;

	/* the email address to send the message to. */
	protected $to;

	/* the email address to set this message from. */
	protected $from;

	/* a custom level threshold to place on this writer alone, optional of course
	   and this is still restricted by the level set on the logger instance itself
	   as it would never reach point if the log was too low. If a log comes in that 
	   does not reach the threshold, it is then propagated to the parent logger, if
	   propagation is enabled.
	 */
	protected $threshold;

	/* the transport that will be using to send messages. */
	protected $transport;

	/* the mailer which will actually send the email messages */
	protected $mailer;

	/* the body of the email message. */
	protected $body = '';

	/* the default pattern to use on logs. */
	protected $pattern = '[%level] - [%date{Y-m-d H:i:s}] - %message%newline';

	/**
	 * @override
	 * Constructor - Initailizes the Mail transport and the mailer class and checks the validity of the
	 * emails in config.
	 * @param array $config the configuration for this writer.
	 */
	public function init(Configuration $config) {

		$transport = $config->transport;

		if(!($transport instanceof Configuration) || count($transport) == 0) {
			throw new \Exception('no transport config provided');
		}

		//check we have a transport type.
		if(!isset($transport->type) || !in_array($transport->type, 
				array(
					Mail::TRANSPORT_TYPE_MAIL,
					Mail::TRANSPORT_TYPE_SENDMAIL,
					Mail::TRANSPORT_TYPE_SMTP
				)
			)
		) {
			throw new \Exception('no valid transport type defined');
		}

		//get the transport parameters.
		$params = clone $transport; unset($param->type);
		$type = $tranport->type;

		//check we have enough overall args.
		if(count($params) == 0 && $type != Mail::TRANSPORT_TYPE_MAIL) {
			throw new \Exception('not enough provided arguments for transport type: ' . $type);
		}

		//check we have a username, password and host if the type is SMTP
		if($type == Mail::TRANSPORT_TYPE_SMTP && 
			(!array_key_exists('username', $params) || !array_key_exists('password', $params) || !array_key_exists('host', $params))) {
			throw new \Exception('smtp needs a username, password and host');
		}

		//determine that the host/command is set dependant on the type.
		if($type == Mail::TRANSPORT_TYPE_SENDMAIL &&
			(!array_key_exists('command', $params))) {
			throw new \Exception('sendmail needs a command to execute.');
		}

		//determine if an optional port is set for SMTP.
		if($type == Mail::TRANSPORT_TYPE_SMTP && (!array_key_exists('port', $params) || !is_numeric($params['port']))) {
			$params['port'] = 25;
		}

		//determine if an optional security is set for SMTP.
		if($type == Mail::TRANSPORT_TYPE_SMTP && (!array_key_exists('ssl', $params) || !is_bool($params['ssl']))) {
			$bool = (isset($params['ssl'])) ? filter_var($params['ssl'], FILTER_VALIDATE_BOOLEAN) : false;
			$params['ssl'] = $bool;
		}

		$ssl = ($params['ssl']) ? 'ssl' : null;

		//initialize the transport.
		$className = "\Swift_{$type}Transport";

		$transport = ($type == Mail::TRANSPORT_TYPE_SENDMAIL)
				? $className::newInstance($params['command'])
				: (($type == Mail::TRANSPORT_TYPE_SMTP)
					? $className::newInstance($params['host'], $params['port'], $ssl)
					: $className::newInstance());

		//if transport is SMTP, set the username and password.
		if($type == Mail::TRANSPORT_TYPE_SMTP) {
			$transport->setUsername($params['username'])->setPassword($params['password']);
		}

		//initialize the mailer.
		$this->transport = $transport;
		$this->mailer = \Swift_Mailer::newInstance($transport);

		//check the to and from emails for validity.
		if(!isset($config['to']) || !isset($config['from'])) {
			throw new \Exception('to or from email is not set.');
		}

		foreach(array($config['to'], $config['from']) as $email) {
			if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new \Exception('could not validate to and from emails.');
			}
		}

		$this->to = $config['to']; $this->from = $config['from'];

		//set the default subject if one is not set.
		if(!array_key_exists('subject', $config) || strlen($config['subject']) == 0) {
			$config['subject'] = 'PHPLog Mail Writer';
		}

		$this->subject = $config['subject'];

		//finally see if the users set any custom threshold for just this writer.
		$this->threshold = ($config->threshold instanceof Level)
			? $config['threshold']
			: Level::all();


		//set the layout.
		if(!isset($config->layout['pattern'])) {
			$this->getConfig()->layout->pattern = $this->pattern;
		}

		$this->setLayout(new Pattern());

	}

	/**
	 * @override 
	 * attempts to append a log to the end of the email body.
	 * emails are only send when the writer is closing.
	 * @param Event $event the event to log.
	 */
	public function append(Event $event) {
		$text = '';
		if($this->getLayout() !== null) {
			$text = $this->getLayout()->parse($event);
		}

		if(strlen($text) == 0) {
			return false;
		}

		if(!$event->getLevel()->isGreaterOrEqualTo($this->threshold)) {
			return false;
		}

		//we want to convert the newline to a br as a email is html.
		$text = nl2br($text);
		$this->body .= $text;
		return true;

	}

	/**
	 * @override
	 * attempts to send the email and then closes the writer down.
	 */
	public function close() {
		if(!$this->isClosed()) {
			//attempt to send the block of stored logs in this execution.
			$message = \Swift_Message::newInstance();
			$message->setTo($this->to)->setFrom($this->from)
					->setBody($this->body, 'text/html')
					->setSubject($this->subject);

			$this->mailer->send($message);
			parent::close();
		}
	}

}