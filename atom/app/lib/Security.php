<?php

/**
 * AtomMVC: Security Class
 * atom/app/lib/Security.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

use \HTMLPurifier_Config;
use \HTMLPurifier;

class Security {
	
	protected static $enableAntiCsrf = true;
    protected static $antiCsrfHash = '';
    protected static $antiCsrfExpire = 60;
    protected static $antiCsrfTokenName = 'atomCsrfToken';
    protected static $antiCsrfCookieName = 'atomCsrfToken';
    protected static $purifier = null;
	protected static $purifierConfig = null;
    protected static $allowed = array(
        'img[src|alt|title|width|height|style|data-mce-src|data-mce-json]',
        'figure', 'figcaption',
        'video[src|type|width|height|poster|preload|controls]', 'source[src|type]',
        'a[href|target]',
        'iframe[width|height|src|frameborder|allowfullscreen]',
        'strong', 'b', 'i', 'u', 'em', 'br', 'font',
        'h1[style]', 'h2[style]', 'h3[style]', 'h4[style]', 'h5[style]', 'h6[style]',
        'p[style]', 'div[style]', 'center', 'address[style]',
        'span[style]', 'pre[style]',
        'ul', 'ol', 'li',
        'table[width|height|border|style]', 'th[width|height|border|style]',
        'tr[width|height|border|style]', 'td[width|height|border|style]',
        'hr'
    );
    
    public static function initialize() {
		
		// Is CSRF protection enabled?
		if (static::$enableAntiCsrf) {

			// CSRF config
			foreach (array('antiCsrfExpire', 'antiCsrfTokenName', 'antiCsrfCookieName') as $key) {

				if (false !== ($val = Config::get('master',$key))) {

					static::${$key} = $val;
				}
			}

			// Append application specific cookie prefix
			if (Config::get('master','cookiePrefix')) {

				static::$antiCsrfCookieName = Config::get('master','cookiePrefix').static::$antiCsrfCookieName;
			}
		}
    }
	
	public static function antiCsrfVerified() {
		
		$verified = false;
		
		// If it's not a POST request we will set the CSRF cookie
		if (Request::isGet() || static::antiCsrfTokensMatch()) {

			$verified = true;
		}

		// We kill this since we're done and we don't want to
		// polute the _POST array
		Input::clearPost(static::getAntiCsrfTokenName());

		// Nothing should last forever
		Cookie::clear(static::getAntiCsrfCookiName());

		static::setAntiCsrfCookie();
		
		return $verified;

        //log_message('debug', 'CSRF token verified');
	}
	
	protected static function antiCsrfTokensMatch() {

		$tokensExist = Input::hasPost(static::getAntiCsrfTokenName()) && Cookie::hasValue(static::getAntiCsrfCookiName());
		$tokensMatch = Input::post(static::getAntiCsrfTokenName()) == Cookie::value(static::getAntiCsrfCookiName());
		return $tokensExist && $tokensMatch;
	}
    
    public static function setAntiCsrfCookie() {
        
        $minutes = (time() / 60) + static::getAntiCsrfExpire();
        
		$secureCookie = (Config::get('master','cookieSecure') === true) ? true : false;
		
		$secureCookie = $secureCookie && Request::isSecure();
		
		static::setAntiCsrfToken();
        
		Cookie::set(
			static::getAntiCsrfCookiName(),
			static::getAntiCsrfToken(),
			$minutes,
			Config::get('master','cookiePath'),
			Config::get('master','cookieDomain'),
			$secureCookie);

        //log_message('debug', "CRSF cookie Set");
    }
    
    protected static function setAntiCsrfToken() {
		
        static::$antiCsrfHash = Hash::sha512(uniqid(rand(), true),false);
    }
	
	public static function getAntiCsrfToken() {
		
        return static::$antiCsrfHash;
    }
	
	public static function getAntiCsrfTokenName() {
		
		return static::$antiCsrfTokenName;
	}
	
	public static function getAntiCsrfCookiName() {
		
		return static::$antiCsrfCookieName;
	}
	
	public static function getAntiCsrfExpire() {
		
		return static::$antiCsrfExpire;
	}
	
	public static function antiCsrfField() {
		
		$type = 'hidden';
		$name = static::getAntiCsrfTokenName();
		$value = static::getAntiCsrfToken();
		$attr = implode(' ',array(
			"type=\"$type\"",
			"name=\"$name\"",
			"value=\"$value\""
		));
		return "<input $attr>";
	}
    
    public static function purify($html) {
		
        return static::htmlPurifier()->purify($html);
    }
	
	public static function purifyArray($htmlArray = []) {
		
		return static::htmlPurifier()->purifyArray($htmlArray);
	}
	
	protected static function getAllowed() {
		return static::$allowed;
	}
	
	protected static function htmlPurifier() {
		
		if(!static::$purifier) {
			static::initializePurifier();
		}
		
		return static::$purifier;
	}
	
    protected static function initializePurifier(){
        $config = static::getPurifierConfig();
        static::$purifier = new HTMLPurifier($config);
    }
	
	protected static function getPurifierConfig() {
		
		if(!static::$purifierConfig) {
			$config = HTMLPurifier_Config::createDefault();
			$config->set('HTML.Doctype', Config::get('master', 'doctype'));
			$config->set('CSS.AllowTricky', true);
			$config->set('Cache.SerializerPath', ATOM_CACHE_PATH.'htmlpurifier');
	
			// Allow iframes from:
			// o YouTube.com
			// o Vimeo.com
			$config->set('HTML.SafeIframe', true);
			$config->set('URI.SafeIframeRegexp', '%^(http:|https:)?//(www.youtube(?:-nocookie)?.com/embed/|player.vimeo.com/video/)%');
	
			$config->set('HTML.Allowed', implode(',', static::getAllowed()));
	
			// Set some HTML5 properties
			$config->set('HTML.DefinitionID', 'html5-definitions'); // unqiue id
			$config->set('HTML.DefinitionRev', 1);
	
			if ($def = $config->maybeGetRawHTMLDefinition()) {
				// http://developers.whatwg.org/sections.html
				$def->addElement('section', 'Block', 'Flow', 'Common');
				$def->addElement('nav',     'Block', 'Flow', 'Common');
				$def->addElement('article', 'Block', 'Flow', 'Common');
				$def->addElement('aside',   'Block', 'Flow', 'Common');
				$def->addElement('header',  'Block', 'Flow', 'Common');
				$def->addElement('footer',  'Block', 'Flow', 'Common');
	
				// Content model actually excludes several tags, not modelled here
				$def->addElement('address', 'Block', 'Flow', 'Common');
				$def->addElement('hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common');
	
				// http://developers.whatwg.org/grouping-content.html
				$def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
				$def->addElement('figcaption', 'Inline', 'Flow', 'Common');
	
				// http://developers.whatwg.org/the-video-element.html#the-video-element
				$def->addElement('video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', array(
					'src' => 'URI',
					'type' => 'Text',
					'width' => 'Length',
					'height' => 'Length',
					'poster' => 'URI',
					'preload' => 'Enum#auto,metadata,none',
					'controls' => 'Bool',
				));
				$def->addElement('source', 'Block', 'Flow', 'Common', array(
					'src' => 'URI',
					'type' => 'Text',
				));
	
				// http://developers.whatwg.org/text-level-semantics.html
				$def->addElement('s',    'Inline', 'Inline', 'Common');
				$def->addElement('var',  'Inline', 'Inline', 'Common');
				$def->addElement('sub',  'Inline', 'Inline', 'Common');
				$def->addElement('sup',  'Inline', 'Inline', 'Common');
				$def->addElement('mark', 'Inline', 'Inline', 'Common');
				$def->addElement('wbr',  'Inline', 'Empty', 'Core');
	
				// http://developers.whatwg.org/edits.html
				$def->addElement('ins', 'Block', 'Flow', 'Common', array('cite' => 'URI', 'datetime' => 'CDATA'));
				$def->addElement('del', 'Block', 'Flow', 'Common', array('cite' => 'URI', 'datetime' => 'CDATA'));
	
				// TinyMCE
				$def->addAttribute('img', 'data-mce-src', 'Text');
				$def->addAttribute('img', 'data-mce-json', 'Text');
	
				// Others
				$def->addAttribute('iframe', 'allowfullscreen', 'Bool');
				$def->addAttribute('table', 'height', 'Text');
				$def->addAttribute('td', 'border', 'Text');
				$def->addAttribute('th', 'border', 'Text');
				$def->addAttribute('tr', 'width', 'Text');
				$def->addAttribute('tr', 'height', 'Text');
				$def->addAttribute('tr', 'border', 'Text');
			}
			
			static::$purifierConfig = $config;
		}
		
		return static::$purifierConfig;
	}
}