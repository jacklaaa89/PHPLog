<?php

namespace PHPLog\Layout;

use PHPLog\LayoutAbstract;
use PHPLog\Event;

/**
 * This class formats a log based on a pattern that is provided to the configuration
 * and replaces placeholders with the log values when they are returned as a string.
 * @version 2 - updated the regex which parses patterns.
 * @version 2.1 - added location information for where the event was triggered from.
 * @version 2.2 - now utilizes renderers to format variables from objects to strings.
 * @version 3.0beta - added functionality for if/else statement in patterns.
 * @author Jack Timblin
 */
class Pattern extends LayoutAbstract {

	/* the current version of the pattern parser. */
	const STABLE_VERSION = 'stableParse';

	/* the current version of the beta pattern parser. */
	const BETA_VERSION = 'betaParse';

	/* any values that need special treatment, i.e formatting dates, currency etc. */
	private $specialValues;

	/* any filters to apply to the value, i.e uc => ucwords, l => lowercase etc. */
	private $filters;

	/* the provided pattern to print values into. */
	protected $pattern;

	/* the identifier that starts a value placeholder in the pattern. */
	private $identifier = '%';

	/* the version the user has opted to use, default to stable version VERSION (as of writing 2.21) */
	private $versionUsed = self::STABLE_VERSION;

	/** 
	 * the old regex used to parse values into the pattern.
	 * @version 1.1 - added some more identifiers for date/times in attributes.
	 * @depreceated - this layout now uses the new regex pattern and this will be removed in version 3.
	 */
	//private $regex_old = '/(__ID__(\w+)(?:\{([\w\d\-\,\ \:\/]+)\})?(?:\|([\w]{1,2})(?:\|([\w]{1,2}))*)?)/';

	/**
	 * the regex used to parse values into the pattern.
	 * @version 2 - added support for placeholders as attributes in other placeholders i.e %date{%format}
	 * and it also allows for alphanumeric variables to be passed to filters. i.e %cost|nf(2)|uc
	 */
	private $regex = '/(__ID__([\w\d]+)(?:\{([\w\d\-, \:\/]+|(?:(__ID__)([\w\d]+)))\})?(?:\|(?:([\w]{1,2})(?:\(([\w\d+\/\:]+)?\))?)(?:\|([\w\d]{1,2})(?:\(([\w\d_\/\:]+)?\))?)?)?)/';

	/**
	 * the regex to allow for a single if/else statement in patterns.
	 * @version 1 - allows for a single if/else statement, no nested statements are permitted and will
	 * be parsed normally.
	 * @todo this needs to be dramatically improved, and because of this, its an opt-in feature
	 * of the parser. the syntax is %if {variableName} ({operator} {value})?% '..' (%else% '..')? %endif% if the identifier is '%'.
	 * the boolean statement in the if is true for boolean values, or non-empty values. false for anything else.
	 */
	private $regex_if = '/(?:(?:__ID__if ([\w\d]+)(?: (<=|>=|==|<|>) ([\w\d]+))?__ID__)((?:([\s\S]+)__ID__else__ID__([\s\S]+))|([\s\S]+))(?:__ID__endif__ID__))/';

	/* added some constant values. */
	private $consts = array();

