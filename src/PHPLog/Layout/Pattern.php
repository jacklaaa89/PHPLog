<?php

namespace PHPLog\Layout;

use PHPLog\LayoutAbstract;
use PHPLog\Event;
use PHPLog\Exception\CompilerException;
use PHPLog\Configuration;

/**
 * This class formats a log based on a pattern that is provided to the configuration
 * and replaces placeholders with the log values when they are returned as a string.
 * @version 2 - updated the regex which parses patterns.
 * @version 2.1 - added location information for where the event was triggered from.
 * @version 2.2 - now utilizes renderers to format variables from objects to strings.
 * @version 3.0beta - added functionality for if/else statement in patterns.
 * @version 3.0beta2 - added syntax error capturing support.
 * @version 3.0beta3 - utilizes the new configuration model for layouts and writers.
 * @version 3.0beta4 - added the functionality to allow for custom, filters and consts etc in the parser.
 * @version 3.0beta5 - allowed for an infinite amount of variables in functions and filters.
 * these are then parsed into an array and passed to the function.
 * @version 3.0 - the parser has graduated to a stable version 3.0.
 * @author Jack Timblin
 */
class Pattern extends LayoutAbstract
{

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

    /* the version the user has opted to use, default to stable version VERSION (as of writing 2.2) */
    private $versionUsed = self::STABLE_VERSION;

    /**
     * the regex used to parse values into the pattern.
     * @version 2 - added support for placeholders as attributes in other placeholders i.e %date{%format}
     * and it also allows for alphanumeric variables to be passed to filters. i.e %cost|nf(2)|uc
     * @version 2.1beta - updated to support an infinite amount of variables passed through to the special values and filters.
     * variables will also have to be wrapped in '' quotes so we can parse them correctly.
     * @version 2.2beta - multiple parameters are a lot more structured, params have to be wrapped
     * in "'" and seperated with a ","
     * @version 2.2 - commas and "'" can now be escaped using the normal escape backstash so comma in a value is \, and "'" is \'
     */
    private $regex = '/(__ID__([\w\d]+)(?:\{(?:(\'[\\\ \w\d\-,\'\.\:\/\"]*\'(?:,\'[\\\ \w\d\-,\.\'\:\/]*\')*)|(?:(__ID__)([\w\d]+)))\})?(?:\|(?:([\w]{1,2})(?:\((\'[\w\d\-,\.\\\ \:\/]*\'(?:,\'[\w\d\-,\.\\\ \:\/]*\')*)?\))?)(?:\|([\w\d]{1,2})(?:\((\'[\w\d\-,\.\'\\\ \:\/]*\'(?:,\'[\w\d\-,\.\'\\\ \:\/]*\')*)?\))?)?)?)/';

    /**
     * the regex to allow for a single if/else statement in patterns.
     * @version 1 - allows for a single if/else statement, no nested statements are permitted and will
     * be parsed normally.
     * @version 1.1 - the parser accounts for most/all syntax errors that would break the regex, so this is 
     * its final form. We have also optional expressions in the if statement so '%if tester > 3%' is valid.
     */
    private $regex_if = '/(?:(?:__ID__if ([\w\d]+)(?:(?: (<=|>=|==|<|>) ([\w\d]+))|[ ])?__ID__)((?:([\s\S]+)__ID__else__ID__([\s\S]+))|([\s\S]+))(?:__ID__endif__ID__))/';

    /* added some constant values. */
    private $consts = array();

