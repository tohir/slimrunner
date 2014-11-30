<?php


/**
 * SlimRunController Class
 *
 * Basically a way of offsetting code from AppRun into controllers
 */
abstract class SlimRunController extends SlimRunner
{
    
    private $slimRunner;
    
    public function __construct(&$slimRunner)
    {
        $this->slimRunner = $slimRunner;
        $this->template = $this->slimRunner->template;
        $this->db = $this->slimRunner->db;
    }
    
    protected function init() {}
    
    /**
     * Method to Redirect to Another Route
     *
     * @param string $redirect Route to Redirect To
     * @param int $status HTTP Status Code
     */
    protected function redirect($redirect, $status=302)
    {
        return $this->slimRunner->redirect($redirect, $status);
    }
    
    /**
     * Set ETag HTTP Response Header
     *
     * @param string $value The etag value
     * @param string $type  The type of etag to create; either "strong" or "weak"
     */
    protected function etag($value, $type='strong')
    {
        return $this->slimRunner->etag($value);
    }
    
    /**
     * Set Expires HTTP response header
     *
     * @param string|int    $time   If string, a time to be parsed by `strtotime()`;
     *                              If int, a UNIX timestamp;
     */
    protected function expires($time)
    {
        return $this->slimRunner->expires($time);
    }
    
    /**
     * Set HTTP Status Code
     *
     * @param int    $statusCode   200, 500, 403, etc
     */
    protected function setStatusCode($statusCode)
    {
        return $this->slimRunner->setStatusCode($statusCode);
    }
    
    /**
     * Method to set the page template
     * @param string $template Path to the page template
     */
    protected function setPageTemplate($template)
    {
        return $this->slimRunner->setPageTemplate($template);
    }
    
    /**
     * Method to set the page template
     * @param string $template Path to the page template
     */
    protected function setLayoutTemplate($template)
    {
        return $this->slimRunner->setLayoutTemplate($template);
    }
    
    /**
     * Method that can be used to set Ajax Response
     * Effectively, just turns off the page and layout template
     */
    protected function setIsAjaxResponse()
    {
        return $this->slimRunner->setIsAjaxResponse();
    }
    
    /**
     * Method to get a GET Value
     * @param string $name Name of the item
     * @param mixed $default Default value to be used if not set
     */
    protected function getValue($name, $default='')
    {
        return $this->slimRunner->getValue($name, $default);
    }
    
    /**
     * Method to get a POST Value
     * @param string $name Name of the item
     * @param mixed $default Default value to be used if not set
     */
    protected function postValue($name, $default='')
    {
        return $this->slimRunner->postValue($name, $default);
    }
    
    /**
     * Method to get a $_SESSION Value
     * @param string $name Name of the item
     * @param mixed $default Default value to be used if not set
     */
    protected function sessionValue($name, $default='')
    {
        return $this->slimRunner->sessionValue($name, $default);
    }
    
    /**
     * Method to set a $_SESSION Value
     * @param string $name Name of the Session Var
     * @param mixed $value Session Var Value
     */
    protected function setSessionValue($name, $value)
    {
        return $this->slimRunner->setSessionValue($name, $value);
    }
    
    /**
     * Method to unset a $_SESSION Value
     * @param string $name Name of the Session Var
     */
    protected function unsetSessionValue($name)
    {
        return $this->slimRunner->unsetSessionValue($name);
    }
}