	/**
	 * @override
	 * Constructor, initialises all values needed to parse patterns.
	 * @param array $config the configuration for this layout.
	 */
	public function __construct($config) {
		parent::__construct($config);

		//get configuration values passed from the user.
		$this->pattern = (isset($config['pattern']) && strlen($config['pattern']) > 0) ? $config['pattern'] : 'LOG - %level - %message - %date';
		$this->identifier = (isset($config['identifier']) && strlen($config['identifier']) > 0) ? $config['identifier'] : $this->identifier;

		//format the regex's to parse variables from the pattern.
		$this->regex = str_replace('__ID__', $this->identifier, $this->regex);
		$this->regex_if = str_replace('__ID__', $this->identifier, $this->regex_if);

		//determine what pattern parser version to use, defaults to the latest stable version.
		$this->versionUsed = (isset($config['version'])) ? $config['version'] : self::STABLE_VERSION;

		//set up the filters and variables that require special formatting, like dates.
		$this->filters = array(
			/* ucwords => (a string => A String) */
			'uc' => function($value, $attr) {
				return ucwords(strtolower($value));
			},
			/* strtoupper => (a string => A STRING) */
			'u' => function($value, $attr) {
				return strtoupper($value);
			},
			/* strtolower => (A STRING => a string) */
			'l' => function($value, $attr) {
				return strtolower($value);
			},
			/* number_format => (1605.45544 => 1,605.46) */
			'nf' => function($value, $attr) {
				$attr = (!is_numeric($attr)) ? 2 : (int) $attr;
				return number_format($value, $attr);
			},
			/* trim  => (' a string ' => 'a string') */
			't' => function($value, $attr) {
				$attr = (strlen($attr) > 0) ? $attr : ' ';
				return trim($value, $attr);
			},
			/* ltrim => (' a string ' => 'a string ') */
			'lt' => function($value, $attr) {
				$attr = (strlen($attr) > 0) ? $attr : ' ';
				return ltrim($value, $attr);
			},
			/* rtrim => (' a string ' => ' a string') */
			'rt' => function($value, $attr) {
				$attr = (strlen($attr) > 0) ? $attr : ' ';
				return rtrim($value, $attr);
			},
			//replaces $attr with ' ' good for spliting words etc.
			'e' => function($value, $attr) {
				$value = implode(' ', explode($attr, $value));
				return $value;
			}	
		);

		//initialize the const values. i.e the variables that are defined as constants.
		$this->consts = array(
			'newline' => PHP_EOL,
			'tab' => "\t"
		);

		$this->specialValues = array(
			/* used to format dates into a specific format. */
			'date' => function($value, $format) {
				$format = (isset($format) && strlen($format) > 0) ? $format : 'Y-m-d';
				return date($format, $value);
			},
			/* used for allow for multiple tabs by using an int argument. */
			'tab' => function($value, $format) {
				$format = (strlen($format) < 2 && strlen($format) > 0 && is_numeric($format)) ? $format : 1;
				$format = intval($format) - 1;
				$v = $value;
				if($format > 0) {
					for($i = 0; $i < $format; $i++) {
						$value .= $v;
					}
				}
				return $value;
			}
		);

		
	}

	/**
	 * parses the current log event into the pattern in which the log should be formatted.
	 * 
	 * ### Options ###
	 * a pattern can be any string format and contain any characters but anything prefixed with
	 * the identifier variable will be replaced with the relevent variable or '' if that variable
	 * does not exist. Variables will attempted to be pulled from the event object that is passed
	 * into this method, (extra variables with custom names are assigned to this object at runtime)
	 * and because of this there are some defined variables that have been statically set in the 
	 * pattern parser to account for this. These are:
	 *
	 * %message - the message that was passed to the log.
	 * %level - the string representation of the severity level for the log.
	 * %date({format})* - the date that that the log was created, can optionally be formatted.
	 * %logger - the name of the logger that generated the log.
	 * %newline - outputs a PHP_EOL string.
	 * %tab({amount})* - outputs a tab an amount can be defined to increase the size of the tab.
	 *
	 * Any other variables that are defined in the pattern will be treated as extra values. 
	 * And obviously the '%' will be substituted with whatever identifier is defined on configuration.
	 *
	 * ### FILTERS ###
	 * optional filters can be applied to variables in the pattern (apart from %newline and %tab)
	 * which are applied to the variable. (maximum of two filters per variable) These include: 
	 *
	 * uc - applies ucwords to the value.
	 * u - applies strtoupper to the value
	 * l - applies strtolower to the value
	 * nf - applies number_format to the value.
	 * t - applies trim to the value.
	 * lt - applies ltrim to the value.
	 * rt - applies rtrim to the value.
	 * e(delimiter) - seperates a word by a delimiter. so IS_NULL with e(_) becomes IS NULL
	 *
	 * These filters are applied by using the pipe operator in a variable. for example: %message|u
	 *
	 * ### VERSION 2 ###
	 * Any variable that requires an argument (i.e %date{%format}) can now be passed another variable
	 * which will also be resolved and passed to the approiate function that deals with applying arguments
	 * to variables. i.e %format in %date{%format} will be resolved from the event as a variable and passed
	 * to the function which formats the date.
	 *
	 * Filters are also allowed alphanumeric arguments in order to add more detailed customisation in using filters.
	 * it also unlocks the possibility of using more complex filters. for example %cost|nf(2)|t will apply number_format
	 * to the resolved %cost variable to 2 decimal places and then trim will be applies to that value.
	 *
	 * ### VERSION 2.1 ###
	 * the Event now carries information for the location on where the log event was triggered. This information 
	 * includes: 
	 *
	 * %line - the line number in the script that executed the log event.
	 * %file - the name of the file that executed the log event.
	 * %class - the class name of the object that executed the event.
	 * %function - the function that the log event was called in.
	 *
	 * @param Event $event the event that we are attempting to log.
	 * @return string the $event formatted to the provided pattern.
	 */
	public function stableParse(Event $event) {
		return $this->parseStatement($event, $this->pattern);
	}

