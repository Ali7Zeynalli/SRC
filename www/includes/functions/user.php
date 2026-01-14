<?php
require_once dirname(__DIR__) . '/functions.php';

/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */



// LDAP UAC Constants
define('LDAP_UAC_SCRIPT', 0x0001);
define('LDAP_UAC_ACCOUNTDISABLE', 0x0002);
define('LDAP_UAC_HOMEDIR_REQUIRED', 0x0008);
define('LDAP_UAC_LOCKOUT', 0x0010);
define('LDAP_UAC_PASSWD_NOTREQD', 0x0020);
define('LDAP_UAC_PASSWD_CANT_CHANGE', 0x0040);
define('LDAP_UAC_ENCRYPTED_TEXT_PWD_ALLOWED', 0x0080);
define('LDAP_UAC_TEMP_DUPLICATE_ACCOUNT', 0x0100);
define('LDAP_UAC_NORMAL_ACCOUNT', 0x0200);
define('LDAP_UAC_INTERDOMAIN_TRUST_ACCOUNT', 0x0800);
define('LDAP_UAC_WORKSTATION_TRUST_ACCOUNT', 0x1000);
define('LDAP_UAC_SERVER_TRUST_ACCOUNT', 0x2000);
define('LDAP_UAC_DONT_EXPIRE_PASSWORD', 0x10000);
define('LDAP_UAC_MNS_LOGON_ACCOUNT', 0x20000);
define('LDAP_UAC_SMARTCARD_REQUIRED', 0x40000);
define('LDAP_UAC_TRUSTED_FOR_DELEGATION', 0x80000);
define('LDAP_UAC_NOT_DELEGATED', 0x100000);
define('LDAP_UAC_USE_DES_KEY_ONLY', 0x200000);
define('LDAP_UAC_DONT_REQ_PREAUTH', 0x400000);
define('LDAP_UAC_PASSWORD_EXPIRED', 0x800000);
define('LDAP_UAC_TRUSTED_TO_AUTH_FOR_DELEGATION', 0x1000000);

function getAllUsers($ldap_conn) {
    try {
        // Cache-dən yoxlayırıq
        $cache_key = 'all_users_cache';
        $cache_lifetime = 300; // 5 dəqiqə

        if (isset($_SESSION[$cache_key]) && 
            (time() - $_SESSION[$cache_key . '_time'] < $cache_lifetime)) {
            return $_SESSION[$cache_key];
        }
        
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        
        // LDAP axtarış limitlərini artırırıq
        ldap_set_option($ldap_conn, LDAP_OPT_SIZELIMIT, 5000);
        
        // Daha effektiv filter və minimal atributlar
        $filter = "(&(objectClass=user)(objectCategory=person))";
        $attributes = array(
            "samaccountname",
            "useraccountcontrol",
            "pwdlastset",
            "distinguishedname",
            "memberof"
        );
        
        // Səhifələmə ilə axtarış
        $cookie = '';
        $all_users = array();
        $all_users['count'] = 0;
        
        do {
            $result = ldap_search(
                $ldap_conn, 
                $base_dn, 
                $filter, 
                $attributes,
                0, 0, 0,
                LDAP_DEREF_NEVER,
                array(
                    array(
                        'oid' => LDAP_CONTROL_PAGEDRESULTS,
                        'value' => array(
                            'size' => 1000,
                            'cookie' => $cookie
                        )
                    )
                )
            );
            
            if ($result === false) {
                throw new Exception('LDAP search failed: ' . ldap_error($ldap_conn));
            }
            
            ldap_parse_result($ldap_conn, $result, $errcode, $matcheddn, $errmsg, $referrals, $controls);
            
            $entries = ldap_get_entries($ldap_conn, $result);
            
            if ($all_users['count'] === 0) {
                $all_users = $entries;
            } else {
                for ($i = 0; $i < $entries['count']; $i++) {
                    $all_users[$all_users['count']] = $entries[$i];
                    $all_users['count']++;
                }
            }
            
            $cookie = isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie']) ? 
                     $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'] : '';
            
        } while (!empty($cookie));
        
        // Cache-ə yazırıq
        $_SESSION[$cache_key] = $all_users;
        $_SESSION[$cache_key . '_time'] = time();
        
        return $all_users;
        
    } catch (Exception $e) {
        error_log("Error in getAllUsers: " . $e->getMessage());
        throw new Exception('Failed to get users: ' . $e->getMessage());
    }
}

