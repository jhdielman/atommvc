<?php

/**
 * AtomMVC: File Class
 * atom/app/lib/File.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

class FileSystem {

    protected $file;

    public $size;

    public $filename;

    public $type;

    public $extension;

    public $path;

    public $exists;

    public function __construct($filename = '') {

        $this->filename = $filename;
    }

    public function open($mode = "r", $useIncludePath = false, $context = null) {

        $filename = $this->filename;
        $file;
        if(is_null($context)) {
            $this->file = fopen($filename, $mode, $useIncludePath);
        } else {
            $this->file = fopen($filename, $mode, $useIncludePath, $context);
        }
        return $this->file;
    }

    public function close() {

    }

    public function save() {

    }

    public function move() {

    }

    public function copy() {

    }

    public function delete() {

    }

    public function exists() {

    }

    public function getCsv() {

    }

    public function putCsv() {

    }

    public function permissions($mode = null) {

        //set: chmod($filename, 0755);
        //get: fileperms($filename);

    }

    public function info() {
        $filename = $this->filename;
        return stat($filename);
    }

    public function getContents($useIncludePath = false, $context = null, $offset = -1, $maxlen = null) {

        $contents = null;
        $filename = $this->filename;

        if(is_int($maxlen)) {
            $contents = file_get_contents($filename, $useIncludePath, $context, $offset, $maxlen);
        } else {
            $contents = file_get_contents($filename, $useIncludePath, $context, $offset);
        }

        return $contents;
    }
}
