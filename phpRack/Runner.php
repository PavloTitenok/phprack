<?php
/**
 * phpRack: Integration Testing Framework
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt. It is also available 
 * through the world-wide-web at this URL: http://www.phprack.com/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@phprack.com so we can send you a copy immediately.
 *
 * @copyright Copyright (c) phpRack.com
 * @version $Id$
 * @category phpRack
 */

/**
 * @see phpRack_Test
 */
require_once PHPRACK_PATH . '/Test.php';

/**
 * @see phpRack_Runner_AuthResult
 */
require_once PHPRACK_PATH . '/Runner/AuthResult.php';

/**
 * Run all tests together, or one by one
 *
 * First you should create an instance of this class, providing it an array
 * of options. Then you can either run individual test or all tests in a 
 * test suite:
 *
 * <code>
 * $runner = new phpRack_Runner(array('dir'=>'/path/to/my-tests'));
 * echo $runner->runSuite();
 * </code>
 *
 * This code will give you a plain-text report of all tests in your collection,
 * executed and logged.
 *
 * @package Tests
 */
class phpRack_Runner
{
    
    /**
     * COOKIE name
     *
     * @see isAuthenticated()
     */
    const COOKIE_NAME = 'phpRack_auth';
    
    /**
     * COOKIE lifetime in seconds
     *
     * We set to 30 days, which equals to 30 * 24 * 60 * 60 = 2592000
     *
     * @see isAuthenticated()
     */
    const COOKIE_LIFETIME = 2592000;
    
    /**
     * Form param names
     *
     * @see isAuthenticated()
     */
    const POST_LOGIN = 'login';
    const POST_PWD = 'password';
    
    /**
     * This is how you should name your test files, if you want
     * them to be found by the Runner
     *
     * @var string
     * @see getTests()
     */
    const TEST_PATTERN = '/(\w+Test)\.php$/i';
    
    /**
     * List of options, which are changeable
     *
     * @var array
     * @see __construct()
     */
    protected $_options = array(
        'dir' => null,
        'auth' => null,
        'htpasswd' => null,
    );
    
    /**
     * Auth result, if authentication was already performed
     *
     * @var phpRack_Runner_AuthResult
     * @see authenticate()
     */
    protected $_authResult = null;
    
    /**
     * Construct the class
     *
     * @param array Options to set to the class
     * @return void
     * @throws Exception If an option is invalid
     * @see $this->_options
     */
    public function __construct(array $options) 
    {
        foreach ($options as $option=>$value) {
            if (!array_key_exists($option, $this->_options)) {
                throw new Exception("Option '{$option}' is not recognized");
            }
            $this->_options[$option] = $value;
        }
    }
    
    /**
     * Authenticate the user before running any tests
     *
     * @param string Login of the user
     * @param string Secret password of the user
     * @param boolean Defines whether second argument is password or it's hash
     * @return phpRack_Runner_AuthResult
     * @see $this->_authResult
     */
    public function authenticate($login, $password, $isHash = false)
    {
        // if it's already authenticated, just return it
        if (!is_null($this->_authResult)) {
            return $this->_authResult;
        }
            
        // make sure that we're working with HASH
        $hash = ($isHash) ? $password : md5($password);
        
        switch (true) {
            // plain authentication by login/password
            // this option is set by default to NULL, here we validate that
            // it was changed to ARRAY
            case is_array($this->_options['auth']):
                $auth = $this->_options['auth'];
                if ($auth['username'] != $login) {
                    return $this->_validated(false, 'Invalid login');
                }
                if (md5($auth['password']) != $hash) {
                    return $this->_validated(false, 'Invalid password');
                }
                return $this->_validated(true);
        
            // list of login/password provided in file
            // this option is set by default to NULL, here we just validate
            // that it contains a name of file
            case is_string($this->_options['htpasswd']):
                require_once PHPRACK_PATH . '/Adapters/File.php';
                $file = phpRack_Adapters_File::factory($this->_options['htpasswd'])->getFileName();
            
                $fileContent = file($file);
                foreach ($fileContent as $line) {
                    list($lg, $psw) = explode(':', $line, 2);
                    /* Just to make sure we don't analyze some whitespace */
                    $lg = trim($lg);
                    $psw = trim($psw);
                    if (($lg == $login) && ($psw == $hash)) {
                        return $this->_validated(true);
                    }
                }
                return $this->_validated(false, 'Invalid login credentials provided');
                
            // authenticated TRUE, if no authentication required
            default:
                return $this->_validated(true);
        }
    }
    
