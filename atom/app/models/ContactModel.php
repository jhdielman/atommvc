<?php

namespace Atom;

class ContactModel extends Model {
    
    public $fields = [
        'firstname' => '',
        'lastname' => '',
        'email' => '',
        'description' => ''
    ];

    public $rules = [
        'firstname' => [
            'rules' => ['required','max' => 250]
        ],
        'lastname' => [
            'rules' => ['required','max' => 250]
        ],
        'email' => [
            'rules' => ['required','email','max' => 250]
        ],
        'description' => [
            'rules' => ['required','max' => 1000]
        ]
    ];
}