function getUserDetails($ldap_conn, $username) {
    try {
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        
        $filter = "(&(objectClass=user)(objectCategory=person)(sAMAccountName=" . 
            ldap_escape($username, "", LDAP_ESCAPE_FILTER) . "))";
            
        $result = ldap_search($ldap_conn, $base_dn, $filter);
        
        if ($result === false) {
            throw new Exception('LDAP search failed');
        }
        
        $entries = ldap_get_entries($ldap_conn, $result);
        
        if ($entries['count'] === 0) {
            throw new Exception('User not found');
        }
        
        return formatUserDetails($entries[0], $ldap_conn);
        
    } catch (Exception $e) {
        throw new Exception('Failed to get user details: ' . $e->getMessage());
    }
}

function formatUserDetails($user, $ldap_conn) {
    try {
        if (!isset($user['samaccountname'][0])) {
            throw new Exception('Invalid user data structure');
        }

        $uac = isset($user['useraccountcontrol'][0]) ? (int)$user['useraccountcontrol'][0] : 0;
        $pwdLastSet = $user['pwdlastset'][0] ?? '0';
        
        $pwdStatus = getPasswordExpiryStatus($pwdLastSet, $ldap_conn, $uac);
        
        // Format distinguished name for OU
        $dn = $user['distinguishedname'][0] ?? '';
        $ou = formatOUPath($dn);
        
        // Format group memberships
        $groups = isset($user['memberof']) ? formatGroupList($user['memberof']) : '';

        // Fix created date formatting
        $created = isset($user['whencreated'][0]) 
            ? formatLDAPDate($user['whencreated'][0]) 
            : 'Not available';

        return [
            'username' => $user['samaccountname'][0],
            'displayName' => $user['displayname'][0] ?? '',
            'email' => $user['mail'][0] ?? '',
            'department' => $user['department'][0] ?? '',
            'title' => $user['title'][0] ?? '',
            'phone' => $user['telephonenumber'][0] ?? '',
            'mobile' => $user['mobile'][0] ?? '',
            'enabled' => ($uac & 2) !== 2,
            'locked' => ($uac & 16) === 16,
            'lastLogon' => formatLDAPTime($user['lastlogon'][0] ?? 0),
            'created' => $created,  // Updated this line
            'passwordStatus' => $pwdStatus['status'],
            'passwordStatusClass' => $pwdStatus['class'],
            'ou' => $ou ?: 'Root',
            'groups' => $groups,
            'description' => $user['description'][0] ?? ''
        ];
    } catch (Exception $e) {
        error_log("Error formatting user details: " . $e->getMessage());
        throw new Exception("Failed to format user details: " . $e->getMessage());
    }
}


function getPasswordExpiryStatus($pwdLastSet, $ldap_conn, $userAccountControl = null) {
    try {
        // First check for "Never Expires" flag
        if ($userAccountControl && ($userAccountControl & 65536)) { // UF_DONT_EXPIRE_PASSWORD
            return [
                'status' => 'Never Expires',
                'class' => 'bg-info'
            ];
        }

        // Existing password status logic
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        
        if ($pwdLastSet === '0') {
            return [
                'status' => 'Must Change',
                'class' => 'bg-secondary'
            ];
        }
        
        $result = ldap_read($ldap_conn, $base_dn, "(objectclass=*)", ["maxPwdAge"]);
        $domain_info = ldap_get_entries($ldap_conn, $result);
        
        $maxPwdAge = abs($domain_info[0]['maxpwdage'][0])/10000000;
        $pwdLastSetTimestamp = ($pwdLastSet/10000000) - 11644473600;
        $expiryTimestamp = $pwdLastSetTimestamp + $maxPwdAge;
        $now = time();
        
        if ($now > $expiryTimestamp) {
            return ['status' => 'Expired', 'class' => 'bg-danger'];
        }
        
        $daysLeft = floor(($expiryTimestamp - $now) / 86400);
        if ($daysLeft <= 5) {
            return ['status' => "$daysLeft  days left", 'class' => 'bg-warning'];
        }
        
        return ['status' => "$daysLeft days left", 'class' => 'bg-success'];
        
    } catch (Exception $e) {
        error_log("Password status error: " . $e->getMessage());
        return ['status' => 'Unknown', 'class' => 'bg-secondary'];
    }
}

