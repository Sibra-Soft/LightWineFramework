<?php
namespace LightWine;

use LightWine\Core\Services\ServerService;
use LightWine\Core\Services\RequestService;
use LightWine\Core\Helpers\Helpers;
use LightWine\Core\Helpers\StringHelpers;

use \Exception;
use \TypeError;
use \Error;
use LightWine\Core\HttpResponse;

class Bootloader {
	private function Autoloader(){
        spl_autoload_register(function($class){
            $type = "class";

            if(strpos(strtolower($class), "model") !== false){
                $type = "model";        // Load models
            }elseif(strpos(strtolower($class), "enum") !== false){
                $type = "enum";         // Load enums
            }elseif(strpos(strtolower($class), "interfaces") !== false){
                $type = "interface";    // Load interfaces
            }

            $includeFile = __DIR__.str_replace('\\', '/', str_replace("LightWine", "", $class)).'.'.$type.'.php';
            require_once ($includeFile);
        });
    }

    /**
     * Used for error handling of the framework
     * @param mixed $errno The number of the current error
     * @param mixed $errstr The description of the error
     * @param mixed $errfile The file where the error occured
     * @param mixed $errline Linenumber of the file where the error occured
     */
    public function SetErrorHandler($errno, $errstr, $errfile, $errline){

    }

    /**
     * Used for exception handeling of the total framework
     * @param TypeError|Exception|Error $exception The thrown exception
     */
    public function SetExceptionHandler($exception){
        $view = Helpers::GetFileContent("~/src/Views/Exception.tpl");

        $message = StringHelpers::SplitString($exception->getMessage(), "#", 0);
        $specifiedSource = StringHelpers::SplitString($exception->getMessage(), "#", 1);

        if(StringHelpers::IsNullOrWhiteSpace($specifiedSource)){
            $specifiedSource = $exception->getCode();
        }

        $view = str_replace("{{source_file_line}}", $exception->getLine(), $view);
        $view = str_replace("{{source_file}}", $exception->getFile(), $view);
        $view = str_replace("{{error_message}}", $message, $view);
        $view = str_replace("{{source}}", $specifiedSource, $view);

        HttpResponse::SetContentType("text/html");
        HttpResponse::SetData($view);
    }

    /**
     * Adds the specified configuration file to the project
     * @param string $file The path to the configuration file
     */
    public function AddConfigurationFile(string $file){
        $GLOBALS["ConfigFile"] = $file;
    }

    /**
     * Main bootloader function, this function gets the request and starts the internal server
     */
    public function Run(){
        set_error_handler(array($this, 'SetErrorHandler'));
        set_exception_handler(array($this, "SetExceptionHandler"));

        $this->Autoloader();

        $request = new RequestService();
        $requestModel = $request->GetRouteBasedOnRequestUrl();

        $server = new ServerService($requestModel);
        $responseModel = $server->Start();

        HttpResponse::SetContentType("text/html");
        HttpResponse::SetData($responseModel->Page->Content);
    }
}
?>