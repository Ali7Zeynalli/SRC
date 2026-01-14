<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */
// Prevent any output before JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start clean output buffer
if (ob_get_level()) ob_end_clean();
ob_start();

session_start();
require_once(__DIR__ . '/../includes/functions.php');


header('Content-Type: application/json');

if (!isset($_SESSION['ad_username'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('user_unauthorized')]);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action'])) {
        throw new Exception(__('user_action_required'));
    }
    
    $ldap_conn = getLDAPConnection();
    $config = require(__DIR__ . '/../config/config.php');
    $action = $input['action'];
    
    // Clear any accidental output
    if (ob_get_length()) ob_clean();
    
    switch ($action) {
        case 'unlock':
            unlockUser($ldap_conn, $input['username']);
            logActivity('UNLOCK_USER', $input['username'], __('user_unlocked'));
            echo json_encode(['success' => true]);
            break;
            
        case 'activate':
            try {
                // Əvvəlki çıxışları təmizləyək
                if (ob_get_length()) ob_clean();
                
                if (empty($input['username'])) {
                    throw new Exception(__('user_username_required'));
                }

                // Aktivləşdirmə funksiyasını çağıraq
                activateUser($ldap_conn, $input['username']);

                // Uğurlu cavab
                header('Content-Type: application/json');
                logActivity('ENABLE_USER', $input['username'], __('user_enabled'));
                echo json_encode([
                    'success' => true,
                    'message' => __('user_activated_success')
                ]);

            } catch (Exception $e) {
                // Xəta baş verdikdə
                if (ob_get_length()) ob_clean();
                
                error_log("Activation error: " . $e->getMessage());
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        case 'deactivate':
            deactivateUser($ldap_conn, $input['username']);
            logActivity('DISABLE_USER', $input['username'], __('user_disabled'));
            echo json_encode(['success' => true]);
            break;
            
        case 'reset_password':
            try {
                if (empty($input['username'])) {
                    throw new Exception(__('user_username_required'));
                }
                
                $username = $input['username'];
                $config = require(__DIR__ . '/../config/config.php');
                
                // Get user DN and current settings
                $filter = "(&(objectClass=user)(objectCategory=person)(sAMAccountName=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . "))";
                $result = ldap_search($ldap_conn, $config['ad_settings']['base_dn'], $filter, ['distinguishedName', 'userAccountControl', 'pwdLastSet']);
                
                if ($result === false) {
                    throw new Exception(__('user_ldap_search_failed') . ': ' . ldap_error($ldap_conn));
                }
                
                $entries = ldap_get_entries($ldap_conn, $result);
                if ($entries['count'] === 0) {
                    throw new Exception(__('user_not_found') . ': ' . $username);
                }
                
                $user_dn = $entries[0]['distinguishedname'][0];
                $current_uac = $entries[0]['useraccountcontrol'][0];
                
                // Determine password and options
                if (!empty($input['use_default_password'])) {
                    $new_password = $config['password_settings']['default_temp_password'];
                    $must_change = true;
                } else {
                    if (empty($input['password'])) {
                        throw new Exception(__('user_password_required'));
                    }
                    
                    // Validate password complexity
                    if (!validatePasswordComplexity($input['password'])) {
                        throw new Exception(__('user_password_complexity_failed'));
                    }
                    
                    $new_password = $input['password'];
                    $must_change = !empty($input['must_change']);
                }

                // 1. Reset password
                $encoded_password = encodePassword($new_password);
                if (!ldap_modify($ldap_conn, $user_dn, ['unicodePwd' => $encoded_password])) {
                    throw new Exception(__('user_password_reset_failed') . ': ' . ldap_error($ldap_conn));
                }

                // 2. Clear Kerberos cache
                $cache_cleared = clearKerberosCache($username);

                // 3. Handle password expiration
                if ($must_change) {
                    // First set pwdLastSet to 0
                    if (!ldap_modify($ldap_conn, $user_dn, ['pwdLastSet' => [0]])) {
                        throw new Exception(__('user_password_change_forced_failed') . ': ' . ldap_error($ldap_conn));
                    }

                    // Update userAccountControl to force password change
                    $new_uac = $current_uac | LDAP_UAC_PASSWORD_EXPIRED;
                    if (!ldap_modify($ldap_conn, $user_dn, ['userAccountControl' => [$new_uac]])) {
                        throw new Exception(__('user_password_expired_flag_failed') . ': ' . ldap_error($ldap_conn));
                    }

                    // Double-check pwdLastSet is 0
                    $check = ldap_read($ldap_conn, $user_dn, '(objectClass=*)', ['pwdLastSet']);
                    $check_entries = ldap_get_entries($ldap_conn, $check);
                    if ($check_entries[0]['pwdlastset'][0] != '0') {
                        throw new Exception(__('user_password_change_verification_failed'));
                    }
                } else {
                    // Set pwdLastSet to -1 (current time)
                    if (!ldap_modify($ldap_conn, $user_dn, ['pwdLastSet' => [-1]])) {
                        throw new Exception(__('user_password_timestamp_failed') . ': ' . ldap_error($ldap_conn));
                    }
                }

                // 4. Unlock account
                ldap_modify($ldap_conn, $user_dn, [
                    'lockoutTime' => [0],
                    'badPwdCount' => [0]
                ]);

                // Log activity
                logActivity(
                    'PASSWORD_RESET',
                    $username,
                    __('user_password_reset_by_admin') . 
                    ($must_change ? ' ' . __('user_must_change_next_logon') : '') .
                    ($input['use_default_password'] ? ' ' . __('user_using_default_password') : ' ' . __('user_manual_entry')) .
                    ($cache_cleared ? ' - ' . __('user_kerberos_cache_cleared') : ' - ' . __('user_kerberos_cache_failed'))
                );
                
                echo json_encode([
                    'success' => true,
                    'cache_cleared' => $cache_cleared,
                    'must_change' => $must_change
                ]);
                
            } catch (Exception $e) {
                error_log("Password reset failed for user $username: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        case 'delete':
            if (empty($input['username'])) {
                throw new Exception(__('user_username_required'));
            }
            
            try {
                // Clear any previous output
                if (ob_get_length()) ob_clean();
                
                // Find user's DN
                $filter = "(&(objectClass=user)(sAMAccountName=" . ldap_escape($input['username'], "", LDAP_ESCAPE_FILTER) . "))";
                $result = ldap_search($ldap_conn, $config['ad_settings']['base_dn'], $filter, ["distinguishedName"]);
                
                if ($result === false) {
                    throw new Exception(__('user_ldap_search_error') . ': ' . ldap_error($ldap_conn));
                }
                
                $entries = ldap_get_entries($ldap_conn, $result);
                if ($entries['count'] == 0) {
                    throw new Exception(__('user_not_found') . ': ' . $input['username']);
                }
                
                $user_dn = $entries[0]['distinguishedname'][0];
                
                // Debug logging
                error_log("User DN to be deleted: " . $user_dn);
                
                // Delete the user account
                if (!@ldap_delete($ldap_conn, $user_dn)) {
                    throw new Exception(__('user_ldap_delete_error') . ': ' . ldap_error($ldap_conn));
                }
                
                // Clear output before sending response
                if (ob_get_length()) ob_clean();
                
                logActivity('DELETE_USER', $input['username'], __('user_deleted'));
                echo json_encode([
                    'success' => true,
                    'message' => __('user_deleted_success')
                ]);
                
            } catch (Exception $e) {
                // Clear any output before error response
                if (ob_get_length()) ob_clean();
                
                error_log("Delete user error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => __('user_delete_failed') . ': ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'move_ou':
            if (!isset($input['new_ou'])) {
                throw new Exception(__('user_new_ou_required'));
            }
            changeUserOU($ldap_conn, $input['username'], $input['new_ou']);
            echo json_encode(['success' => true]);
            break;
            
        case 'update_groups':
            if (!isset($input['groups'])) {
                throw new Exception(__('user_groups_required'));
            }
            updateUserGroups($ldap_conn, $input['username'], $input['groups']);
            echo json_encode(['success' => true]);
            break;
            
        case 'create_user':
            try {
                // Clear any previous output
                if (ob_get_length()) ob_clean();
                
                // Debug log
                error_log("Create user request received: " . print_r($input, true));
                
                // Validate required fields
                $requiredFields = ['username', 'firstname', 'lastname', 'password', 'ou'];
                foreach ($requiredFields as $field) {
                    if (empty($input[$field])) {
                        throw new Exception(str_replace('{field}', $field, __('user_field_required')));
                    }
                }
                
                // Check if username already exists
                $filter = "(sAMAccountName=" . ldap_escape($input['username'], "", LDAP_ESCAPE_FILTER) . ")";
                $result = ldap_search($ldap_conn, $config['ad_settings']['base_dn'], $filter, ['sAMAccountName']);
                $entries = ldap_get_entries($ldap_conn, $result);
                
                if ($entries['count'] > 0) {
                    throw new Exception(str_replace('{username}', $input['username'], __('user_already_exists')));
                }
                
                // Prepare user data
                $userData = [
                    'username' => $input['username'],
                    'firstName' => $input['firstname'],
                    'lastName' => $input['lastname'],
                    'displayName' => $input['displayname'],
                    'password' => $input['password'],
                    'mail' => $input['email'] ?? null,
                    'ou' => $input['ou'],
                    'accountOptions' => [
                        'mustChangePassword' => !empty($input['account_options']['must_change_password']),
                        'passwordNeverExpires' => !empty($input['account_options']['password_never_expires'])
                    ],
                    'groups' => $input['groups'] ?? []
                ];
                
                error_log("Prepared user data: " . print_r($userData, true));
                
                // Create the user
                $result = createUser($ldap_conn, $userData);
                
                // Return success response
                echo json_encode([
                    'success' => true,
                    'message' => $result['message']
                ]);
                
            } catch (Exception $e) {
                // Clear any output before error response
                if (ob_get_length()) ob_clean();
                
                error_log("Create user error: " . $e->getMessage());
                error_log("Error trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => __('user_creation_failed') . ': ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            throw new Exception(__('user_unknown_action') . ': ' . $action);
    }
    
} catch (Exception $e) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    error_log("User action error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Ensure output is sent
    ob_end_flush();
    if (isset($ldap_conn)) {
        ldap_unbind($ldap_conn);
    }
}