function formatLDAPTime($ldapTime) {
    if (empty($ldapTime)) return 'Never';
    return date('Y-m-d H:i:s', ($ldapTime / 10000000) - 11644473600);
}

function formatLDAPDate($adDate) {
    if (empty($adDate)) return 'Not available';
    
    // AD dates are in format: YYYYMMDDHHMMSS.0Z
    // Example: 20231215152601.0Z
    $year = substr($adDate, 0, 4);
    $month = substr($adDate, 4, 2);
    $day = substr($adDate, 6, 2);
    $hour = substr($adDate, 8, 2);
    $minute = substr($adDate, 10, 2);
    $second = substr($adDate, 12, 2);
    
    return date('Y-m-d H:i:s', strtotime("$year-$month-$day $hour:$minute:$second"));
}

function formatOUPath($dn) {
    if (empty($dn)) return 'Root';
    
    $parts = explode(',', $dn);
    $ou_parts = array_filter($parts, function($part) {
        return strpos(trim($part), 'OU=') === 0;
    });
    
    if (empty($ou_parts)) return 'Root';
    
    return implode(' > ', array_map(function($ou) {
        return substr(trim($ou), 3); // Remove "OU=" prefix
    }, array_reverse($ou_parts)));
}

function formatGroupList($memberOf) {
    if (!isset($memberOf['count'])) return '';
    
    $groups = [];
    for ($i = 0; $i < $memberOf['count']; $i++) {
        if (!isset($memberOf[$i])) continue;
        
        $group_dn = explode(',', $memberOf[$i])[0] ?? '';
        if (strpos($group_dn, 'CN=') === 0) {
            $groups[] = substr($group_dn, 3);
        }
    }
    
    sort($groups);
    return implode(', ', $groups);
}

function deleteUser($ldap_conn, $username) {
    try {
        // Validate username
        if (empty($username)) {
            throw new Exception(__('user_username_required'));
        }

        // Get base DN from config
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];

        // Find user's DN
        $filter = "(&(objectClass=user)(sAMAccountName=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . "))";
        $result = ldap_search($ldap_conn, $base_dn, $filter, ["distinguishedName"]);
        
        if ($result === false) {
            $error = ldap_error($ldap_conn);
            error_log("LDAP search error: " . $error);
            throw new Exception(__('user_ldap_search_error') . ': ' . $error);
        }
        
        $entries = ldap_get_entries($ldap_conn, $result);
        if ($entries['count'] == 0) {
            error_log("User not found: " . $username);
                            throw new Exception(__('user_not_found') . ': ' . $username);
        }
        
        $user_dn = $entries[0]['distinguishedname'][0];
        
        // Debug logging
        error_log("Attempting to delete user DN: " . $user_dn);
        
        // Try to remove from all groups first
        try {
            updateUserGroups($ldap_conn, $username, []);
        } catch (Exception $e) {
            error_log("Warning: Failed to remove user from groups: " . $e->getMessage());
            // Continue with deletion even if group removal fails
        }

        // Delete the user account
        $result = ldap_delete($ldap_conn, $user_dn);
        if ($result === false) {
            $error = ldap_error($ldap_conn);
            error_log("LDAP delete error: " . $error);
            throw new Exception(__('user_ldap_delete_error') . ': ' . $error);
        }

        // Log the successful deletion
        error_log("Successfully deleted user: " . $username);
        logAction($username, 'delete', __('user_deleted_success'));
        return true;

    } catch (Exception $e) {
        error_log("Delete user error: " . $e->getMessage());
        throw new Exception(__('user_delete_failed') . ': ' . $e->getMessage());
    }
}

