<?php

namespace PHPLog\Writer;

use PHPLog\WriterAbstract;
use PHPLog\Event;
use PHPLog\Layout\Bind;
use PHPLog\Configuration;

class PDO extends WriterAbstract {

	/* the database source name, a connection url to the database instance. */
	protected $dsn;

	/* the username used to connect to the database. */
	protected $username;

	/* the password used to connect to the database. */
	protected $password;

	/* the name of the database table. */
	protected $tableName;

	/* the PDO database connection handle. */
	protected $connection;

	/**
	 * the insert statement used to input log events into the database.
	 * @param :table: this will be replaced with the database table name.
	 * @param :keys: this will be replaced with the columns of the database, this will
	 * 			     be the names of the fields in the pattern.
	 * @param :bindkeys: this will be replaced with the same fields as keys except they are prefixed
	 * 				 with a ':' to match the placeholder in the generated bind array. 
	 */
	private $insertStatement = 'INSERT INTO :table: (:keys:) VALUES (:bindkeys:)';

	/* the pattern used in the conversion. */
	protected $pattern = '%level,%message,%date{Y-m-d H:i:s}';

	/**
	 * Constructor - initializes the database connection.
	 * @param array $config the configuration for this writer.
	 * 
	 * for this writer to work we need a dsn, a username, password and a table name.
	 */
	public function __construct(Configuration $config) {

		parent::__construct($config);

		if(!isset($config['tableName']) || strlen($config['tableName']) == 0) {
			throw new \Exception('table name is required');
		}

		$this->tableName = $config['tableName'];

		$this->dsn = $config->get('dsn', '');
		$this->username = $config->get('username', '');
		$this->password = $config->get('password', '');
		$this->insertStatement = $config->get('insertStatement', $this->insertStatement);

		try {
			$this->connection = new \PDO($this->dsn, $this->username, $this->password);
			$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} catch(\PDOException $e) {
			throw new \Exception('could not connect to database instance: ' . $e->getMessage());
		}

		$this->insertStatement = str_replace(':table:', $this->tableName, $this->insertStatement);
			
		if(!isset($this->getConfig()->layout->pattern)) {
			$this->getConfig()->layout->pattern = $this->pattern;
		}

		$this->setLayout(new Bind());

	}
	
	/**
	 * attempts to log the event in the database.
	 * @param Event $event the logging event we are attempting to log.
	 */
	public function append(Event $event) {
		//generate the log using the layout.
		$bind = array();

		if($this->getLayout() !== null) {
			$bind = $this->getLayout()->parse($event);
		}

		//format the insert statement.
		$this->insertStatement = str_replace(':keys:', str_replace(':', '', implode(',', array_keys($bind))), $this->insertStatement);
		$this->insertStatement = str_replace(':bindkeys:', implode(',', array_keys($bind)), $this->insertStatement);

		if(!isset($this->connection)) {
			return false;
		}

		$this->connection->beginTransaction();
		try {
			$statement = $this->connection->prepare($this->insertStatement);
			if(!$statement->execute($bind)) {
				//get the errors.
			}
			$this->connection->commit();
		} catch (\Exception $e) {
			$this->connection->rollback();
			return false;
		}

		return true;

	}

}