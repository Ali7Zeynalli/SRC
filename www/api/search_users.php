<?php
// api/search_users.php
session_start();
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['ad_username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $ldap_conn = getLDAPConnection();
    $config = require(getConfigPath());
    $base_dn = $config['ad_settings']['base_dn'];

    // Search for users matching the query in sAMAccountName or displayName
    $filter = "(&(objectClass=user)(objectCategory=person)(|(sAMAccountName=*$query*)(displayName=*$query*)))";
    
    // Limit results
    $result = ldap_search($ldap_conn, $base_dn, $filter, ['sAMAccountName', 'displayName', 'distinguishedName'], 0, 50);
    
    if (!$result) {
        echo json_encode([]);
        exit;
    }
    
    $entries = ldap_get_entries($ldap_conn, $result);
    $users = [];
    
    for ($i = 0; $i < $entries['count']; $i++) {
        $username = $entries[$i]['samaccountname'][0];
        $displayName = $entries[$i]['displayname'][0] ?? $username;
        $dn = $entries[$i]['distinguishedname'][0];
        
        $users[] = [
            'username' => $username,
            'displayName' => $displayName,
            'dn' => $dn
        ];
    }
    
    echo json_encode($users);

} catch (Exception $e) {
    error_log("Search API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
}
