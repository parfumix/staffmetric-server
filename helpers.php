<?php

if(! function_exists('get_name_from_email')) {
    function get_name_from_email($email_address, $split='@') {
        return ucwords(strtolower(substr($email_address, 0, strripos($email_address, $split))));
    }
}

if(! function_exists('get_domain_from_email')) {
    function get_domain_from_email($email) {
        $email_domain = explode('@', $email);
        $email_domain = $email_domain[1];

        return $email_domain;
    }
}