function activateUser($ldap_conn, $username) {
    try {
        // Validate username
        if (empty($username)) {
            throw new Exception(__('user_username_required'));
        }

        // Find user's DN
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        
        $filter = "(&(objectClass=user)(sAMAccountName=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . "))";
        $result = ldap_search($ldap_conn, $base_dn, $filter, array("distinguishedName", "userAccountControl"));
        
        if (!$result) {
            error_log("LDAP search error: " . ldap_error($ldap_conn));
            throw new Exception(__('user_ldap_search_error') . ': ' . ldap_error($ldap_conn));
        }

        $entries = ldap_get_entries($ldap_conn, $result);
        
        if ($entries['count'] == 0) {
            error_log("User not found: " . $username);
            throw new Exception(__('user_not_found') . ': ' . $username);
        }

        $user_dn = $entries[0]['distinguishedname'][0];
        
        // Read current status
        $currentStatus = isset($entries[0]['useraccountcontrol'][0]) ? (int)$entries[0]['useraccountcontrol'][0] : 514;
        
        // Debug information
        error_log("Activation started - User: " . $username);
        error_log("User DN: " . $user_dn);
        error_log("Current status: " . $currentStatus);

        // Normal active user status (512)
        $newStatus = 512;
        
        // If "Password never expires" bit exists, keep it (66048)
        if ($currentStatus & 65536) {
            $newStatus = 66048;
        }
        
        error_log("New status: " . $newStatus);

        // Array for LDAP modification
        $entry = array(
            'userAccountControl' => array($newStatus)
        );

        // Execute LDAP modification
        $result = @ldap_modify($ldap_conn, $user_dn, $entry);
        
        if (!$result) {
            $error = ldap_error($ldap_conn);
            error_log("LDAP modification error: " . $error);
            throw new Exception(__('user_update_failed') . ': ' . $error);
        }

        error_log("User successfully activated: " . $username);
        
        // Additional verification - confirm status change
        $verify_result = ldap_search($ldap_conn, $base_dn, $filter, array("userAccountControl"));
        $verify_entries = ldap_get_entries($ldap_conn, $verify_result);
        
        if ($verify_entries['count'] > 0) {
            $final_status = (int)$verify_entries[0]['useraccountcontrol'][0];
            error_log("Verification status: " . $final_status);
            
            // Check if status changed correctly
            if (($final_status & 2) === 2) {
                throw new Exception(__('user_update_failed'));
            }
        }

        return true;

    } catch (Exception $e) {
        error_log("Activation error: " . $e->getMessage());
        throw new Exception(__('user_update_failed') . ': ' . $e->getMessage());
    }
}