    /**
     * Checks whether user is authenticated before running any tests
     *
     * @return boolean
     * @see $this->_authResult
     */
    public function isAuthenticated() 
    {
        if (!is_null($this->_authResult)) {
            return $this->_authResult->isValid();
        }
        
        // global variables, in case they are not declared as global yet
        global $_COOKIE;
        
        // there are a number of possible authentication scenarios
        switch (true) {
            // login/password are provided in HTTP request, through POST
            // params. we should save them in COOKIE in order to avoid
            // further login requests.
            case array_key_exists(self::POST_LOGIN, $_POST) && 
            array_key_exists(self::POST_PWD, $_POST):
                $login = $_POST[self::POST_LOGIN];
                $hash = md5($_POST[self::POST_PWD]);
                setcookie(
                    self::COOKIE_NAME, // name of HTTP cookie
                    $login . ':' . $hash, // hashed form of login and pwd
                    time() + self::COOKIE_LIFETIME // cookie expiration date
                );
                break;
                
            // this is CLI environment, not web -- we don't require any
            // authentication
            case $this->isCliEnvironment():
                return $this->_validated(true)->isValid();
                
            // we already have authentication information in COOKIE, we just
            // need to parse it and validate
            case array_key_exists(self::COOKIE_NAME, $_COOKIE):
                list($login, $hash) = explode(':', $_COOKIE[self::COOKIE_NAME]);
                break;
            
            // no authinfo, chances are that site is not protected
            default:
                $login = $hash = false;
                break;
        }
        
        return $this->authenticate($login, $hash, true)->isValid();
    }
    
    /**
     * Get current auth result, if it exists
     *
     * @return phpRack_Runner_AuthResult
     * @see $this->_authResult
     * @throws Exception If the result is not set yet
     */
    public function getAuthResult() 
    {
        if (!isset($this->_authResult)) {
            throw new Exception("AuthResult is not set yet, use authenticate() before");
        }
        return $this->_authResult;
    }
    
    /**
     * We're running the tests in CLI environment?
     *
     * @return boolean
     * @see isAuthenticated()
     */
    public function isCliEnvironment() 
    {
        global $_SERVER;
        return empty($_SERVER['DOCUMENT_ROOT']);
    }
    
    /**
     * Get tests location directory
     *
     * @return string
     * @throws Exception If directory is absent
     * @see $this->_options
     * @see getTests()
     */
    public function getDir() 
    {
        $dir = $this->_options['dir'];
        if (!file_exists($dir)) {
            throw new Exception("Test directory '{$dir}' is not found");
        }
        return realpath($dir);
    }
    
    /**
     * Get full list of tests, in array
     *
     * @return phpRack_Test[]
     */
    public function getTests() 
    {
        $tests = array();
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->getDir())) as $file) {
            if (!preg_match(self::TEST_PATTERN, $file->getFilename())) {
                continue;
            }
                
            $tests[] = phpRack_Test::factory(strval($file), $this);
        }
        return $tests;
    }
    
    /**
     * Run all tests and return a text report about their execution
     *
     * @return string
     * @see $this->getTests()
     * @see $this->run()
     */
    public function runSuite() 
    {
        $tests = $this->getTests();
        $report = '';
        $success = true;
        foreach ($tests as $test) {
            $result = $test->run();
            $report .= sprintf(
                "%s\n%s: %s, %0.3fsec\n",
                $result->getPureLog(),
                $test->getLabel(),
                $result->wasSuccessful() ? phpRack_Test::OK : phpRack_Test::FAILURE,
                $result->getDuration()
            );
            $success &= $result->wasSuccessful();
        }
        $report .= "PHPRACK SUITE: " . ($success ? phpRack_Test::OK : phpRack_Test::FAILURE) . "\n";
        return $report;
    }
    
    /**
     * Run one test and return JSON result
     *
     * @param string Test file name (absolute name of PHP file)
     * @param string Unique token to return back, if required
     * @return string JSON
     * @throws Exception
     */
    public function run($fileName, $token = 'token') 
    {
        if (!$this->isAuthenticated()) {
            //TODO: handle situation when login screen should appear
            throw new Exception("Authentication failed, please login first");
        }
        $test = phpRack_Test::factory($fileName, $this);
        
        $result = $test->run();
        return json_encode(
            array(
                'success' => $result->wasSuccessful(),
                'log' => $result->getLog(),
                PHPRACK_AJAX_TOKEN => $token,
                'options' => $test->getAjaxOptions()
            )
        );
    }
    
    /**
     * Save and return an AuthResult
     *
     * @param boolean Success/failure of the validation
     * @param string Optional error message
     * @return phpRack_Runner_AuthResult
     * @see authenticate()
     */
    protected function _validated($result, $message = null) 
    {
        return $this->_authResult = new phpRack_Runner_AuthResult($result, $message);
    }
    
}