    /**
     * initializes the layout using the configuration.
     * @param Configuration $config the configuration for this layout.
     * @see PHPLog\LayoutAbstract::init()
     */
    public function init(Configuration $config) 
    {
        //get configuration values passed from the user.
        parent::init($config);

        $this->pattern = $config->get('pattern', $this->pattern);
        $this->identifier = $config->get('identifier', $this->identifier);

        //format the regex's to parse variables from the pattern.
        $this->regex = str_replace('__ID__', $this->identifier, $this->regex);
        $this->regex_if = str_replace('__ID__', $this->identifier, $this->regex_if);

        $this->versionUsed = $config->get('version', self::STABLE_VERSION);

        //set up the filters and variables that require special formatting, like dates.
        $this->filters = array(
        /* ucwords => (a string => A String) */
        'uc' => function ($value, $attr) {
            return ucwords(strtolower($value));
        },
        /* strtoupper => (a string => A STRING) */
        'u' => function ($value, $attr) {
            return strtoupper($value);
        },
        /* strtolower => (A STRING => a string) */
        'l' => function ($value, $attr) {
            return strtolower($value);
        },
        /* number_format => (1605.45544 => 1,605.46) */
        'nf' => function ($value, $attr) {
            $attr = (is_array($attr)) ? $attr[0] : $attr;
            $attr = (!is_numeric($attr)) ? 2 : (int) $attr;
            return number_format($value, $attr);
        },
        /* trim  => (' a string ' => 'a string') */
        't' => function ($value, $attr) {
            $attr = (is_array($attr)) ? $attr[0] : $attr;
            $attr = (strlen($attr) > 0) ? $attr : ' ';
            return trim($value, $attr);
        },
        /* ltrim => (' a string ' => 'a string ') */
        'lt' => function ($value, $attr) {
            $attr = (is_array($attr)) ? $attr[0] : $attr;
            $attr = (strlen($attr) > 0) ? $attr : ' ';
            return ltrim($value, $attr);
        },
        /* rtrim => (' a string ' => ' a string') */
        'rt' => function ($value, $attr) {
            $attr = (is_array($attr)) ? $attr[0] : $attr;
            $attr = (strlen($attr) > 0) ? $attr : ' ';
            return rtrim($value, $attr);
        },
        //replaces $attr with ' ' good for spliting words etc.
        'e' => function ($value, $attr) {
            $attr = (is_array($attr)) ? $attr[0] : $attr;
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
        'date' => function ($value, $format) {
            $format = (is_array($format)) ? $format[0] : $format;
            $format = (isset($format) && strlen($format) > 0) ? $format : 'Y-m-d';
            return date($format, $value);
        },
        /* used for allow for multiple tabs by using an int argument. */
        'tab' => function ($value, $format) {
            $format = (is_array($format)) ? $format[0] : $format;
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

        //check the configuration for any custom consts, filters or special values.
        //defaultly defined functions cannot be overridden and will be ignored.
        $filters = $config->get('filters', new Configuration(array()));
        $consts = $config->get('consts', new Configuration(array()));
        $variableFunctions = $config->get('variableFunctions', new Configuration(array()));

        //add custom filters.
        foreach($filters as $name => $function) {
            if($function instanceof \Closure) {
                $reflection = new \ReflectionFunction($function);
                if($reflection->getNumberOfRequiredParameters() >= 2) {
                    //only the first two params are used at the minute.
                    if(!array_key_exists($name, $this->filters)) {
                        $this->filters[$name] = $function;
                    }
                }
            }
        }

        //add custom specialValues.
        foreach($variableFunctions as $name => $function) {
            if($function instanceof \Closure) {
                $reflection = new \ReflectionFunction($function);
                if($reflection->getNumberOfRequiredParameters() >= 2) {
                    //only the first two params are used at the minute.
                    if(!array_key_exists($name, $this->specialValues)) {
                        $this->specialValues[$name] = $function;
                    }
                }
            }
        }

        //add custom consts.
        foreach($consts as $name => $value) {
            if(isset($value) && is_string($value)) {
                //only the first two params are used at the minute.
                if(!array_key_exists($name, $this->consts)) {
                    $this->consts[$name] = $value;
                }
            }
        }

    }

    /**
     * Currently no Beta version of the parser is available. Will throw an error on use.
     * @param Event $event the event that we are attempting to log.
     * @return string the $event formatted to the provided pattern.
     * @throws Exception there is no beta version, so throws an error to confirm that.
     */
    public function betaParse(Event $event) 
    {
        throw new \Exception('Beta parser version is currently not available.');
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
     * ### VERSION 3.0beta ###
     * the parser can now handle basic if/else statements in patterns. The variable in the
     * the condition is first resolved and then evaluated to determine if it is true or false.
     * The statement inside the if/else is then resolved based on this outcome and this is then
     * pushed back into the statement. We can only handle a single if/else sequence. (more than one can
     * be defined in the pattern, but there can be no nested if/else statements)
     * 
     * ### VERSION 3.0beta2 ###
     * in this beta version any syntax errors that occur using if/else statements will throw
     * an exception. These violations include:
     *
     * - no if/endif statement (i.e an if without an endif and vice versa.)
     * - a rogue 'else' without an if or else.
     * - a nested if/else statement was found.
     *
     *
     * ### VERSION 3.0beta3 ###
     * The pattern parser now utilizes the new PHPLog\Configuration class for config instead
     * of using a standard array.
     *
     * ### VERSION 3.0beta4 ###
     * a user can now supply custom filter functions, special value functions and consts in the
     * configuration and they will be used in the compiler if they meet the minimum requirements
     * of that particular function and dont attempt to override default functions.
     * 
     * ### VERSION 3.0beta5 ###
     * parameters passed to filters and functions must now be contained in '' marks and cannot 
     * contain any \' in the value. The advantage of this is to allow multiple parameters in filters/functions
     * i.e %message|sr('View','Show',...) any amount of params can be applied and they get passed
     * as an array to the function that will deal with it.
     *
     * ### VERSION 3.0 ###
     * the beta parser has now graduated from beta.
     *
     * ### KNOWN ISSUES / BUGS ###
     * an if/else statement is not recognised if any of the inside statements are empty.
     *
     * @param  Event $event the event that we are attempting to log.
     * @return string the $event formatted to the provided pattern.
     * @throws Exception if a syntax error is found in the pattern.
     */
    public function stableParse(Event $event) 
    {
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
                    $ex = '$exprTrue = (isset($exprVar) && ($exprVar '.$operator.' '.$v.'));';
                }
                eval($ex);

                $else = null;
                //determine what to render based on the outcome.
                //first determine if there was an else statement.
                if(isset($matches[6][$i]) && strlen($matches[6][$i]) > 0) {
                    $else = $matches[6][$i];
                }

                //get the if statement.
                $if = (isset($else)) ? $matches[5][$i] : $matches[7][$i];

                //check that the if or else does not currently contain any nested if/else statements.
                preg_match_all($this->regex_if, $if, $ifMatches, PREG_OFFSET_CAPTURE);
                if(is_array($ifMatches) && isset($ifMatches[0]) && count($ifMatches[0]) > 0) {
                    $offset = (isset($ifMatches[0]) && count($ifMatches[0]) > 0 && isset($ifMatches[0][1])) ? $ifMatches[0][1] : null;
                    throw new CompilerException('Syntax Error: nested if/else sequence found.', $if, $offset);
                }

                if(isset($else)) {
                    preg_match_all($this->regex_if, $else, $elseMatches, PREG_OFFSET_CAPTURE);
                    if(is_array($elseMatches) && isset($elseMatches[0]) && count($elseMatches[0]) > 0) {
                        $offset = (isset($elseMatches[0]) && count($elseMatches[0]) > 0 && isset($elseMatches[0][1])) ? $elseMatches[0][1] : null;
                        throw new CompilerException('Syntax Error: nested if/else sequence found.', $else, $offset);
                    }
                }

                //check for broken nested/if else statements in the if and else.
                $this->checkForSyntaxErrors($if);
                if(isset($else)) {
                    $this->checkForSyntaxErrors($else);
                }

                $variable = ($exprTrue) ? $this->parseStatement($event, $if) 
                : ((isset($else)) ? $this->parseStatement($event, $else) : '');

                $statement = str_replace($placeholder, $variable, $statement);

            }
        } else {
            $this->checkForSyntaxErrors($statement);
        }

        return $this->parseStatement($event, $statement);

    }

    /**
     * checks if any broken if/else statements in a statement.
     * @throws \Exception if a syntax error occured.
     */
    private function checkForSyntaxErrors($statement) 
    {
        //check we dont have any syntax errors. ('i.e rogue %if {condition}% or %else% or %endif%')
        if(preg_match(
            '/(?:(?:'.$this->getIdentifier().'if ([\w\d]+)(?:(?: (==|<|>|<=|>=) ([\w\d]+))|[ ])?'.$this->getIdentifier().'))/',
            $statement, $m, PREG_OFFSET_CAPTURE
        )) {
            $offset = (isset($m[0]) && count($m[0]) > 0 && isset($m[0][1])) ? $m[0][1] : null;
            throw new CompilerException('Syntax Error: if statement defined with no closing endif', $statement, $offset);
        }
        if(preg_match('/'.$this->getIdentifier().'else'.$this->getIdentifier().'/', $statement, $m, PREG_OFFSET_CAPTURE)) {
            $offset = (isset($m[0]) && count($m[0]) > 0 && isset($m[0][1])) ? $m[0][1] : null;
            throw new CompilerException('Syntax Error: else statement supplied without if/endif statement', $statement, $offset);
        }
        if(preg_match('/'.$this->getIdentifier().'endif'.$this->getIdentifier().'/', $statement, $m, PREG_OFFSET_CAPTURE)) {
            $offset = (isset($m[0]) && count($m[0]) > 0 && isset($m[0][1])) ? $m[0][1] : null;
            throw new CompilerException('Syntax Error: endif statement supplied without if statement', $statement, $offset);
        }
    }

    /**
     * parses an individual statement, i.e resolves variables and pushes them into the pattern.
     * @param Event  $event     the event to parse.
     * @param string $statement the statement to evaluate.
     * @return string the evaluated statement.
     */
    public function parseStatement(Event $event, $statement) 
    {
        //parse the variables from the event into the provided pattern.
        if(!preg_match_all($this->regex, $statement, $matches)) {
            throw new CompilerException('Syntax Error - regex compiler exception', $statement);
        }

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
                try {
                    $var = $this->render($var);
                } catch(\Exception $e) {
                    throw new CompilerException('An error occured rendering a value', $statement);
                }

                //pass it through any filters that have been added to the variable.
                $f = array($matches[6][$i] => $matches[7][$i], $matches[8][$i] => $matches[9][$i]);

                foreach($f as $fil => $attr) {
                    if(strlen($fil) > 0 && array_key_exists($fil, $this->filters)) {
                        $func = $this->filters[$fil];

                        //format the args.
                        $attr = $this->formatArgs($attr);
                        try {
                            $var = $func($var, $attr);
                        } catch (\Exception $e) {
                            throw new CompilerException('An error occured running a filter.', $statement);
                        }
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

                $att = $this->formatArgs($att);
                try {
                    $var = $func($var, $att);
                } catch (\Exception $e) {
                    throw new CompilerException('An error occured running a variable function.', $statement);
                }
            }

            //push the value in the correct position.
            $pattern = str_replace($placeholder, $var, $pattern);

        }

        return $pattern;
    }

    /**
     * @see PHPLog\WriterAbstract::parse()
     */
    public function parse(Event $event) 
    {
        if(!method_exists($this, $this->versionUsed)) {
            return ''; //could not parse the event.
        }
        return $this->{$this->versionUsed}($event);
    }

    /**
     * attempts to format the arguments passed to either a function or a filter
     * in the parsing attempt.
     * @param string $args the args parsed through to the parser.
     * @return array an array of parsed args on success
     * @version 1.0 - graduated from beta phase.
     */
    private function formatArgs($args) 
    {

        //first replace any escaped "," with a unique token as this the delimiter.
        $tokens = array('__CTAG__', '__DTAG__');
        $args = str_replace(array('\,', '\\\''), $tokens, $args);

        //explode the variables by a comma and strip any whitespace and "'" from the start and end.
        $args = explode(',', $args);

        foreach($args as &$arg) {
            $arg = trim($arg, " '");
            $arg = str_replace($tokens, array(",", "'"), $arg);
        }

        return $args;

    }

    /**
     * gets the identifier in which variables are defined in a pattern
     * i.e 'LOG - %message' <- message is a variable which is replaced if the identifier is '%' 
     * @return string the identifier.
     */
    public function getIdentifier() 
    {
        return $this->identifier;
    }

    /**
     * determines the version that is currently being used from the @version tags in 
     * the classes header.
     * @return string the version of the parser that is currently being used.
     */
    public function getVersion() 
    {

        //check if the versions have already been set.
        if(isset($this->{'BETA_VERSION_NUMBER'}) && isset($this->{'STABLE_VERSION_NUMBER'})) {
            return ($this->versionUsed == self::STABLE_VERSION) 
            ? $this->{'STABLE_VERSION_NUMBER'} : $this->{'BETA_VERSION_NUMBER'};
        }

        $reflect = new \ReflectionClass($this);
        $comments = $reflect->getDocComment();

        //try and determine the latest normal / beta versions.
        preg_match_all('/(?:@version[ ]?([\d\.]+)(?:[ ]?([\w\d]+))?)/', $comments, $matches);

        if(!isset($matches) || !isset($matches[0]) || count($matches[0]) == 0) {
            return '1.0'; //could not determine the version.
        }

        $beta = null; $stable = null;
        for($i = count($matches[0]) - 1; $i > 0; $i--) {

            //working backwards.
            if(isset($beta) && isset($stable)) {
                break; //weve set the variables.
            }
            if(isset($matches[2][$i]) && strlen($matches[2][$i]) > 0) {
                if(!isset($beta)) {
                    $beta = $matches[1][$i] . $matches[2][$i];
                }    
            } else {
                if(!isset($stable)) {
                    $stable = $matches[1][$i];
                }
            }
            
        }

        //set the version numbers as a variable.
        $this->{'STABLE_VERSION_NUMBER'} = $stable;
        $this->{'BETA_VERSION_NUMBER'} = $beta;

        return ($this->versionUsed == self::STABLE_VERSION) 
        ? $this->{'STABLE_VERSION_NUMBER'} : $this->{'BETA_VERSION_NUMBER'};

    }

    /**
     * returns the regexes that are currently being used to parse
     * patterns in this parser.
     * only available to classes that extend this class.
     * @return array the regexes being used in this parser.
     */
    protected function getRegex() 
    {
        return array(
        str_replace('__ID__', $this->getIdentifier(), $this->regex), 
        str_replace('__ID__', $this->getIdentifier(), $this->regex_if)
        );
    }

}