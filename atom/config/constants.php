<?php

/**
 * AtomMVC: Constants
 * atom/config/constants.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 *
 */

// To thwart confusion, let's start at the top with the Atom Base Path
defined('ATOM_BASE_PATH')       || define('ATOM_BASE_PATH',    realpath(dirname(__DIR__)).'/');

// The rest should look nice an orderly... like so
defined('ATOM_APP_PATH')        || define('ATOM_APP_PATH',          ATOM_BASE_PATH.'app/');
defined('ATOM_CONFIG_PATH')     || define('ATOM_CONFIG_PATH',       ATOM_BASE_PATH.'config/');

defined('ATOM_API_PATH')        || define('ATOM_API_PATH',          ATOM_APP_PATH.'api/');
defined('ATOM_CACHE_PATH')      || define('ATOM_CACHE_PATH',        ATOM_APP_PATH.'cache/');
defined('ATOM_CLIENT_PATH')     || define('ATOM_CLIENT_PATH',       ATOM_APP_PATH.'client/');
defined('ATOM_CONTROLLER_PATH') || define('ATOM_CONTROLLER_PATH',   ATOM_APP_PATH.'controllers/');
defined('ATOM_CORE_PATH')       || define('ATOM_CORE_PATH',         ATOM_APP_PATH.'core/');
defined('ATOM_ERROR_PATH')      || define('ATOM_ERROR_PATH',        ATOM_APP_PATH.'errors/');
defined('ATOM_MODEL_PATH')      || define('ATOM_MODEL_PATH',        ATOM_APP_PATH.'models/');
defined('ATOM_TEMPLATE_PATH')   || define('ATOM_TEMPLATE_PATH',     ATOM_APP_PATH.'templates/');
defined('ATOM_VENDOR_PATH')     || define('ATOM_VENDOR_PATH',       ATOM_APP_PATH.'vendor/');
defined('ATOM_VIEW_PATH')       || define('ATOM_VIEW_PATH',         ATOM_APP_PATH.'views/');

// httpdocs paths
defined('HTTPDOCS_BASE_PATH')   || define('HTTPDOCS_BASE_PATH',     realpath(dirname(__DIR__).'/../httpdocs/') . '/');
defined('HTTPDOCS_ASSETS_PATH') || define('HTTPDOCS_ASSETS_PATH',   HTTPDOCS_BASE_PATH.'assets/');

// File extentions
defined('PHPEXT')               || define('PHPEXT',                 '.php');

//General
defined('NO_DATA_TEXT')         || define('NO_DATA_TEXT', '--');
defined('UPLOAD_MAX_SIZE_MB')   || define('UPLOAD_MAX_SIZE_MB', 500);

//Virtual Paths
defined('ATOM_ASSETS_VPATH')    || define('ATOM_ASSETS_VPATH',      '/assets/');
