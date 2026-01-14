<?php
/*
 * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
 */ 
// Turn off error display
ini_set('display_errors', 0); // Errors should not be displayed to client
error_reporting(E_ALL);

// Start clean output buffer
ob_start();

session_start();
require_once(__DIR__ . '/../includes/functions.php');


// Ensure no output before headers
if (ob_get_length()) ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('group_unauthorized')]);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action'])) {
        throw new Exception(__('group_action_required'));
    }
    
    $ldap_conn = getLDAPConnection();
    $action = $input['action'];
    
    // Clean the buffer
    if (ob_get_length()) ob_clean();
    
    switch ($action) {
        case 'create_group':
            // Check required fields
            if (empty($input['name']) || empty($input['ou']) || empty($input['type'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => __('group_required_fields_missing')
                ]);
                exit;
            }
            
            try {
                // Build group data
                $groupData = [
                    'name' => $input['name'],
                    'ou' => $input['ou'],
                    'type' => $input['type'],
                ];
                
                // Add optional fields if they exist
                if (!empty($input['scope'])) {
                    $groupData['scope'] = $input['scope'];
                }
                
                if (isset($input['description'])) {
                    $groupData['description'] = $input['description'];
                }
                
                if (isset($input['email'])) {
                    $groupData['email'] = $input['email'];
                }
                
                if (isset($input['notes'])) {
                    $groupData['notes'] = $input['notes'];
                }
                
                // Log detailed info
                error_log('DEBUG: create_group - Group data prepared: ' . json_encode($groupData));
                
                // Create group
                $result = createGroup($ldap_conn, $groupData);
                
                // Ensure clean buffer before sending JSON response
                if (ob_get_length()) ob_clean();
                
                // Log the operation
                $detailsJson = json_encode([
                    'name' => $groupData['name'],
                    'ou' => $groupData['ou'],
                    'type' => $groupData['type'],
                    'scope' => $groupData['scope'] ?? '',
                    'description' => $groupData['description'] ?? ''
                ]);
                
                logActivity('create_group', $detailsJson, "Group created: {$groupData['name']}");
                
                // Return success
                echo json_encode([
                    'success' => true,
                    'group_dn' => $result['dn'],
                    'message' => __('group_created_success')
                ]);
                exit;
                
            } catch (Exception $e) {
                error_log('ERROR: create_group - Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                
                // Ensure clean buffer before sending JSON response
                if (ob_get_length()) ob_clean();
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => __('group_creation_failed') . ': ' . $e->getMessage()
                ]);
                exit;
            }
            break;
            
        case 'update_group':
            // Validate required fields
            if (empty($input['dn'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => __('group_dn_required')
                ]);
                exit;
            }
            
            try {
                $groupData = [
                    'dn' => $input['dn']
                ];
                
                // Description change
                if (isset($input['description'])) {
                    $groupData['description'] = $input['description'];
                    error_log('DEBUG: update_group - Description provided: ' . (empty($input['description']) ? 'empty' : $input['description']));
                }
                
                // Email change
                if (isset($input['email'])) {
                    $groupData['email'] = $input['email'];
                    error_log('DEBUG: update_group - Email provided: ' . (empty($input['email']) ? 'empty' : $input['email']));
                }
                
                // Notes change
                if (isset($input['notes'])) {
                    $groupData['notes'] = $input['notes'];
                    error_log('DEBUG: update_group - Notes provided: ' . (empty($input['notes']) ? 'empty' : $input['notes']));
                }
                
                // Log detailed info if name change exists
                if (!empty($input['name'])) {
                    $groupData['name'] = $input['name'];
                    error_log('DEBUG: update_group - Name change requested to: ' . $input['name']);
                }
                
                // Add type change if exists
                if (!empty($input['type'])) {
                    $groupData['type'] = $input['type'];
                    error_log('DEBUG: update_group - Type change requested to: ' . $input['type']);
                }
                
                // Add scope change if exists
                if (!empty($input['scope'])) {
                    $groupData['scope'] = $input['scope'];
                }
                
                // Log group data
                error_log('DEBUG: update_group - Group data prepared: ' . json_encode($groupData));
                
                $result = updateGroup($ldap_conn, $groupData);
                
                // Ensure clean buffer before sending JSON response
                if (ob_get_length()) ob_clean();
                
                // Return the result
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'dn' => $result['dn'],
                    'changed_attributes' => $result['changed_attributes'] ?? []
                ]);
                exit;
                
            } catch (Exception $e) {
                error_log('ERROR: update_group - Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                
                // Ensure clean buffer before sending JSON response
                if (ob_get_length()) ob_clean();
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => __('group_update_failed') . ': ' . $e->getMessage()
                ]);
                exit;
            }
            break;
            
        case 'delete_group':
            try {
                // Group DN is required
                if (empty($input['dn'])) {
                    throw new Exception(__('group_dn_required'));
                }
                
                // Execute group deletion operation
                $result = deleteGroup($ldap_conn, $input['dn']);
                
                // Return the result
                echo json_encode([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => __('group_delete_failed') . ': ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'add_members':
            // Check required fields
            if (empty($input['group_dn']) || empty($input['member_dns']) || !is_array($input['member_dns'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => __('group_members_required')
                ]);
                exit;
            }
            
            try {
                $groupDN = $input['group_dn'];
                $memberDNs = $input['member_dns'];
                
                // Validate group exists
                $filter = "(&(objectClass=group)(distinguishedName=" . ldap_escape($groupDN, "", LDAP_ESCAPE_FILTER) . "))";
                $result = ldap_read($ldap_conn, $groupDN, $filter, ['cn']);
                
                if (!$result || ldap_count_entries($ldap_conn, $result) !== 1) {
                    throw new Exception(__('group_not_found'));
                }
                
                $groupEntry = ldap_get_entries($ldap_conn, $result);
                $groupName = $groupEntry[0]['cn'][0];
                
                // Add members to group
                $result = addGroupMembers($ldap_conn, $groupDN, $memberDNs);
                
                // Log the operation
                $detailsJson = json_encode([
                    'group_dn' => $groupDN,
                    'group_name' => $groupName,
                    'member_count' => count($memberDNs)
                ]);
                
                logActivity('add_group_members', $detailsJson, str_replace('{count}', $result['added_count'], __('group_members_added')));
                
                // Return success
                echo json_encode([
                    'success' => true,
                    'added_count' => $result['added_count'],
                    'skipped_count' => $result['skipped_count'],
                    'message' => str_replace('{count}', $result['added_count'], __('group_members_added_success'))
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        case 'remove_members':
            try {
                // Group DN and member DNs are required
                if (empty($input['dn'])) {
                    throw new Exception(__('group_dn_required'));
                }
                
                if (empty($input['members']) || !is_array($input['members'])) {
                    throw new Exception(__('group_members_array_required'));
                }
                
                // Execute operation to remove members from the group
                $result = removeGroupMembers($ldap_conn, $input['dn'], $input['members']);
                
                // Return the result
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'successCount' => $result['successCount'],
                    'failedCount' => $result['failedCount'],
                    'failedMembers' => $result['failedMembers']
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => __('group_members_remove_failed') . ': ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'get_members_detailed':
            try {
                // Group DN is required
                if (empty($input['dn'])) {
                    throw new Exception(__('group_dn_required'));
                }
                
                // Get detailed information about group members
                $members = getGroupMembersDetailed($ldap_conn, $input['dn']);
                
                // Return the result
                echo json_encode([
                    'success' => true,
                    'members' => $members,
                    'count' => count($members)
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => __('group_members_get_failed') . ': ' . $e->getMessage(),
                    'members' => [],
                    'count' => 0
                ]);
            }
            break;
            
        case 'move_group_to_ou':
            try {
                // Log incoming data
                error_log('DEBUG: move_group_to_ou - Input data: ' . json_encode($input));
                
                // Group DN is required
                if (empty($input['dn'])) {
                    throw new Exception(__('group_dn_required'));
                }
                
                // New OU is required
                if (empty($input['new_ou'])) {
                    throw new Exception(__('group_new_ou_required'));
                }
                
                // Execute operation to move group to a different OU
                try {
                    $result = moveGroupToOU($ldap_conn, $input['dn'], $input['new_ou']);
                    
                    // Return the result
                    echo json_encode([
                        'success' => true,
                        'message' => $result['message'],
                        'dn' => $result['dn']
                    ]);
                } catch (Exception $moveError) {
                    error_log('ERROR: move_group_to_ou - Move failed: ' . $moveError->getMessage() . "\n" . $moveError->getTraceAsString());
                    throw $moveError;
                }
            } catch (Exception $e) {
                error_log('ERROR: move_group_to_ou - Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => __('group_move_failed') . ': ' . $e->getMessage(),
                    'debug_info' => 'Check server logs for more details.'
                ]);
            }
            break;
            
        default:
            throw new Exception(__('group_unknown_action') . ': ' . $action);
    }
    
} catch (Exception $e) {
    // Clean the buffer
    if (ob_get_length()) ob_clean();
    
    error_log("Group action error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Send the result
    ob_end_flush();
    if (isset($ldap_conn)) {
        ldap_unbind($ldap_conn);
    }
} 