<?php
require_once dirname(__DIR__) . '/functions.php';
 /*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */



function getGPODetails($ldap_conn, $dn) {
    try {
        $result = ldap_read($ldap_conn, $dn, "(objectClass=*)", ["*"]);
        if (!$result) {
            throw new Exception(str_replace('{error}', ldap_error($ldap_conn), __('gpo_read_failed')));
        }

        $entries = ldap_get_entries($ldap_conn, $result);
        if ($entries['count'] === 0) {
            throw new Exception(__('gpo_not_found'));
        }

        return $entries[0];
    } catch (Exception $e) {
        error_log(str_replace('{error}', $e->getMessage(), __('gpo_details_exception')));
        throw $e;
    }
}