<?php

namespace Controller;

class Controller{
    var $controllerName;
    var $controllerMethod;

    public function getControllerAttribute(){
        return [
            "Controller Name" => $this->controllerName,
            "Method" => $this->controllerMethod,
        ];
    }
}