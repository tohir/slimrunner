<?php

namespace SlimRunner;

use SlimRunner\FactoryLoader as FactoryLoader;
use SlimRunner\AppConfig as AppConfig;

abstract class SlimRunner
{
    
    /**
     * @var object Slim Object
     */
    private $slim;
    
    /**
     * @var array List of Routes, with Callback, Request Method, Conditionals
     */
    private $routes;
    
    /**
     * @var string $routePrepend
     * Use if you want /[routePrepend]/route/to/item
     */
    protected $routePrepend = '';
    
    /**
     * @var obj $template Template Object
     */
    protected $template;
    
    /**
     * @var string $pageTemplate Path to the Page Template
     */
    private $pageTemplate;
    
    /**
     * @var string $pageTemplate Path to the Layout Template
     */
    private $layoutTemplate;
    
    /**
     * Constructor
     *
     * @param string $configFile Path to .ini config file
     * @param string $writableFolder Path to folder with write permissions
     */
    public function __construct($configFile, $writableFolder)
    {
        AppConfig::load($configFile);
        
        if (!is_writable($writableFolder)) {
            throw new Exception('Folder is not writable');
        }
        
        date_default_timezone_set(AppConfig::get('datetime', 'timezone', 'GMT'));
        
        $this->slim = $this->createSlim();
        
        $this->template = FactoryLoader::load('Template', FALSE, $writableFolder);
        
        $this->init();
    }
    
    /**
     * This function needs to be overridden to create the routes
     */
    abstract protected function init();
    
    /**
     * Invoke the Slim Application
     */
    public function run()
    {
        $this->slim->run();
    }
    
    /**
     * Method to create the Slim Object
     * @return object Slim Object
     */
    private function createSlim()
    {
        return new \Slim\Slim();
    }
    
    /**
     * Needs to be public because of closure
     */
    public function dispatch($method_name, $method_args)
    {
        $request = $this->slim->request();
        $response = $this->slim->response();
        
        if (preg_match('/\A(?:[A-Z]\w+::[A-Z|a-z|_]\w+)\Z/', $method_name)) {
            $parts = explode('::', $method_name);
            
            $object = $this->loadController($parts[0]);
            $method_name = $parts[1];
            
        } else {
            $object =& $this;
        }
        
        $response = call_user_func_array(
            array($object, $method_name.'_'.strtolower($request->getMethod())),
            $method_args
        );
        
        if (!empty($this->layoutTemplate)) {
            $response = $this->template->loadTemplate($this->layoutTemplate, array('content'=>$response));
        }
        
        if (!empty($this->pageTemplate)) {
            $response = $this->template->loadTemplate($this->pageTemplate, array('content'=>$response));
        }
        echo $response;
    }
    
    /**
     * Method to load a controller
     * @param string $controller Controller Name
     * @return object Controller Object
     */
    private function loadController($controller)
    {
        $className = $controller.'Controller';
        
        $class = new $className($this);
        $class->init();
        
        return $class;
    }
    
    
    /**
     * Method to register Application Routes
     * Uses GET by default
     * 
     * @param array $route List of Routes containing: route, accessChecks, function, method, conditional
     * 
     * @example
     * $this->registerRoutes(array(
     *     array('/', NULL, 'home'),
     *     array('/openclipart/:term' , 'loginRequired', 'openclipart'),
     *     array('/openclipart/:term/:page', 'loginRequired:1|anotherCheck', 'openclipart', 'get', array('page' => '\d+'))
     * ));
     *
     */
    protected function registerRoutes($routes)
    {
        if ($this->routes !== null) {
            throw new Exception('Routes have already been registered');
        }
        
        $this->routes = $routes;
        $app = $this; // Needed because '$this' is disallowed in closures
        
        foreach ($routes as $route) {
            list($route_pattern, $accessChecks, $method_name) = $route;
            
            // Defaults to get
            $method_list = isset($route[3]) ? $route[3] : 'get';
            
            // Conditions for route options is optional
            $conditions = isset($route[4]) ? $route[4] : array();
            
            $methods = explode('|', $method_list);
            
            $callback = function() use ($app, $method_name) {
                $app->dispatch($method_name, func_get_args()); // function_args passed to controller via Slim
            };
            
            $accessChecksMethod = function () use ($accessChecks, $app) {
                return $app->runAccessChecks($accessChecks);
            };
            
            foreach ($methods as $method) {
                $this->slim->$method($this->routePrepend . $route_pattern, $accessChecksMethod, $callback)->conditions($conditions);
            }
        }
    }
    
