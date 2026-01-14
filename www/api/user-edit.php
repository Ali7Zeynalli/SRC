<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */
session_start();
require_once(__DIR__ . '/../includes/functions.php');

header('Content-Type: application/json');

if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    echo json_encode(['error' => __('user_unauthorized')]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['username'])) {
        throw new Exception(__('user_username_required'));
    }

    $ldap_conn = getLDAPConnection();
    $username = $data['username'];
    
    // Get config for base_dn
    $config = require(__DIR__ . '/../config/config.php');
    $base_dn = $config['ad_settings']['base_dn'];
    
    // Get user DN
    $filter = "(&(objectClass=user)(sAMAccountName=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . "))";
    $result = ldap_search($ldap_conn, $base_dn, $filter);
    
    if (!$result || ldap_count_entries($ldap_conn, $result) !== 1) {
        throw new Exception(__('user_not_found'));
    }

    $entry = ldap_get_entries($ldap_conn, $result)[0];
    $userDN = $entry['dn'];

    // Prepare update data
    $updateData = [];
    $deleteData = []; // Boş dəyərlər üçün
    $logDetails = []; // Logging üçün

    // Helper function to handle empty values
    $processField = function($value, $field) use (&$updateData, &$deleteData, &$logDetails, $entry) {
        if ($value === '') {
            // Yalnız atribut mövcud olduqda silməyə çalışırıq
            $fieldLower = strtolower($field);
            if (isset($entry[$fieldLower]) && !empty($entry[$fieldLower])) {
                $deleteData[$field] = [];
                $logDetails[] = str_replace('{field}', $field, __('user_field_cleared'));
            }
        } else if ($value !== null) {
            $updateData[$field] = [$value];
            $logDetails[] = str_replace('{field}', $field, __('user_field_updated'));
        }
    };

    // Process each field
    $processField($data['displayName'], 'displayname');
    $processField($data['email'], 'mail');
    $processField($data['title'], 'title');
    $processField($data['phone'], 'telephonenumber');
    $processField($data['mobile'], 'mobile');
    $processField($data['department'], 'department');
    $processField($data['description'], 'description');

    // Handle password options if provided
    if (isset($data['never_expires'])) {
        $userInfo = getUserInfo($ldap_conn, $username);
        $currentUAC = intval($userInfo['useraccountcontrol'][0]);
        
        if ($data['never_expires']) {
            $newUAC = $currentUAC | 65536; // DONT_EXPIRE_PASSWORD flag
            $logDetails[] = __('user_password_never_expires_set');
        } else {
            $newUAC = $currentUAC & ~65536;
            $logDetails[] = __('user_password_expires_enabled');
        }
        
        $updateData['userAccountControl'] = [$newUAC];
    }

    if (isset($data['must_change'])) {
        if ($data['must_change']) {
            $updateData['pwdLastSet'] = [0];
            $logDetails[] = __('user_must_change_next_logon');
        } else {
            $updateData['pwdLastSet'] = [-1];
            $logDetails[] = __('user_must_change_removed');
        }
    }

    // Debug logging
    error_log("Updating user: " . $username);
    error_log("DN: " . $userDN);
    error_log("Update data: " . print_r($updateData, true));
    error_log("Delete data: " . print_r($deleteData, true));

    // First delete empty values if any
    if (!empty($deleteData)) {
        try {
            if (!@ldap_mod_del($ldap_conn, $userDN, $deleteData)) {
                error_log("Warning: Failed to delete attributes: " . ldap_error($ldap_conn));
                // Xəta olsa da davam edirik, fatal error deyil
            }
        } catch (Exception $e) {
            error_log("Exception during attribute deletion: " . $e->getMessage());
            // Xəta olsa da davam edirik
        }
    }

    // Then update non-empty values if any
    if (!empty($updateData)) {
        if (!ldap_modify($ldap_conn, $userDN, $updateData)) {
            throw new Exception(__('user_update_failed') . ': ' . ldap_error($ldap_conn));
        }
    }

    // Log the changes if any were made
    if (!empty($logDetails)) {
        logActivity(
            'USER_DATA_CHANGE',
            $username,
            implode(", ", $logDetails)
        );
    }

    echo json_encode([
        'success' => true,
        'message' => __('user_updated_success'),
        'details' => $logDetails
    ]);

} catch (Exception $e) {
    error_log("User edit error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($ldap_conn)) {
        ldap_unbind($ldap_conn);
    }
} 