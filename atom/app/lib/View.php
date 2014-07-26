<?php

namespace Atom;

class View {
	
	protected $defaultTemplate = '_wrapper';
		
	protected $viewPath;
	
	public function __construct($name = null, $model = null, $template = null) {
		
		$rendered = false;
		$name = $name ?: 'index';
		$viewPath = $this->getViewPath();
		$viewFile = $viewPath.$name.PHPEXT;
		$template = $template ?: $this->defaultTemplate;
		$templateFile = is_null($template) ? '' : ATOM_TEMPLATE_PATH.$template.PHPEXT; 

		if(is_dir($viewPath) && is_file($viewFile)) {
			if(is_file($templateFile)) {
				include $templateFile;
			} else {
				include $viewFile;
			}
		} else {
			Request::redirect(404);
		}
		
		return $this;
	}
	
	public static function render($name = null, $model = null, $template = null) {
		
		return new static($name, $model, $template);
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
