<?php

namespace Atom;

class View implements IRenderable {
    
    protected $viewPath;
    protected $viewName;
    protected $model;
    protected $templateName;
    protected $defaultTemplate = '_wrapper';

    public function __construct($name = null, $model = null, $templateName = null) {
        $this->viewName = ($name ?: 'index');
        $this->model = $model;
        $this->templateName = ($templateName ?: $this->defaultTemplate);
    }

    public function render() {
        
        $this->filter();
        
        $name = $this->viewName;
        $model = $this->model;
        $viewPath = $this->getViewPath();
        $viewFile = $viewPath.$name.PHPEXT;
        $template = $this->templateName ?: $this->defaultTemplate;
        $templateFile = is_null($template) ? '' : ATOM_TEMPLATE_PATH.$template.PHPEXT;

        if(is_dir($viewPath) && is_file($viewFile)) {
            if(is_file($templateFile)) {
                include $templateFile;
            } else {
                include $viewFile;
            }
        } else {
            Response::redirect(404);
        }
    }
    
    public function filter() {
        //TODO...
    }

    protected function getViewPath() {

        if(!$this->viewPath) {

            $viewPath = ATOM_VIEW_PATH;
            $viewFolder = Request::segment(0) ?: 'home';
            $viewPath .= (new String($viewFolder))
                ->toLower()
                ->replace('-','')
                ->append('/')
                ->out();
                
            $this->viewPath = $viewPath;
        }
        return $this->viewPath;
    }
}