function deactivateUser($ldap_conn, $username) {
    try {
        // Validate username
        if (empty($username)) {
            throw new Exception(__('user_username_required'));
        }

        // Find user's DN
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        
        $filter = "(&(objectClass=user)(sAMAccountName=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . "))";
        $result = ldap_search($ldap_conn, $base_dn, $filter, array("distinguishedName", "userAccountControl"));
        
        if (!$result) {
            error_log("LDAP search error: " . ldap_error($ldap_conn));
            throw new Exception(__('user_ldap_search_error') . ': ' . ldap_error($ldap_conn));
        }

        $entries = ldap_get_entries($ldap_conn, $result);
        
        if ($entries['count'] == 0) {
            error_log("User not found: " . $username);
            throw new Exception(__('user_not_found') . ': ' . $username);
        }

        $user_dn = $entries[0]['distinguishedname'][0];
        $currentStatus = isset($entries[0]['useraccountcontrol'][0]) ? (int)$entries[0]['useraccountcontrol'][0] : 512;

        // Debug information
        error_log("Deactivation started - User: " . $username);
        error_log("User DN: " . $user_dn);
        error_log("Current status: " . $currentStatus);

        // Add deactivate bit (OR 2)
        $newStatus = $currentStatus | 2;
        
        error_log("New status: " . $newStatus);

        // Array for LDAP modification
        $entry = array(
            'userAccountControl' => array($newStatus)
        );

        // Execute LDAP modification
        if (!ldap_modify($ldap_conn, $user_dn, $entry)) {
            $error = ldap_error($ldap_conn);
            error_log("LDAP modification error: " . $error);
            throw new Exception(__('user_update_failed') . ': ' . $error);
        }

        error_log("User successfully deactivated: " . $username);
        return true;

    } catch (Exception $e) {
        error_log("Deactivation error: " . $e->getMessage());
        throw new Exception(__('user_update_failed') . ': ' . $e->getMessage());
    }
}

function getLockedUsers($ldap_conn) {
    try {
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        
        // Kilidlənmiş istifadəçiləri axtar
        $filter = "(&(objectCategory=person)(objectClass=user)(lockoutTime>=1))";
        $attributes = ["cn", "samaccountname", "lockoutTime", "distinguishedName"];
        
        $result = ldap_search($ldap_conn, $base_dn, $filter, $attributes);
        if (!$result) {
            throw new Exception(__('user_ldap_search_error') . ': ' . ldap_error($ldap_conn));
        }
        
        $entries = ldap_get_entries($ldap_conn, $result);
        $lockedUsers = [];
        
        for ($i = 0; $i < $entries['count']; $i++) {
            $lockedUsers[] = [
                'name' => $entries[$i]['cn'][0],
                'username' => $entries[$i]['samaccountname'][0],
                'lockoutTime' => formatLDAPTime($entries[$i]['lockouttime'][0]),
                'dn' => $entries[$i]['distinguishedname'][0]
            ];
        }
        
        return $lockedUsers;
    } catch (Exception $e) {
        error_log("Error getting locked users: " . $e->getMessage());
        throw new Exception(__('user_locked_users_failed') . ': ' . $e->getMessage());
    }
}

function unlockUser($ldap_conn, $username) {
    try {
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        
        // Find user DN
        $filter = "(&(objectClass=user)(sAMAccountName=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . "))";
        $result = ldap_search($ldap_conn, $base_dn, $filter, ["distinguishedName"]);
        
        if (!$result) {
            throw new Exception(__('user_not_found'));
        }
        
        $entries = ldap_get_entries($ldap_conn, $result);
        if ($entries['count'] === 0) {
            throw new Exception(__('user_not_found'));
        }
        
        $user_dn = $entries[0]['distinguishedname'][0];
        
        // Reset lockoutTime to 0
        $entry = ['lockoutTime' => [0]];
        if (!ldap_modify($ldap_conn, $user_dn, $entry)) {
            throw new Exception(__('user_unlock_failed'));
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error unlocking user: " . $e->getMessage());
        throw new Exception(__('user_unlock_failed') . ': ' . $e->getMessage());
    }
}

function validatePasswordComplexity($password) {
    // Minimum length
    if (strlen($password) < 8) {
        return false;
    }
    
    // Must contain at least three of the following:
    $categories = 0;
    
    // Uppercase letters
    if (preg_match('/[A-Z]/', $password)) {
        $categories++;
    }
    
    // Lowercase letters
    if (preg_match('/[a-z]/', $password)) {
        $categories++;
    }
    
    // Numbers
    if (preg_match('/[0-9]/', $password)) {
        $categories++;
    }
    
    // Special characters
    if (preg_match('/[^A-Za-z0-9]/', $password)) {
        $categories++;
    }
    
    // Must meet at least 3 categories
    return $categories >= 3;
}

