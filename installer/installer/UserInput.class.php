<?php

define("FILE_USER_INPUT", "user_input.ini"); // this file contains the saved user input

/*
* This class handles all the user input
*/
class UserInput
{
	private $user_input;
	private $input_loaded = false;

	// return true if user input is already loaded
	public function hasInput() {
		return is_file(FILE_USER_INPUT);
	}
	
	// load input from user input file
	public function loadInput() {
		$this->user_input = parse_ini_file(FILE_USER_INPUT, true);
		$this->input_loaded = true;
	}
	
	// save the user input to input file
	public function saveInput() {
		OsUtils::writeConfigToFile($this->user_input, FILE_USER_INPUT);
	}		
	
	// get the input for the given $key
	public function get($key) {
		return $this->user_input[$key];
	}
	
	// set the input $value for the given $key
	public function set($key, $value) {
		return $this->user_input[$key] = $value;
	}
	
	// returns the user input array
	public function getAll() {
		return $this->user_input;
	}
	
	// returns true if user input is loaded
	public function isInputLoaded() {
		return $this->input_loaded;
	}
	
	/**
	 * gets input from the user and returns it
	 * if $key was already loaded from config it will be taked from there and user will not have to insert
	 * 
	 * @param string $key 
	 * @param string $request_text text to show the user
	 * @param string $not_valid_text text to show the user if the input is invalid (according to the validator)
	 * @param InputValidator $validator the input validator to user (default is null, no validation)
	 * @param string $default the default value (default's default is '' :))
	 * @param bool $hideValue do not show the value on the screen, in case it's password for example
	 * @return string
	 */
	public function getInput($key, $request_text, $not_valid_text, InputValidator $validator = null, $default = '', $hideValue = false) 
	{
		if (isset($key) && isset($this->user_input[$key]))
			return $this->user_input[$key];
		
		if (isset($validator) && !empty($default)) 
			$validator->emptyIsValid = true;
		
		logMessage(L_USER, $request_text);
			
		$inputOk = false;
		while (!$inputOk)
		{
			echo '> ';
			$input = trim(fgets(STDIN));
			
			if($hideValue)
			{
				logMessage(L_INFO, "User input accepted");
			}
			else
			{
				logMessage(L_INFO, "User input is $input");
			}
			
			if (isset($validator) && !$validator->validateInput($input)) 
			{
				logMessage(L_USER, $not_valid_text);
			} 
			else 
			{			
				$inputOk = true;
				echo PHP_EOL;
				
				if (empty($input) && !empty($default)) 
				{
					$input = $default;
					if($hideValue)
						logMessage(L_INFO, "Using default value");
					else
						logMessage(L_INFO, "Using default value: $default");
				}	
			}				
		}
		
		if (isset($key)) 
			$this->user_input[$key] = $input;
			
  		return $input;	
	}
	
	// Get a y/n input from the user
	// if $key was already loaded from config it will be taken from there and user will not have to insert
	// $request text - text to show the user	
	// $default - the default value (show be 'y'/'n')
	public function getTrueFalse($key, $request_text, $default) {	
		if (isset($key) && isset($this->user_input[$key])) {
			return $this->user_input[$key];
		}			
		
		if ((strcasecmp('y', $default) === 0) || (strcasecmp('yes', $default) === 0)) {
			$request_text .= ' (Y/n)';
		} else {
			$request_text .= ' (y/N)';
		}
		
		$validator = InputValidator::createYesNoValidator();
		$input = $this->getInput(null, $request_text, "Input is not valid", $validator, $default);
		$retrunVal = ((strcasecmp('y',$input) === 0) || (strcasecmp('yes',$input) === 0));
		
		if (isset($key)) $this->user_input[$key] = $retrunVal;
		return $retrunVal;		
	}
	
	// get all the user input for the installation
	public function getApplicationInput() {				

		logMessage(L_USER, "Please provide the following information:");
		echo PHP_EOL;
		
		$this->getInput(AppConfigAttribute::TIME_ZONE, 
						"Default time zone for Kaltura application (leave empty to use system timezone: ". date_default_timezone_get()." )",
						"Timezone must be a valid timezone, please enter again", 
						InputValidator::createTimezoneValidator(), 
						date_default_timezone_get());
		$this->getInput(AppConfigAttribute::BASE_DIR, 
						"Full target directory path for Kaltura application (leave empty for /opt/kaltura)",
						"Target directory must be a valid directory path, please enter again", 
						InputValidator::createDirectoryValidator(), 
						'/opt/kaltura');
		$this->getInput(AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME, 
						"Please enter the domain name/virtual hostname that will be used for the Kaltura server (without http://)", 
						'Must be a valid hostname or ip, please enter again', 
						InputValidator::createHostValidator(), 
						null);
		$this->getInput(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL, 
						"Your primary system administrator email address", 
						"Email must be in a valid email format, please enter again", 
						InputValidator::createEmailValidator(false), 
						null);
		$this->getInput(AppConfigAttribute::ADMIN_CONSOLE_PASSWORD, 
						"The password you want to set for your primary administrator", 
						"Password should not be empty and should not contain whitespaces, please enter again", 
						InputValidator::createNoWhitespaceValidator(), 
						null, 
						true);
		$this->getInput(AppConfigAttribute::DB1_HOST, 
						"Database host (leave empty for 'localhost')", 
						"Must be a valid hostname or ip, please enter again (leave empty for 'localhost')", 
						InputValidator::createHostValidator(), 
						'localhost');
		$this->getInput(AppConfigAttribute::DB1_PORT, 
						"Database port (leave empty for '3306')", 
						"Must be a valid port (1-65535), please enter again (leave empty for '3306')", 
						InputValidator::createRangeValidator(1, 65535), 
						'3306');
		$this->set(AppConfigAttribute::DB1_NAME,'kaltura'); // currently we do not support getting the DB name from the user because of the DWH implementation
		$this->getInput(AppConfigAttribute::DB_ROOT_USER, 
						"Database username (with create & write privileges)", 
						"Database username cannot be empty, please enter again", 
						InputValidator::createNonEmptyValidator(), 
						null);
		$this->getInput(AppConfigAttribute::DB_ROOT_PASS, 
						"Database password (leave empty for no password)", 
						null, 
						null, 
						null);
		$this->getInput(AppConfigAttribute::DB1_CREATE_NEW_DB, 
						"Would you like to create a new kaltura database or use an exisiting one? (Y/n)", 
						"Input is not valid", 
						InputValidator::createYesNoValidator(), 
						null);
		$this->getInput(AppConfigAttribute::SPHINX_DB_HOST,
						"Sphinx host (leave empty if Sphinx is running on this machine).", 
						null, 
						InputValidator::createHostValidator(), 
						'127.0.0.1');
		$this->getInput(AppConfigAttribute::ENVIRONMENT_PROTOCOL, 
						"Environment protocol - enter http/https", 
						null, 
						null, 
						'http');
						
		$this->saveInput();	
	}
}