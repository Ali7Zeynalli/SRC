<?php
require_once dirname(__DIR__) . '/functions.php';
 /*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */



function getAllGroups($ldap_conn) {
    $config = require(getConfigPath());
    $base_dn = $config['ad_settings']['base_dn'];
    
    $filter = "(objectClass=group)";
    $attributes = [
        "cn", "distinguishedname", "grouptype", "member",
        "whencreated", "description", "mail"
    ];
    
    $result = ldap_search($ldap_conn, $base_dn, $filter, $attributes);
    $entries = ldap_get_entries($ldap_conn, $result);
    
    $groups = [];
    for ($i = 0; $i < $entries['count']; $i++) {
        $entry = $entries[$i];
        $dn = $entry['distinguishedname'][0];
        
        // Get group type and scope from groupType attribute
        $groupTypeValue = intval($entry['grouptype'][0]);
        $type = getGroupSecurityType($groupTypeValue);
        $scope = getGroupScope($groupTypeValue);
        
        // Get OU path
        $ou = formatOUPath($dn);
        
        $groups[] = [
            'name' => $entry['cn'][0],
            'dn' => $dn,
            'type' => $type,
            'scope' => $scope,
            'memberCount' => isset($entry['member']) ? $entry['member']['count'] : 0,
            'ou' => $ou,
            'created' => formatLDAPDate($entry['whencreated'][0] ?? ''),
            'description' => $entry['description'][0] ?? '',
            'email' => $entry['mail'][0] ?? '',
            'groupTypeValue' => $groupTypeValue // Save raw value for debugging
        ];
    }
    
    usort($groups, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $groups;
}

/**
 * Group creation function
 * @param resource $ldap_conn LDAP connection
 * @param array $groupData Group data
 * @return array Operation result
 */
function createGroup($ldap_conn, $groupData) {
    if (empty($groupData['name'])) {
        throw new Exception(__('group_name_required'));
    }
    
    if (empty($groupData['ou'])) {
        throw new Exception(__('group_dn_required'));
    }
    
    // Group name format must be valid
    if (!preg_match('/^[a-zA-Z0-9\s\-\_\.]+$/', $groupData['name'])) {
        throw new Exception(__('group_name_invalid'));
    }
    
    // Prepare the group DN
    $groupDN = "CN=" . $groupData['name'] . "," . $groupData['ou'];
    
    // Scope and type values
    $type = isset($groupData['type']) ? $groupData['type'] : 'Security';
    $scope = isset($groupData['scope']) ? $groupData['scope'] : 'Global';
    
    // Active Directory Group Type calculation:
    // Security vs Distribution and Domain Local, Global, Universal Scope
    // https://docs.microsoft.com/en-us/windows/win32/adschema/a-grouptype
    
    $groupType = 0;
    
    // Base group type: Security or Distribution
    if ($type == 'Security') {
        $groupType |= 0x80000000; // Security Group
    } else {
        $groupType |= 0x00000000; // Distribution Group
    }
    
    // Scope: Domain Local, Global, Universal
    if ($scope == 'DomainLocal') {
        $groupType |= 0x00000004; // Domain Local Group
    } elseif ($scope == 'Global') {
        $groupType |= 0x00000002; // Global Group
    } elseif ($scope == 'Universal') {
        $groupType |= 0x00000008; // Universal Group
    }
    
    // Prepare group attributes
    $attributes = [
        'objectClass' => ['top', 'group'],
        'cn' => $groupData['name'],
        'sAMAccountName' => $groupData['name'],
        'groupType' => $groupType
    ];
    
    // If description is provided, add it
    if (!empty($groupData['description'])) {
        $attributes['description'] = $groupData['description'];
    }
    
    // If email is provided, add it
    if (!empty($groupData['email'])) {
        $attributes['mail'] = $groupData['email'];
    }
    
    // If notes are provided, add them
    if (!empty($groupData['notes'])) {
        $attributes['info'] = $groupData['notes'];
    }
    
    // Create the group
    $result = ldap_add($ldap_conn, $groupDN, $attributes);
    
    if (!$result) {
        $error = ldap_error($ldap_conn);
        throw new Exception(str_replace('{error}', $error, __('group_creation_failed')));
    }
    
    // Log the operation
    logActivity('CREATE_GROUP', $groupData['name'], str_replace('{type}', $type, str_replace('{scope}', $scope, __('group_created_success'))));
    
    return [
        'success' => true,
        'message' => __('group_created_success'),
        'dn' => $groupDN
    ];
}

/**
 * Group update function
 * @param resource $ldap_conn LDAP connection
 * @param array $groupData Group data
 * @return array Operation result
 */
function updateGroup($ldap_conn, $groupData) {
    error_log("updateGroup: Starting update for group: " . json_encode($groupData));
    error_log("updateGroup: LDAP connection type: " . gettype($ldap_conn));
    
    if (empty($groupData['dn'])) {
        error_log("updateGroup: No DN provided");
        throw new Exception(__('group_dn_required'));
    }
    
    try {
        // Retrieve group information
        error_log("updateGroup: Attempting to read group with DN: " . $groupData['dn']);
        $result = ldap_read($ldap_conn, $groupData['dn'], "(objectClass=group)", ['cn', 'distinguishedName']);
        
        if (!$result) {
            $error = ldap_error($ldap_conn);
            error_log("updateGroup: Error reading group: $error");
            throw new Exception(str_replace('{error}', $error, __('group_read_failed')));
        }
        
        error_log("updateGroup: Successfully read group, getting entries");
        $entries = ldap_get_entries($ldap_conn, $result);
        error_log("updateGroup: Found " . $entries['count'] . " entries");
        
        if ($entries['count'] == 0) {
            error_log("updateGroup: Group not found for DN: " . $groupData['dn']);
            throw new Exception(__('group_not_found'));
        }
        
        $oldName = $entries[0]['cn'][0];
        $dn = $groupData['dn'];
        $oldDN = $dn;
        
        error_log("updateGroup: Found group with name: $oldName, DN: $dn");
        
        // If name is changed, perform a rename operation
        if (!empty($groupData['name']) && $groupData['name'] !== $oldName) {
            error_log("updateGroup: Group name change detected: $oldName -> " . $groupData['name']);
            
            try {
                // Get the parent DN of the group object
                $dnParts = ldap_explode_dn($dn, 0);
                if (!$dnParts || !isset($dnParts['count']) || $dnParts['count'] < 2) {
                    error_log("updateGroup: Invalid group DN format: $dn");
                    throw new Exception(__('group_dn_invalid'));
                }
                
                array_shift($dnParts); // First element is DN count, needs to be removed
                array_shift($dnParts); // Remove the CN=GroupName part
                $parentDN = implode(',', $dnParts);
                
                // Rename operation
                $newRDN = "CN=" . $groupData['name'];
                error_log("updateGroup: Attempting to rename with ldap_rename to $newRDN, $parentDN");
                error_log("updateGroup: Full rename parameters - Old DN: $dn, New RDN: $newRDN, Parent DN: $parentDN");
                
                $result = ldap_rename($ldap_conn, $dn, $newRDN, $parentDN, true);
                
                if (!$result) {
                    $error = ldap_error($ldap_conn);
                    error_log("updateGroup: Failed to rename group: $error");
                    throw new Exception(str_replace('{error}', $error, __('group_rename_failed')));
                }
                
                // Get the new DN
                $dn = $newRDN . "," . $parentDN;
                error_log("updateGroup: Group renamed successfully, new DN: $dn");
                
                // Also update sAMAccountName
                error_log("updateGroup: Updating sAMAccountName to: " . $groupData['name']);
                error_log("updateGroup: sAMAccountName update parameters - DN: $dn, Name: " . $groupData['name']);
                $result = ldap_modify($ldap_conn, $dn, ['sAMAccountName' => $groupData['name']]);
                
                if (!$result) {
                    $error = ldap_error($ldap_conn);
                    error_log("updateGroup: Failed to update sAMAccountName: $error");
                    throw new Exception(str_replace('{error}', $error, __('group_samaccountname_update_failed')));
                }
                
                // Log the operation
                logActivity('RENAME_GROUP', $oldName . ' -> ' . $groupData['name'], __('group_renamed_success'));
            } catch (Exception $e) {
                error_log("updateGroup: Exception during rename: " . $e->getMessage());
                throw $e; // Re-throw to be caught at higher level
            }
        }
        
        // Attributes to be modified
        $attributes = [];
        
        // Description change
        if (isset($groupData['description'])) {
            error_log("updateGroup: Updating description: '" . $groupData['description'] . "'");
            $attributes['description'] = [$groupData['description']]; // LDAP format - array dəyər
        }
        
        // Email change
        if (isset($groupData['email'])) {
            error_log("updateGroup: Updating email: '" . $groupData['email'] . "'");
            $attributes['mail'] = [$groupData['email']]; // LDAP format - array dəyər
        }
        
        // Notes change
        if (isset($groupData['notes'])) {
            error_log("updateGroup: Updating notes: '" . $groupData['notes'] . "'");
            $attributes['info'] = [$groupData['notes']]; // LDAP format - array dəyər
        }
        
        // If attributes exist, modify them
        if (!empty($attributes)) {
            error_log("updateGroup: Modifying attributes: " . json_encode($attributes));
            error_log("updateGroup: Modify operation on DN: $dn");
            
            // Convert normal values to the correct LDAP format
            $modifyAttributes = []; // normal dəyərlər üçün
            $deleteAttributes = []; // empty values for removal
            
            foreach ($attributes as $key => $value) {
                if (empty($value[0]) && $value[0] !== '0') {
                    // Empty values for removal
                    error_log("updateGroup: Empty value for attribute $key, will remove from LDAP");
                    $deleteAttributes[$key] = [];
                } else {
                    // Normal values
                    $modifyAttributes[$key] = $value;
                }
            }
            
            error_log("updateGroup: Final attribute values for LDAP: " . json_encode($modifyAttributes));
            
            // First, modify normal values
            if (!empty($modifyAttributes)) {
                error_log("updateGroup: Modifying attributes with values: " . json_encode($modifyAttributes));
                $result = ldap_modify($ldap_conn, $dn, $modifyAttributes);
                
                if (!$result) {
                    $error = ldap_error($ldap_conn);
                    error_log("updateGroup: Failed to update group attributes: $error");
                    throw new Exception(str_replace('{error}', $error, __('group_attributes_update_failed')));
                }
                
                error_log("updateGroup: Attributes with values updated successfully");
            }
            
            // Then remove empty values
            if (!empty($deleteAttributes)) {
                error_log("updateGroup: Removing empty attributes: " . json_encode(array_keys($deleteAttributes)));
                $result = ldap_mod_del($ldap_conn, $dn, $deleteAttributes);
                
                if (!$result) {
                    $error = ldap_error($ldap_conn);
                    error_log("updateGroup: Failed to remove empty attributes: $error");
                    // This exception can be thrown, but the main changes are already applied
                } else {
                    error_log("updateGroup: Empty attributes removed successfully");
                }
            }
            
            // Verification
            $allAttributes = array_merge(array_keys($modifyAttributes), array_keys($deleteAttributes));
            if (!empty($allAttributes)) {
                $verifyResult = ldap_read($ldap_conn, $dn, "(objectClass=group)", $allAttributes);
                if ($verifyResult) {
                    $verifyEntries = ldap_get_entries($ldap_conn, $verifyResult);
                    error_log("updateGroup: Verification - Modified attributes current values: " . json_encode($verifyEntries));
                } else {
                    error_log("updateGroup: Verification failed - Could not read group after modification");
                }
            }
        } else {
            error_log("updateGroup: No attributes to update");
        }
        
        // If group type or scope changes are requested
        if (!empty($groupData['type']) || !empty($groupData['scope'])) {
            error_log("updateGroup: Type or scope change requested");
            
            try {
                // First, get current type
                $result = ldap_read($ldap_conn, $dn, "(objectClass=group)", ['groupType']);
                
                if (!$result) {
                    $error = ldap_error($ldap_conn);
                    error_log("updateGroup: Error reading group type: $error");
                    throw new Exception(str_replace('{error}', $error, __('group_type_read_failed')));
                }
                
                $entries = ldap_get_entries($ldap_conn, $result);
                
                if ($entries['count'] > 0) {
                    $currentGroupType = intval($entries[0]['grouptype'][0]);
                    $newGroupType = $currentGroupType;
                    
                    error_log("updateGroup: Current group type: $currentGroupType");
                    
                    // Group type change (Security/Distribution)
                    if (!empty($groupData['type'])) {
                        $isSecurity = ($currentGroupType & 0x80000000) == 0x80000000;
                        $newIsSecurity = ($groupData['type'] === 'Security');
                        
                        error_log("updateGroup: Type change: current is " . ($isSecurity ? "Security" : "Distribution") . 
                                  ", new is " . ($newIsSecurity ? "Security" : "Distribution"));
                        
                        if ($isSecurity !== $newIsSecurity) {
                            // Clear previous type
                            $newGroupType &= ~0x80000000;
                            
                            // Add new type
                            if ($newIsSecurity) {
                                $newGroupType |= 0x80000000; // Security Group
                            }
                            
                            error_log("updateGroup: Type change will be applied, new type: $newGroupType");
                        }
                    }
                    
                    // Group scope change (Domain Local, Global, Universal)
                    if (!empty($groupData['scope'])) {
                        $currentScope = getGroupScope($currentGroupType);
                        
                        error_log("updateGroup: Scope change: current is $currentScope, new is " . $groupData['scope']);
                        
                        // Clear previous scope bits (bits 0-2)
                        $newGroupType &= ~0x0000000F;
                        
                        // Add new scope
                        if ($groupData['scope'] === 'DomainLocal') {
                            $newGroupType |= 0x00000004; // Domain Local Group
                        } elseif ($groupData['scope'] === 'Global') {
                            $newGroupType |= 0x00000002; // Global Group
                        } elseif ($groupData['scope'] === 'Universal') {
                            $newGroupType |= 0x00000008; // Universal Group
                        }
                        
                        error_log("updateGroup: Scope change will be applied, new type: $newGroupType");
                    }
                    
                    // Type update
                    if ($newGroupType !== $currentGroupType) {
                        error_log("updateGroup: Updating group type from $currentGroupType to $newGroupType");
                        error_log("updateGroup: Type update parameters - DN: $dn, New Group Type: $newGroupType");
                        
                        // Verify groupType attribute is in the correct format - it should be integer
                        $typeLdapValue = intval($newGroupType);
                        error_log("updateGroup: groupType LDAP value type: " . gettype($typeLdapValue) . ", value: $typeLdapValue");
                        
                        // Perform the operation
                        $result = ldap_modify($ldap_conn, $dn, ['groupType' => $typeLdapValue]);
                        
                        if (!$result) {
                            $error = ldap_error($ldap_conn);
                            error_log("updateGroup: Failed to update group type: $error");
                            throw new Exception(str_replace('{error}', $error, __('group_type_update_failed')));
                        }
                        
                        error_log("updateGroup: Group type updated successfully");
                        
                        // Verify again by reading
                        $verifyResult = ldap_read($ldap_conn, $dn, "(objectClass=group)", ['groupType']);
                        if ($verifyResult) {
                            $verifyEntries = ldap_get_entries($ldap_conn, $verifyResult);
                            if ($verifyEntries['count'] > 0) {
                                error_log("updateGroup: Verification - New group type is: " . $verifyEntries[0]['grouptype'][0]);
                            }
                        }
                    } else {
                        error_log("updateGroup: No type or scope changes needed");
                    }
                }
            } catch (Exception $e) {
                error_log("updateGroup: Exception during type/scope update: " . $e->getMessage());
                throw $e; // Re-throw to be caught at higher level
            }
        }
        
        // Log the operation
        logActivity('UPDATE_GROUP', $oldName, __('group_updated_success'));
        
        error_log("updateGroup: Group update completed successfully");
        
        // Create an array to hold changed attributes
        $changedAttributes = [];
        if (isset($groupData['name']) && $groupData['name'] !== $oldName) {
            $changedAttributes['name'] = ['from' => $oldName, 'to' => $groupData['name']];
        }
        if (isset($groupData['description'])) {
            $changedAttributes['description'] = ['value' => $groupData['description']];
        }
        if (isset($groupData['email'])) {
            $changedAttributes['mail'] = ['value' => $groupData['email']];
        }
        if (isset($groupData['type'])) {
            $changedAttributes['type'] = ['value' => $groupData['type']];
        }
        if (isset($groupData['scope'])) {
            $changedAttributes['scope'] = ['value' => $groupData['scope']];
        }
        
        error_log("updateGroup: Changed attributes: " . json_encode($changedAttributes));
        
        return [
            'success' => true,
            'message' => __('group_updated_success'),
            'dn' => $dn,
            'changed_attributes' => $changedAttributes
        ];
    } catch (Exception $e) {
        error_log("updateGroup: Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        throw $e;
    }
}

/**
 * Group deletion function
 * @param resource $ldap_conn LDAP connection
 * @param string $groupDN Group DN
 * @return array Operation result
 */
function deleteGroup($ldap_conn, $groupDN) {
    if (empty($groupDN)) {
        throw new Exception(__('group_dn_required'));
    }
    
    // Get group name for logging
    $groupName = getGroupName($groupDN);
    
    // Delete the group
    $result = ldap_delete($ldap_conn, $groupDN);
    
    if (!$result) {
        $error = ldap_error($ldap_conn);
        throw new Exception(str_replace('{error}', $error, __('group_delete_failed')));
    }
    
    // Log the operation
    logActivity('DELETE_GROUP', $groupName, __('group_deleted_success'));
    
    return [
        'success' => true,
        'message' => __('group_deleted_success')
    ];
}

/**
 * Add user to group function
 * @param resource $ldap_conn LDAP connection
 * @param string $groupDN Group DN
 * @param array $memberDNs DNs of users to add
 * @return array Operation result
 */
function addGroupMembers($ldap_conn, $groupDN, $memberDNs) {
    if (empty($groupDN)) {
        throw new Exception(__('group_dn_required'));
    }
    
    if (empty($memberDNs) || !is_array($memberDNs)) {
        throw new Exception(__('group_members_array_required'));
    }
    
    // Get group name
    $groupName = getGroupName($groupDN);
    
    // Add each member to the group
    $successCount = 0;
    $skippedCount = 0;
    $failedMembers = [];
    
    foreach ($memberDNs as $memberDN) {
        try {
            $entry = ['member' => $memberDN];
            $result = ldap_mod_add($ldap_conn, $groupDN, $entry);
            
            if ($result) {
                $successCount++;
                
                // Get user name for logging
                $userName = getUserName($ldap_conn, $memberDN);
                
                // Log the operation
                logActivity('ADD_GROUP_MEMBER', "$userName to $groupName", __('group_member_added_success'));
            } else {
                $error = ldap_error($ldap_conn);
                
                // If already a member, count as skipped rather than failed
                if (strpos($error, 'LDAP_ALREADY_EXISTS') !== false) {
                    $skippedCount++;
                } else {
                    $failedMembers[] = [
                        'dn' => $memberDN,
                        'error' => $error
                    ];
                }
            }
        } catch (Exception $e) {
            $failedMembers[] = [
                'dn' => $memberDN,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return [
        'success' => true,
        'message' => str_replace('{count}', $successCount, __('group_members_added')),
        'added_count' => $successCount,
        'skipped_count' => $skippedCount,
        'failed_count' => count($failedMembers),
        'failed_members' => $failedMembers
    ];
}

/**
 * Remove user from group function
 * @param resource $ldap_conn LDAP connection
 * @param string $groupDN Group DN
 * @param array $memberDNs DNs of users to remove
 * @return array Operation result
 */
function removeGroupMembers($ldap_conn, $groupDN, $memberDNs) {
    if (empty($groupDN)) {
        throw new Exception(__('group_dn_required'));
    }
    
    if (empty($memberDNs) || !is_array($memberDNs)) {
        throw new Exception(__('group_members_array_required'));
    }
    
    // Get group name
    $groupName = getGroupName($groupDN);
    
    // Remove each member from the group
    $successCount = 0;
    $failedMembers = [];
    
    foreach ($memberDNs as $memberDN) {
        try {
            $entry = ['member' => $memberDN];
            $result = ldap_mod_del($ldap_conn, $groupDN, $entry);
            
            if ($result) {
                $successCount++;
                
                // Get user name for logging
                $userName = getUserName($ldap_conn, $memberDN);
                
                // Log the operation
                logActivity('REMOVE_GROUP_MEMBER', "$userName from $groupName", __('group_member_removed_success'));
            } else {
                $error = ldap_error($ldap_conn);
                $failedMembers[] = [
                    'dn' => $memberDN,
                    'error' => $error
                ];
            }
        } catch (Exception $e) {
            $failedMembers[] = [
                'dn' => $memberDN,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return [
        'success' => true,
        'message' => str_replace('{count}', $successCount, __('group_members_removed')),
        'successCount' => $successCount,
        'failedCount' => count($failedMembers),
        'failedMembers' => $failedMembers
    ];
}

/**
 * Get group name from DN
 * @param string $dn Group DN
 * @return string Group name
 */
function getGroupName($dn) {
    $parts = ldap_explode_dn($dn, 0);
    if (isset($parts[0])) {
        return preg_replace('/^CN=/i', '', $parts[0]);
    }
    return '';
}

/**
 * Get user name from DN
 * @param resource $ldap_conn LDAP connection
 * @param string $dn User DN
 * @return string User name
 */
function getUserName($ldap_conn, $dn) {
    try {
        $result = ldap_read($ldap_conn, $dn, "(objectClass=*)", ["displayName", "cn", "sAMAccountName"]);
        if ($result) {
            $entries = ldap_get_entries($ldap_conn, $result);
            if ($entries['count'] > 0) {
                // If display name exists, return it
                if (isset($entries[0]['displayname']) && $entries[0]['displayname']['count'] > 0) {
                    return $entries[0]['displayname'][0];
                }
                // If CN exists, return it
                if (isset($entries[0]['cn']) && $entries[0]['cn']['count'] > 0) {
                    return $entries[0]['cn'][0];
                }
                // If sAMAccountName exists, return it
                if (isset($entries[0]['samaccountname']) && $entries[0]['samaccountname']['count'] > 0) {
                    return $entries[0]['samaccountname'][0];
                }
            }
        }
    } catch (Exception $e) {
        // If an error occurs, extract CN from DN
        $parts = ldap_explode_dn($dn, 0);
        if (isset($parts[0])) {
            return preg_replace('/^CN=/i', '', $parts[0]);
        }
    }
    
    return $dn;
}

/**
 * Get all members of the group (with detailed information)
 * @param resource $ldap_conn LDAP connection
 * @param string $groupDN Group DN
 * @return array Group members list
 */
function getGroupMembersDetailed($ldap_conn, $groupDN) {
    if (empty($groupDN)) {
        throw new Exception('Group DN is required');
    }
    
    // Search for the group
    $result = ldap_read($ldap_conn, $groupDN, "(objectClass=group)", ["member"]);
    $entries = ldap_get_entries($ldap_conn, $result);
    
    if ($entries['count'] == 0 || !isset($entries[0]['member'])) {
        return [];
    }
    
    $members = [];
    $memberCount = $entries[0]['member']['count'];
    
    for ($i = 0; $i < $memberCount; $i++) {
        $memberDN = $entries[0]['member'][$i];
        
        // Get additional information about the member
        try {
            $memberResult = ldap_read($ldap_conn, $memberDN, "(objectClass=*)", [
                "displayName", "sAMAccountName", "userAccountControl",
                "objectClass", "mail", "department"
            ]);
            
            if ($memberResult) {
                $memberEntry = ldap_get_entries($ldap_conn, $memberResult);
                
                if ($memberEntry['count'] > 0) {
                    $objectClasses = [];
                    for ($j = 0; $j < $memberEntry[0]['objectclass']['count']; $j++) {
                        $objectClasses[] = $memberEntry[0]['objectclass'][$j];
                    }
                    
                    $isUser = in_array('user', $objectClasses);
                    $isGroup = in_array('group', $objectClasses);
                    $type = $isUser ? 'user' : ($isGroup ? 'group' : 'other');
                    
                    // Check if user account is enabled
                    $isEnabled = true;
                    if ($isUser && isset($memberEntry[0]['useraccountcontrol'])) {
                        $uac = $memberEntry[0]['useraccountcontrol'][0];
                        $isEnabled = !($uac & 2); // ACCOUNTDISABLE flag
                    }
                    
                    $members[] = [
                        'dn' => $memberDN,
                        'name' => $memberEntry[0]['displayname'][0] ?? 
                                 ($memberEntry[0]['samaccountname'][0] ?? getGroupName($memberDN)),
                        'username' => $memberEntry[0]['samaccountname'][0] ?? '',
                        'email' => $memberEntry[0]['mail'][0] ?? '',
                        'department' => $memberEntry[0]['department'][0] ?? '',
                        'type' => $type,
                        'enabled' => $isEnabled
                    ];
                }
            }
        } catch (Exception $e) {
            // If an error occurs, add minimal member information
            $members[] = [
                'dn' => $memberDN,
                'name' => getGroupName($memberDN),
                'type' => 'unknown'
            ];
        }
    }
    
    // Sort members by name
    usort($members, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $members;
}

/**
 * Get group scope (scope)
 * @param int $groupType Group Type flag
 * @return string Group scope
 */
function getGroupScope($groupType) {
    $groupType = intval($groupType);
    
    if ($groupType & 0x00000004) {
        return 'DomainLocal';
    } elseif ($groupType & 0x00000002) {
        return 'Global';
    } elseif ($groupType & 0x00000008) {
        return 'Universal';
    } else {
        return 'Unknown';
    }
}

/**
 * Get group type
 * @param int $groupType Group Type flag
 * @return string Group type (Security/Distribution)
 */
function getGroupSecurityType($groupType) {
    $groupType = intval($groupType);
    
    if ($groupType & 0x80000000) {
        return 'Security';
    } else {
        return 'Distribution';
    }
}

/**
 * Move group to another OU function
 * @param resource $ldap_conn LDAP connection
 * @param string $groupDN Current group DN
 * @param string $newOU New OU DN
 * @return array Operation result
 */
function moveGroupToOU($ldap_conn, $groupDN, $newOU) {
    error_log("moveGroupToOU: Started moving group from $groupDN to $newOU");
    
    // Check basic parameters
    if (empty($groupDN)) {
        error_log("moveGroupToOU: Empty group DN provided");
        throw new Exception(__('group_dn_required'));
    }
    
    if (empty($newOU)) {
        error_log("moveGroupToOU: Empty new OU provided");
        throw new Exception(__('group_new_ou_required'));
    }
    
    // Check if current OU is the same as new OU
    $dnParts = ldap_explode_dn($groupDN, 0);
    if (!$dnParts || !isset($dnParts['count']) || $dnParts['count'] < 2) {
        error_log("moveGroupToOU: Invalid group DN format: $groupDN");
        throw new Exception(__('group_dn_invalid'));
    }
    
    array_shift($dnParts); // First element is DN count, needs to be removed
    array_shift($dnParts); // Remove the CN=GroupName part
    $oldParentDN = implode(',', $dnParts);
    
    if (strcasecmp($oldParentDN, $newOU) === 0) {
        error_log("moveGroupToOU: Group is already in the specified OU: $newOU");
        throw new Exception(__('group_already_in_ou'));
    }
    
    try {
        // Get group information
        $result = ldap_read($ldap_conn, $groupDN, "(objectClass=group)", ['cn']);
        
        if (!$result) {
            $error = ldap_error($ldap_conn);
            error_log("moveGroupToOU: Error reading group: $error");
            throw new Exception(str_replace('{error}', $error, __('group_read_failed')));
        }
        
        $entries = ldap_get_entries($ldap_conn, $result);
        
        if ($entries['count'] == 0) {
            error_log("moveGroupToOU: Group not found: $groupDN");
            throw new Exception(__('group_not_found'));
        }
        
        // Get basic information
        $groupName = $entries[0]['cn'][0];
        error_log("moveGroupToOU: Found group with name: $groupName");
        
        // Check if destination OU exists
        $ouResult = ldap_read($ldap_conn, $newOU, "(objectClass=*)", ['ou']);
        
        if (!$ouResult) {
            $error = ldap_error($ldap_conn);
            error_log("moveGroupToOU: Error reading destination OU: $error");
            throw new Exception(str_replace('{error}', $error, __('ou_read_failed')));
        }
        
        $ouEntries = ldap_get_entries($ldap_conn, $ouResult);
        
        if ($ouEntries['count'] == 0) {
            error_log("moveGroupToOU: Destination OU not found: $newOU");
            throw new Exception(__('ou_not_found'));
        }
        
        // Form the new DN
        $newDN = "CN=" . $groupName . "," . $newOU;
        error_log("moveGroupToOU: New DN will be: $newDN");
        
        // Move operation
        error_log("moveGroupToOU: Attempting to move group with ldap_rename");
        $result = ldap_rename($ldap_conn, $groupDN, "CN=" . $groupName, $newOU, true);
        
        if (!$result) {
            $error = ldap_error($ldap_conn);
            error_log("moveGroupToOU: Failed to move group: $error");
            throw new Exception(str_replace('{error}', $error, __('group_move_failed')));
        }
        
        error_log("moveGroupToOU: Group successfully moved");
        
        // Format the new OU path
        $ou_path = formatOUPath($newDN);
        
        // Log the operation
        logActivity('MOVE_GROUP', $groupName, str_replace('{old}', $oldParentDN, str_replace('{new}', $newOU, __('group_moved_success'))));
        
        return [
            'success' => true,
            'message' => __('group_moved_success'),
            'dn' => $newDN,
            'ou_path' => $ou_path
        ];
    } catch (Exception $e) {
        error_log("moveGroupToOU: Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        throw $e;
    }
}