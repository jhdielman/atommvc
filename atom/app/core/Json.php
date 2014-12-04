<?php

namespace Atom;

class Json implements IRenderable {
    
    protected $content = null;
    
    public function __construct($data) {
        
        $this->content = (object)[
            'data'      => $data,
            'message'   => null,
            'meta'      => ['token' => Security::getAntiCsrfToken()]
        ];
        
        //Enable messages to be returned via JSON and displayed to the user.
        if (SessionHelper::hasMessage()) {
            $this->content->message = (object)[
                'header'    => SessionHelper::getMessageHeader(),
                'messages'  => SessionHelper::getMessages(),
                'severity'  => SessionHelper::getMessageSeverity()
            ];
        }
    }
    
    public function render() {
        $this->filter();
        $response = new Response(json_encode($this->content));
        $response->addHeader("Content-Type", "application/json");
        $response->send();
    }
    
    public function filter() {
        
        //$data = $this->content->data;
        //if($data instanceof IPurifiable) {
        //    $this->content->data = (object) $data->purify();
        //}
    }
}