<?php

namespace Atom;

class ContactModel extends Model {
	
	public $rules = [
		'email' => [
			'rules' => [
				'required',
				'email'
			],
			'label' => 'Email'
		],
		'firstname' => [
			'rules' => [
				'required'
			],
			'label' => 'First name'
		],
		'lastname' => [
			'rules' => [
				'required'
			],
			'label' => 'Last name'
		],
		'description' => [
			'rules' => [
				'required'
			],
			'label' => 'Description'
		]
	];
}