function encodePassword($password) {
    // Remove any potential UTF-8 BOM and normalize string
    $password = trim($password);
    
    // Add quotes
    $quoted_password = '"' . $password . '"';
    
    // Convert to UTF-16LE
    $utf16_password = mb_convert_encoding($quoted_password, 'UTF-16LE', 'UTF-8');
    
    return $utf16_password;
}

/**
 * Clears Kerberos cache after password change
 * @param string $username The username whose password was changed
 * @return bool Success status
 */
function clearKerberosCache($username) {
    try {
        error_log("Clearing Kerberos cache for user: $username");
        
        // Use sudo if needed
        $command = 'sudo klist purge 2>&1';
        $output = [];
        $return_var = 0;
        
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            error_log("Kerberos cache clear failed. Output: " . implode("\n", $output));
            
            // Try without sudo as fallback
            $command = 'klist purge 2>&1';
            exec($command, $output, $return_var);
            
            if ($return_var !== 0) {
                error_log("Kerberos cache clear failed without sudo. Output: " . implode("\n", $output));
                return false;
            }
        }
        
        error_log("Kerberos cache cleared successfully for user: $username");
        return true;
    } catch (Exception $e) {
        error_log("Error clearing Kerberos cache: " . $e->getMessage());
        return false;
    }
}

/**
 * İstifadəçini bir OU-dan digərinə köçürür
 * @param resource $ldap_conn LDAP bağlantısı
 * @param string $username İstifadəçi adı
 * @param string $new_ou_dn Yeni OU-nun DN-i
 * @return bool Əməliyyatın uğurlu olub-olmadığı
 * @throws Exception Xəta baş verdikdə
 */ 
function changeUserOU($ldap_conn, $username, $new_ou_dn) {
    try {
        // İstifadəçi adını yoxlayırıq
        if (empty($username)) {
            throw new Exception(__('user_username_required'));
        }
        
        // Yeni OU-nu yoxlayırıq
        if (empty($new_ou_dn)) {
            throw new Exception(__('user_ou_required'));
        }
        
        // Config-i yükləyirik
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        
        // İstifadəçinin DN-ni tapırıq
        $filter = "(&(objectClass=user)(sAMAccountName=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . "))";
        $result = ldap_search($ldap_conn, $base_dn, $filter, ["distinguishedName"]);
        
        if (!$result) {
            throw new Exception(__('user_ldap_search_error') . ': ' . ldap_error($ldap_conn));
        }
        
        $entries = ldap_get_entries($ldap_conn, $result);
        
        if ($entries['count'] == 0) {
            throw new Exception(__('user_not_found') . ': ' . $username);
        }
        
        $user_dn = $entries[0]['distinguishedname'][0];
        
        // İstifadəçinin adını (RDN) alırıq
        $rdn_parts = explode(',', $user_dn);
        $user_rdn = $rdn_parts[0];
        
        // Yeni DN yaradırıq
        $new_dn = $user_rdn . ',' . $new_ou_dn;
        
        // Debug məlumatları
        error_log("Moving user: " . $username);
        error_log("Current DN: " . $user_dn);
        error_log("New DN: " . $new_dn);
        
        // İstifadəçini köçürürük
        if (!ldap_rename($ldap_conn, $user_dn, $user_rdn, $new_ou_dn, true)) {
            throw new Exception(__('user_move_failed') . ': ' . ldap_error($ldap_conn));
        }
        
        // Əməliyyatı loglaşdırırıq
        logActivity('MOVE_USER', $username, __('user_moved_success'));
        
        return true;
    } catch (Exception $e) {
        error_log("Error moving user: " . $e->getMessage());
        throw new Exception(__('user_move_failed') . ': ' . $e->getMessage());
    }
}

/**
 * İstifadəçinin qrup üzvlüklərini yeniləyir
 * @param resource $ldap_conn LDAP bağlantısı
 * @param string $username İstifadəçi adı
 * @param array $groups İstifadəçinin əlavə ediləcəyi qrupların siyahısı
 * @return bool Əməliyyatın uğurlu olub-olmadığı
 * @throws Exception Xəta baş verdikdə
 */
