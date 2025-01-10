<?php

namespace App\Modules;

use App\Core\Application;
use App\Core\FileManager;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\LinkHelper;
use App\Helpers\TemplateHelper;
use App\UI\AComponent;

/**
 * AGUICore is the lowest level UI element. It is implemented by Modules, Presenters and Components - all UI elements
 * 
 * @author Lukas Velek
 */
abstract class AGUICore {
    protected HttpRequest $httpRequest;
    protected Application $app;
    protected ?APresenter $presenter;
    protected ?AModule $module;

    /**
     * Creates a flash message and returns its HTML code
     * 
     * @param string $type Flash message type (info, success, warning, error)
     * @param string $text Flash message text
     * @param int $flashMessageCount Number of flash messages
     * @param bool $custom True if flash message has custom handler or false if not
     * @param bool $permanent True if the flash message is permanent or false if not
     * @return string HTML code
     */
    protected function createFlashMessage(string $type, string $text, int $flashMessageCount, bool $custom = false, bool $permanent = false, int $autoCloseLengthInSeconds = 5) {
        $fmc = $flashMessageCount . '-' . ($custom ? '-custom' : '') . ($permanent ? '-permanent' : '');
        $removeLink = '<p class="fm-text fm-link" style="cursor: pointer" onclick="closeFlashMessage(\'fm-' . $fmc . '\')">&times;</p>';

        $jsAutoRemoveScript = '<script type="text/javascript">autoHideFlashMessage(\'fm-' . $fmc . '\', ' . $autoCloseLengthInSeconds . ')</script>';

        $code = '<div id="fm-' . $fmc . '" class="row fm-' . $type . '"><div class="col-md"><p class="fm-text">' . $text . '</p></div><div class="col-md-1" id="right">' . ($custom ? '' : ($permanent ? '' : $removeLink)) . '</div><div id="fm-' . $fmc . '-progress-bar" style="position: absolute; left: 0; bottom: 1%; border-bottom: 2px solid black"></div>' . ($custom ? '' : ($permanent ? '' : $jsAutoRemoveScript)) . '</div>';

        return $code;
    }

    /**
     * Sets Application instance
     * 
     * @param Application $application Application instance
     */
    public function setApplication(Application $application) {
        $this->app = $application;
    }

    /**
     * Sets the http request instance
     * 
     * @param HttpRequest $request HttpRequest instance
     */
    public function setHttpRequest(HttpRequest $httpRequest) {
        $this->httpRequest = $httpRequest;
    }

    /**
     * Sets the current Presenter instance
     * 
     * @param APresenter $presenter Current presenter instance
     */
    public function setPresenter(APresenter $presenter) {
        $this->presenter = $presenter;
    }

    /**
     * Sets the current Module instance
     * 
     * @param AModule $module Current module instance
     */
    public function setModule(AModule $module) {
        $this->module = $module;
    }

    /**
     * Returns a template or null
     * 
     * @param string $file Template file path
     * @return TemplateObject|null TemplateObject if the file is found or null
     */
    protected function getTemplate(string $file) {
        if(FileManager::fileExists($file)) {
            $content = FileManager::loadFile($file);
            $template = new TemplateObject($content);

            if(isset($this->presenter) || isset($this->module)) {
                try {
                    $this->checkComponents($template);
                } catch(AException $e) {
                    throw new GeneralException('Could not render template. Reason: ' . $e->getMessage(), $e, false);
                }
            }

            return $template;
        } else {
            return null;
        }
    }

    /**
     * Checks if components exist
     * 
     * @param TemplateObject $template Template
     */
    protected function checkComponents(TemplateObject $template) {
        $components = TemplateHelper::loadComponentsFromTemplateContent($template->getTemplateContent());

        foreach($components as $componentName => $componentAction) {
            if(method_exists($this, $componentAction)) {
                if(isset($_GET['isFormSubmit']) && $_GET['isFormSubmit'] == '1') {
                    $fr = $this->createFormRequest();
                    $component = $this->$componentAction($this->httpRequest, $fr);
                } else {
                    $component = $this->$componentAction($this->httpRequest);
                }

                if($component instanceof AComponent) {
                    $component->setComponentName($componentName);
                    if(isset($this->presenter)) {
                        $component->setPresenter($this->presenter);
                    }
                    $component->setApplication($this->app);
                    $component->startup();

                    $template->setComponent($componentName, $component);
                } else {
                    throw new GeneralException('Method \'' . $this::class . '::' . $componentAction . '()\' does not return a value that implements IRenderable interface.', null, false);
                }
            } else {
                throw new GeneralException('No method \'' . $this::class . '::' . $componentAction . '()\' exists.', null, false);
            }
        }
    }

