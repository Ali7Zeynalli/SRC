<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */
session_start();
require_once(__DIR__ . '/../includes/functions.php');

require_once(__DIR__ . '/../includes/functions/ldap.php');
require_once(__DIR__ . '/../includes/functions/ou.php');

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('ou_unauthorized')]);
    exit;
}

// Check if action is specified
if (!isset($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('ou_action_required')]);
    exit;
}

try {
    $ldap_conn = getLDAPConnection();
    $action = $_POST['action'];
    $response = ['success' => false];
    
    switch ($action) {
        case 'create':
            // Check required parameters
            if (!isset($_POST['name']) || empty($_POST['name'])) {
                throw new Exception(__('ou_name_required'));
            }
            
            $name = $_POST['name'];
            $description = $_POST['description'] ?? '';
            $parent = $_POST['parent'] ?? 'root';
            
            // Create the OU
            $result = createOU($ldap_conn, $name, $description, $parent);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => str_replace('{name}', $name, __('ou_created_success'))
                ];
                // Use standardized action type and structured data for logs
                logActivity('create_ou', null, json_encode([
                    'name' => $name,
                    'parent' => $parent,
                    'description' => $description
                ]));
            } else {
                throw new Exception(__('ou_create_failed'));
            }
            break;
            
        case 'update':
            // Check required parameters
            if (!isset($_POST['dn']) || empty($_POST['dn'])) {
                throw new Exception(__('ou_dn_required'));
            }
            if (!isset($_POST['name']) || empty($_POST['name'])) {
                throw new Exception(__('ou_name_required'));
            }
            
            $dn = $_POST['dn'];
            $name = $_POST['name'];
            $description = $_POST['description'] ?? '';
            
            // Update the OU
            $result = updateOU($ldap_conn, $dn, $name, $description);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => str_replace('{name}', $name, __('ou_updated_success'))
                ];
                // Use standardized action type and structured data for logs
                logActivity('update_ou', null, json_encode([
                    'name' => $name,
                    'dn' => $dn,
                    'description' => $description
                ]));
            } else {
                throw new Exception(__('ou_update_failed'));
            }
            break;
            
        case 'delete':
            // Check required parameters
            if (!isset($_POST['dn']) || empty($_POST['dn'])) {
                throw new Exception(__('ou_dn_required'));
            }
            
            $dn = $_POST['dn'];
            
            // Get OU name for logging
            $filter = "(objectClass=*)";
            $result = ldap_read($ldap_conn, $dn, $filter, ["ou", "cn"]);
            $entries = ldap_get_entries($ldap_conn, $result);
            $name = isset($entries[0]['ou'][0]) ? $entries[0]['ou'][0] : (isset($entries[0]['cn'][0]) ? $entries[0]['cn'][0] : __('ou_unknown'));
            
            // Delete the OU
            $result = deleteOU($ldap_conn, $dn);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => __('ou_deleted_success')
                ];
                // Use standardized action type and structured data for logs
                logActivity('delete_ou', null, json_encode([
                    'name' => $name,
                    'dn' => $dn
                ]));
            } else {
                throw new Exception(__('ou_delete_failed'));
            }
            break;
            
        case 'move':
            // Check required parameters
            if (!isset($_POST['dn']) || empty($_POST['dn'])) {
                throw new Exception(__('ou_dn_required'));
            }
            if (!isset($_POST['destination']) || empty($_POST['destination'])) {
                throw new Exception(__('ou_destination_required'));
            }
            
            $dn = $_POST['dn'];
            $destination = $_POST['destination'];
            
            // Get OU name for logging
            $filter = "(objectClass=*)";
            $result = ldap_read($ldap_conn, $dn, $filter, ["ou", "cn"]);
            $entries = ldap_get_entries($ldap_conn, $result);
            $name = isset($entries[0]['ou'][0]) ? $entries[0]['ou'][0] : (isset($entries[0]['cn'][0]) ? $entries[0]['cn'][0] : __('ou_unknown'));
            
            // Get destination name
            $destination_name = __('ou_root');
            if ($destination !== 'root') {
                $dest_result = ldap_read($ldap_conn, $destination, $filter, ["ou", "cn"]);
                $dest_entries = ldap_get_entries($ldap_conn, $dest_result);
                $destination_name = isset($dest_entries[0]['ou'][0]) ? $dest_entries[0]['ou'][0] : (isset($dest_entries[0]['cn'][0]) ? $dest_entries[0]['cn'][0] : __('ou_unknown'));
            }
            
            // Move the OU
            $result = moveOU($ldap_conn, $dn, $destination);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => __('ou_moved_success')
                ];
                // Use standardized action type and structured data for logs
                logActivity('move_ou', null, json_encode([
                    'name' => $name,
                    'from' => $dn,
                    'to' => $destination,
                    'destination_name' => $destination_name
                ]));
            } else {
                throw new Exception(__('ou_move_failed'));
            }
            break;
            
        default:
            throw new Exception(__('ou_invalid_action'));
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    // Log the error
    logActivity($_SESSION['ad_username'], 'OU Management', "Error: " . $e->getMessage(), 'error');
} finally {
    if (isset($ldap_conn) && $ldap_conn) {
        ldap_unbind($ldap_conn);
    }
}