    /**
     * Method to run all the accessChecks for Routes
     * This method has to be public for closure, but the access check methods should be protected
     *
     * Multiple accessChecks are separated by pipes '|', parameters are separated by colons ':'
     * Example loginRequired:1|adminAccess
     *
     * For boolean parameters, use '1' instead of 'true'.
     *
     * Lastly the accesscheck methods in the class should be prepended by accesscheck_
     */
    public function runAccessChecks($accessChecks)
    {
        $accessChecks = explode('|', $accessChecks);
        
        foreach ($accessChecks as $accessCheck)
        {
            $params = explode(':', $accessCheck);
            $method = $params[0]; unset($params[0]);
            
            if (!empty($method)) {
                call_user_func_array(array($this,'accesscheck_'.$method), $params);
            }
        }
        
        return;
    }
    
    /**
     * Method to Redirect to Another Route
     *
     * @param string $redirect Route to Redirect To
     * @param int $status HTTP Status Code
     */
    protected function redirect($redirect, $status=302)
    {
        $this->slim->redirect($redirect, $status);
    }
    
    /**
     * Set ETag HTTP Response Header
     *
     * @param string $value The etag value
     * @param string $type  The type of etag to create; either "strong" or "weak"
     */
    protected function etag($value, $type='strong')
    {
        $this->slim->etag($value);
    }
    
    /**
     * Set Expires HTTP response header
     *
     * @param string|int    $time   If string, a time to be parsed by `strtotime()`;
     *                              If int, a UNIX timestamp;
     */
    protected function expires($time)
    {
        $this->slim->expires($time);
    }
    
    /**
     * Set HTTP Status Code
     *
     * @param int    $statusCode   200, 500, 403, etc
     */
    protected function setStatusCode($statusCode)
    {
        $this->slim->response()->status($statusCode);
    }
    
    /**
     * Method to set the page template
     * @param string $template Path to the page template
     */
    protected function setPageTemplate($template)
    {
        $this->pageTemplate = $template;
    }
    
    /**
     * Method to set the page template
     * @param string $template Path to the page template
     */
    protected function setLayoutTemplate($template)
    {
        $this->layoutTemplate = $template;
    }
    
    /**
     * Method that can be used to set Ajax Response
     * Effectively, just turns off the page and layout template
     */
    protected function setIsAjaxResponse()
    {
        $this->setLayoutTemplate(NULL);
        $this->setPageTemplate(NULL);
    }
    
    /**
     * Method to get a GET Value
     * @param string $name Name of the item
     * @param mixed $default Default value to be used if not set
     */
    protected function getValue($name, $default='')
    {
        if (isset($_GET[$name])) {
            return $_GET[$name];
        } else {
            return $default;
        }
    }
    
    /**
     * Method to get a POST Value
     * @param string $name Name of the item
     * @param mixed $default Default value to be used if not set
     */
    protected function postValue($name, $default='')
    {
        if (isset($_POST[$name])) {
            return $_POST[$name];
        } else {
            return $default;
        }
    }
    
    /**
     * Method to get a $_SESSION Value
     * @param string $name Name of the item
     * @param mixed $default Default value to be used if not set
     */
    protected function sessionValue($name, $default='')
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return $default;
        }
    }
    
    /**
     * Method to set a $_SESSION Value
     * @param string $name Name of the Session Var
     * @param mixed $value Session Var Value
     */
    protected function setSessionValue($name, $value)
    {
        if (!isset($_SESSION)) { session_start(); }
        
        $_SESSION[$name] = $value;
    }
    
    /**
     * Method to unset a $_SESSION Value
     * @param string $name Name of the Session Var
     */
    protected function unsetSessionValue($name)
    {
        if (!isset($_SESSION)) { session_start(); }
        
        unset($_SESSION[$name]);
    }
}