	public function betaParse(Event $event) {
		//we need to first parse any if/else statements that are in the pattern.
		preg_match_all($this->regex_if, $this->pattern, $matches);
		
		//check if we had any if/statement matches in the pattern.
		$hasMatches = (is_array($matches) && isset($matches[0]) && count($matches[0]) > 0);

		$statement = $this->pattern;

		if($hasMatches) {
			//go through each of the matches and evaluate the statement.
			for($i = 0; $i < count($matches[0]); $i++) {
				$exprVar = ''; $placeholder = $matches[0][$i]; $exprTrue = false;
				//get the value to test.
				$exprName = 'get'.ucwords($matches[1][$i]);
				$exprVar = $event->{$exprName}();
				//no need to render we are only testing it.
				//generate the expression.
				$ex = '$exprTrue = (isset($exprVar));';
				if(isset($matches[2][$i]) && strlen($matches[2][$i]) > 0) {
					$operator = $matches[2][$i]; $v = $matches[3][$i];
					$ex = '$exprVar = (isset($exprVar) && $exprVar '.$operator.' '.$v.');';
				}
			}
			die(var_dump($ex));
		}

		return $this->parseStatement($event, $statement);

	}

	public function parseStatement(Event $event, $statement) {
		//parse the variables from the event into the provided pattern.
		preg_match_all($this->regex, $statement, $matches);
			
		//check have matches.
		if(count($matches) == 0 || !isset($matches[2]) || count($matches[2]) == 0) {
			return $this->pattern; //no matches, return the pattern.
		}

		$pattern = $statement;

		//get the variables from the event.
		//get the variable from the event.
		for($i = 0; $i < count($matches[2]); $i++) {

			$isConst = false; $var = ''; $placeholder = $matches[0][$i];

			if(array_key_exists($matches[2][$i], $this->consts)) {
				$var = $this->consts[$matches[2][$i]];
				$isConst = true;
			}

			if(!$isConst) {
				$name = 'get'.ucwords($matches[2][$i]);
				$var = $event->$name();
				$var = $this->render($var);

				//pass it through any filters that have been added to the variable.
				$f = array($matches[6][$i] => $matches[7][$i], $matches[8][$i] => $matches[9][$i]);

				foreach($f as $fil => $attr) {
					if(strlen($fil) > 0 && array_key_exists($fil, $this->filters)) {
						$func = $this->filters[$fil];
						$var = $func($var, $attr);
					}
				}
			}

			if(array_key_exists($matches[2][$i], $this->specialValues)) {
				//get the attribute, and pass it through the function.
				$func = $this->specialValues[$matches[2][$i]];
				$att = $matches[3][$i];

				//check that the attr is not another placeholder.
				if($matches[4][$i] == $this->getIdentifier()) {
					$attrName = 'get'.ucwords($matches[5][$i]);
					$att = $event->$attrName();
					$att = ($attr !== null) ? $att : '';
					$att = (!is_string($att)) ? (string) $att : $att;
				}
				$var = $func($var, $att);
			}

			//push the value in the correct position.
			$pattern = str_replace($placeholder, $var, $pattern);

		}

		return $pattern;
	}

	public function parse(Event $event) {
		if(!method_exists($this, $this->versionUsed)) {
			return ''; //could not parse the event.
		}
		return $this->{$this->versionUsed}($event);
	}

	/**
	 * gets the identifier in which variables are defined in a pattern
	 * i.e 'LOG - %message' <- message is a variable which is replaced if the identifier is '%' 
	 * @return string the identifier.
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

}