/**
 * Create a new Organizational Unit
 * 
 * @param resource $ldap_conn LDAP connection
 * @param string $name OU name
 * @param string $description OU description
 * @param string $parent Parent OU DN or 'root'
 * @return bool Success or failure
 */
function createOU($ldap_conn, $name, $description = '', $parent = 'root') {
    $config = require(getConfigPath());
    
    // Determine parent DN
    $parent_dn = ($parent === 'root') ? $config['ad_settings']['base_dn'] : $parent;
    
    // Create OU DN
    $ou_dn = "OU=$name,$parent_dn";
    
    // Prepare attributes
    $attributes = [
        'objectClass' => ['top', 'organizationalUnit'],
        'ou' => $name
    ];
    
    if (!empty($description)) {
        $attributes['description'] = $description;
    }
    
    // Create the OU
    return ldap_add($ldap_conn, $ou_dn, $attributes);
}

/**
 * Update an Organizational Unit
 * 
 * @param resource $ldap_conn LDAP connection
 * @param string $dn OU distinguished name
 * @param string $name New OU name
 * @param string $description New OU description
 * @return bool Success or failure
 */
function updateOU($ldap_conn, $dn, $name, $description = '') {
    // Get current OU name from DN
    $dn_parts = explode(',', $dn);
    $current_ou_part = $dn_parts[0];
    $current_name = substr($current_ou_part, 3); // Remove "OU=" prefix
    
    // Prepare attributes to modify
    $attributes = [];
    
    // Update description only if it's not empty
    if (!empty($description)) {
        $attributes['description'] = $description;
    } else {
        // If description is empty, set it to empty array to remove it
        $attributes['description'] = [];
    }
    
    // Modify the OU attributes (only description at this point)
    $result = @ldap_modify($ldap_conn, $dn, $attributes);
    
    if (!$result) {
        $error = ldap_error($ldap_conn);
        error_log("LDAP Error modifying OU attributes: $error");
        return false;
    }
    
    // If name has changed, we need to rename the OU
    if ($name !== $current_name) {
        // Create new RDN
        $new_rdn = "OU=$name";
        
        // Get parent DN
        $parent_dn = implode(',', array_slice($dn_parts, 1));
        
        // Rename the OU
        $result = @ldap_rename($ldap_conn, $dn, $new_rdn, $parent_dn, true);
        
        if (!$result) {
            $error = ldap_error($ldap_conn);
            error_log("LDAP Error renaming OU: $error");
            return false;
        }
    }
    
    return $result;
}

/**
 * Delete an Organizational Unit
 * 
 * @param resource $ldap_conn LDAP connection
 * @param string $dn OU distinguished name
 * @return bool Success or failure
 */
function deleteOU($ldap_conn, $dn) {
    // First, check if the OU has children
    $result = ldap_list($ldap_conn, $dn, "(objectClass=*)", ["distinguishedName"]);
    $entries = ldap_get_entries($ldap_conn, $result);
    
    // If OU has children, delete them recursively
    if ($entries['count'] > 0) {
        for ($i = 0; $i < $entries['count']; $i++) {
            $child_dn = $entries[$i]['distinguishedname'][0];
            
            // Check if child is an OU or container
            $child_result = ldap_read($ldap_conn, $child_dn, "(objectClass=*)", ["objectClass"]);
            $child_entry = ldap_get_entries($ldap_conn, $child_result);
            
            if (in_array('organizationalUnit', $child_entry[0]['objectclass']) || 
                in_array('container', $child_entry[0]['objectclass'])) {
                // Recursively delete child OU
                deleteOU($ldap_conn, $child_dn);
            } else {
                // Delete child object
                ldap_delete($ldap_conn, $child_dn);
            }
        }
    }
    
    // Delete the OU itself
    return ldap_delete($ldap_conn, $dn);
}

/**
 * Move an Organizational Unit
 * 
 * @param resource $ldap_conn LDAP connection
 * @param string $dn OU distinguished name
 * @param string $destination Destination OU DN or 'root'
 * @return bool Success or failure
 */
function moveOU($ldap_conn, $dn, $destination) {
    $config = require(getConfigPath());
    
    // Determine destination DN
    $destination_dn = ($destination === 'root') ? $config['ad_settings']['base_dn'] : $destination;
    
    // Get OU name from DN
    $dn_parts = explode(',', $dn);
    $ou_part = $dn_parts[0];
    
    // Rename (move) the OU
    return ldap_rename($ldap_conn, $dn, $ou_part, $destination_dn, true);
} 