function updateUserGroups($ldap_conn, $username, $groups) {
    try {
        // İstifadəçi adını yoxlayırıq
        if (empty($username)) {
            throw new Exception(__('user_username_required'));
        }
        
        // Config-i yükləyirik
        $config = require(getConfigPath());
        $base_dn = $config['ad_settings']['base_dn'];
        
        // İstifadəçinin DN-ni tapırıq
        $filter = "(&(objectClass=user)(sAMAccountName=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . "))";
        $result = ldap_search($ldap_conn, $base_dn, $filter, ["distinguishedName", "memberOf"]);
        
        if (!$result) {
            throw new Exception(__('user_ldap_search_error') . ': ' . ldap_error($ldap_conn));
        }
        
        $entries = ldap_get_entries($ldap_conn, $result);
        
        if ($entries['count'] == 0) {
            throw new Exception(__('user_not_found') . ': ' . $username);
        }
        
        $user_dn = $entries[0]['distinguishedname'][0];
        
        // İstifadəçinin mövcud qruplarını alırıq
        $current_groups = [];
        if (isset($entries[0]['memberof'])) {
            for ($i = 0; $i < $entries[0]['memberof']['count']; $i++) {
                $current_groups[] = $entries[0]['memberof'][$i];
            }
        }
        
        // Yeni qrupların DN-lərini tapırıq
        $new_group_dns = [];
        foreach ($groups as $group_name) {
            $group_filter = "(&(objectClass=group)(cn=" . ldap_escape($group_name, "", LDAP_ESCAPE_FILTER) . "))";
            $group_result = ldap_search($ldap_conn, $base_dn, $group_filter, ["distinguishedName"]);
            
            if (!$group_result) {
                error_log(__('group_search_error') . ': ' . ldap_error($ldap_conn) . ' ' . __('for_group') . ': ' . $group_name);
                continue;
            }
            
            $group_entries = ldap_get_entries($ldap_conn, $group_result);
            
            if ($group_entries['count'] > 0) {
                $new_group_dns[] = $group_entries[0]['distinguishedname'][0];
            } else {
                error_log(__('group_not_found') . ': ' . $group_name);
            }
        }
        
        // Əlavə ediləcək və çıxarılacaq qrupları müəyyən edirik
        $groups_to_add = array_diff($new_group_dns, $current_groups);
        $groups_to_remove = array_diff($current_groups, $new_group_dns);
        
        // İstifadəçini qruplara əlavə edirik
        foreach ($groups_to_add as $group_dn) {
            $group_entry = [];
            $group_entry['member'] = [$user_dn];
            
            if (!ldap_mod_add($ldap_conn, $group_dn, $group_entry)) {
                error_log(__('group_add_member_failed') . ': ' . ldap_error($ldap_conn) . ' - ' . __('group') . ': ' . $group_dn);
            } else {
                error_log(__('group_add_member_success') . ': ' . $group_dn);
            }
        }
        
        // İstifadəçini qruplardan çıxarırıq
        foreach ($groups_to_remove as $group_dn) {
            $group_entry = [];
            $group_entry['member'] = [$user_dn];
            
            if (!ldap_mod_del($ldap_conn, $group_dn, $group_entry)) {
                error_log(__('group_remove_member_failed') . ': ' . ldap_error($ldap_conn) . ' - ' . __('group') . ': ' . $group_dn);
            } else {
                error_log(__('group_remove_member_success') . ': ' . $group_dn);
            }
        }
        
        // Əməliyyatı loglaşdırırıq
        $groups_str = implode(", ", $groups);
        logActivity('UPDATE_USER_GROUPS', $username, __('user_groups_updated_success'));
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating user groups: " . $e->getMessage());
        throw new Exception(__('user_groups_update_failed') . ': ' . $e->getMessage());
    }
}

/**
 * Create a new user in Active Directory
 * 
 * @param resource $ldap_conn LDAP connection
 * @param array $userData User data array containing required fields
 * @return array Response array with success status and message
 */
