<?php

namespace App\UI\ExceptionPage;

use App\Core\Application;
use App\Core\Http\HttpRequest;
use App\Exceptions\GeneralException;
use App\Modules\AGUICore;
use App\UI\IRenderable;
use Exception;

/**
 * ExceptionPage is used when an exception occurs during application rendering.
 * It contains information about the exception thrown as well as the stack trace.
 * 
 * @author Lukas Velek
 */
class ExceptionPage extends AGUICore implements IRenderable {
    private ?Exception $exception;

    /**
     * Class constructor
     * 
     * @param Application $app Application instance
     * @param HttpRequest $httpRequest HttpRequest instance
     */
    public function __construct(
        Application $app,
        HttpRequest $httpRequest
    ) {
        parent::setApplication($app);
        parent::setHttpRequest($httpRequest);

        $this->exception = null;
    }

    /**
     * Sets the exception
     * 
     * @param Exception $e Exception
     */
    public function setException(Exception $e) {
        $this->exception = $e;
    }

    public function render() {
        $template = $this->getTemplate(__DIR__ . '/default.html');

        if($template === null) {
            throw new GeneralException('Template file could not be found.');
        }

        $template->sys_app_name = 'SkyDocu';
        $template->sys_page_content = $this->renderException();

        return $template->render()->getRenderedContent();
    }

    /**
     * Renders the exception as well as the stack trace
     * 
     * @return string Rendered template content
     */
    private function renderException(): string {
        $exceptionName = $this->exception::class . ': ' . $this->exception->getMessage();
        $exceptionFile = $this->exception->getFile() . ' on line ' . $this->exception->getLine();
        $exceptionTrace = [];
        
        foreach($this->exception->getTrace() as $trace) {
            $exceptionTrace[] = [
                'file' => $trace['file'] . ' on line ' . $trace['line'],
                'function' => $trace['class'] . '::' . $trace['function'] . '()'

            ];
        }

        $exceptionTraceString = '';
        $i = 0;
        foreach($exceptionTrace as $trace) {
            $exceptionTraceString .= '#' . $i . ' ' . $trace['function'] . '<br><span id="exception-trace-file">' . $trace['file'] . '</span><br><br>';
            $i++;
        }

        $template = $this->getTemplate(__DIR__ . '/exception.html');
        $template->exception_name = $exceptionName;
        $template->exception_file = $exceptionFile;
        $template->exception_trace = $exceptionTraceString;
        
        return $template->render()->getRenderedContent();
    }
}

?>