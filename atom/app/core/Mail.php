<?php

namespace Atom;

class MailHelper {
    public static function getUsePearMail() {
        return MAIL_USE_PEAR_MAIL;
    }
    
    public static function getHost() {
        return MAIL_HOST;
    }
    
    public static function getPort() {
        return MAIL_PORT;
    }
    
    public static function getUsername() {
        return MAIL_USERNAME;
    }
    
    public static function getPassword() {
        return MAIL_PASSWORD;
    }
    
    public static function getFrom() {
        RETURN MAIL_FROM;
    }
    
    public static function getSmtp($persist = false) {
        $params = [
            "host"      => static::getHost(),
            "port"      => static::getPort(),
            "persist"   => $persist];

        if (!empty(static::getUsername()) && !empty(static::getPassword())) {
            $params = array_merge($params, [
                "auth"      => true,
                "username"  => static::getUsername(),
                "password"  => static::getPassword()]);
        }
        
        $smtp = \Mail::factory("smtp", $params);
        
        return $smtp;
    }
    
    public static function send($to, $subject, $body, Array $headers = []) {
        
        require_once "Mail.php";
        
        $headers = array_merge($headers, [
            "From"      => static::getFrom(),
            "To"        => $to,
            "Subject"   => $subject]);
        
        if (static::getUsePearMail() == true) {
            return static::sendViaPearMail($to, $subject, $body, $headers);
        } else {
            return static::sendViaMail($to, $subject, $body, $headers);          
        }
    }
    
    private static function sendViaPearMail($to, $subject, $body, Array $headers = []) {
        $smtp = static::getSmtp(false);
        $mail = $smtp->send($to, $headers, $body);
        
        if (\PEAR::isError($mail)) {
            error_log("ERROR! Failure sending mail to \"$to\" with subject \"$subject\": " . $mail->getMessage());
            return false;
        }
        
        return true;
    }
    
    private static function sendViaMail($to, $subject, $body, Array $headers = []) {
        $joinedHeaders = "";
        
        if (!empty($headers)) {
            foreach ($headers as $key => $value) {
                $joinedHeaders .= "$key: $value\r\n";
            }
        }
        
        try {
            mail($to, $subject, $body, $joinedHeaders);
        } catch (Exception $ex) {
            error_log("ERROR! Failure sending mail to \"$to\" with subject \"$subject\": " . $ex->getMessage());
            return false;
        }
        
        return true;
    }
}