    /**
     * Creates a FormRequest instance
     * 
     * @return ?FormRequest FormRequest or null
     */
    protected function createFormRequest() {
        if(!empty($_POST)) {
            $values = $this->getPostParams();

            return FormRequest::createFromPostData($values);
        } else {
            return null;
        }
    }

    /**
     * Returns all query params -> the $_GET array but without the 'page' and 'action' parameters.
     * 
     * @return array Query parameters
     */
    protected function getQueryParams() {
        $keys = array_keys($this->httpRequest->query);

        $values = [];
        foreach($keys as $key) {
            if($key == 'page' || $key == 'action') {
                continue;
            }

            $values[$key] = $this->httpGet($key);
        }

        return $values;
    }

    /**
     * Returns all post params -> the $_POST array
     * 
     * @return array POST parameters
     */
    protected function getPostParams() {
        $keys = array_keys($this->httpRequest->post);

        $values = [];
        foreach($keys as $key) {
            $values[$key] = $this->httpPost($key);
        }

        return $values;
    }

    /**
     * Returns escaped value from $_GET array. It can also throw an exception if the value is not provided.
     * 
     * @param string $key Array key
     * @param bool $throwException True if exception should be thrown or false if not
     * @return mixed Escaped value or null
     */
    protected function httpGet(string $key, bool $throwException = false) {
        if(array_key_exists($key, $this->httpRequest->query)) {
            if(!is_array($this->httpRequest->query[$key])) {
                return htmlspecialchars($this->httpRequest->query[$key]);
            } else {
                $tmp = [];
                foreach($this->httpRequest->query[$key] as $q) {
                    if(!is_array($q)) {
                        $tmp[] = htmlspecialchars($q);
                    } else {
                        foreach($q as $_q) {
                            $tmp[] = htmlspecialchars($_q);
                        }
                    }
                }
                return $tmp;
            }
        } else {
            if($throwException) {
                throw new RequiredAttributeIsNotSetException($key, '$_GET');
            } else {
                return null;
            }
        }
    }

    /**
     * Returns escaped value from $_POST array. It can also throw an exception if the value is not provided.
     * 
     * @param string $key Array key
     * @param bool $throwException True if exception should be thrown or false if not
     * @return mixed Escaped value or null
     */
    protected function httpPost(string $key, bool $throwException = false) {
        if(array_key_exists($key, $this->httpRequest->post)) {
            return htmlspecialchars($this->httpRequest->post[$key]);
        } else {
            if($throwException) {
                throw new RequiredAttributeIsNotSetException($key, '$_POST');
            } else {
                return null;
            }
        }
    }

    /**
     * Returns data from the $_SESSION by the key
     * 
     * @param string $key Data key
     * @return mixed Data value or null
     */
    protected function httpSessionGet(string $key) {
        if(isset($_SESSION[$key])) {
            return $_SESSION[$key];
        } else {
            return null;
        }
    }

    /**
     * Sets a value to the $_SESSION
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     */
    protected function httpSessionSet(string $key, mixed $value) {
        if($value === null) {
            unset($_SESSION[$key]);
        } else {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Creates a full URL with parameters and returns it as a string
     * 
     * @param string $modulePresenter Module and presenter name
     * @param string $action Action name
     * @param array $params Custom URL params
     * @return string URL as string
     */
    public function createFullURLString(string $modulePresenter, string $action, array $params = []) {
        $urlParts = $this->createFullURL($modulePresenter, $action, $params);

        return $this->convertArrayUrlToStringUrl($urlParts);
    }

    /**
     * Creates a full URL with parameters
     * 
     * @param string $modulePresenter Module and presenter name
     * @param string $action Action name
     * @param array $params Custom URL params
     * @return array URL
     */
    public function createFullURL(string $modulePresenter, string $action, array $params = []) {
        $url = ['page' => $modulePresenter, 'action' => $action];

        return array_merge($url, $params);
    }

    /**
     * Converts URL defined in an array to string format
     * 
     * @param array $url Array URL
     * @return string URL as string
     */
    public function convertArrayUrlToStringUrl(array $url) {
        return LinkHelper::createUrlFromArray($url);
    }
}

?>