function createUser($ldap_conn, $userData) {
    try {
        // Validate required fields
        $requiredFields = ['username', 'firstName', 'lastName', 'password'];
        foreach ($requiredFields as $field) {
            if (empty($userData[$field])) {
                throw new Exception(__('user_field_required', ['field' => $field]));
            }
        }

        $config = require(getConfigPath());
        
        // Generate display name if not provided
        $displayName = $userData['displayName'] ?? "{$userData['firstName']} {$userData['lastName']}";
        
        // Determine user container/OU
        $container = !empty($userData['ou']) ? $userData['ou'] : "CN=Users,{$config['ad_settings']['base_dn']}";
        
        // Create user DN
        $userDN = "CN=" . ldap_escape($displayName, "", LDAP_ESCAPE_DN) . "," . $container;
        
        // Prepare basic attributes
        $attributes = [
            'objectClass' => ['top', 'person', 'organizationalPerson', 'user'],
            'cn' => $displayName,
            'sn' => $userData['lastName'],
            'givenName' => $userData['firstName'],
            'displayName' => $displayName,
            'sAMAccountName' => $userData['username'],
            'userPrincipalName' => $userData['username'] . '@' . $config['ad_settings']['domain'],
            'unicodePwd' => encodePassword($userData['password']),
            'userAccountControl' => [determineUserAccountControl($userData['accountOptions'] ?? [])],
        ];
        
        // Add optional attributes if provided
        $optionalAttributes = [
            'title' => 'title',
            'department' => 'department',
            'company' => 'company',
            'mail' => 'mail',
            'telephoneNumber' => 'phone',
            'mobile' => 'mobile',
            'description' => 'description',
            'streetAddress' => 'street',
            'l' => 'city',
            'st' => 'state',
            'postalCode' => 'zipCode',
            'c' => 'country',
        ];
        
        foreach ($optionalAttributes as $ldapAttr => $dataKey) {
            if (!empty($userData[$dataKey])) {
                $attributes[$ldapAttr] = [$userData[$dataKey]];
            }
        }

        // Debug log
        error_log("Creating user with DN: " . $userDN);
        error_log("User attributes: " . print_r($attributes, true));

        // Create the user
        if (!ldap_add($ldap_conn, $userDN, $attributes)) {
            throw new Exception(__('user_creation_failed') . ': ' . ldap_error($ldap_conn));
        }

        // Handle password options
        $pwdLastSet = -1; // Default to not requiring password change
        if (!empty($userData['accountOptions']['mustChangePassword'])) {
            $pwdLastSet = 0;
        }
        
        ldap_modify($ldap_conn, $userDN, ['pwdLastSet' => [$pwdLastSet]]);

        // Add to groups if specified
        if (!empty($userData['groups']) && is_array($userData['groups'])) {
            foreach ($userData['groups'] as $groupDN) {
                $groupEntry = ['member' => [$userDN]];
                ldap_mod_add($ldap_conn, $groupDN, $groupEntry);
            }
        }

        // Log the action
        logActivity('CREATE_USER', $userData['username'], __('user_created_success'));

        return [
            'success' => true,
            'message' => __('user_created_success'),
            'userDN' => $userDN
        ];

    } catch (Exception $e) {
        error_log("Error creating user: " . $e->getMessage());
        throw new Exception(__('user_creation_failed') . ': ' . $e->getMessage());
    }
}

/**
 * Determine user account control value based on options
 * 
 * @param array $options Account options
 * @return int User account control value
 */
function determineUserAccountControl($options) {
    // Debug log
    error_log("Account options: " . print_r($options, true));
    
    // Default: Normal account, requires password
    $uac = LDAP_UAC_NORMAL_ACCOUNT;
    
    // Handle account options
    if (!empty($options['passwordNeverExpires'])) {
        $uac |= LDAP_UAC_DONT_EXPIRE_PASSWORD;
        error_log("Password never expires flag set");
    }
    
    error_log("Final UAC value: " . $uac);
    return $uac;
}