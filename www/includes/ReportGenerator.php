<?php
 /*
  * Copyright (C) [2025] [Ali Zeynalli] - All Rights Reserved
  * Unauthorized copying of this file, via any medium is strictly prohibited
  * Proprietary and confidential
  * Written by [Ali Zeynalli] <[https://linkedin.com/in/ali7zeynalli]> [2025]
  */
class ReportGenerator {
    private $ldap;
    
    public function __construct($ldap_conn) {
        $this->ldap = $ldap_conn;
    }

    public function generateReport($sections) {
        $data = [];
        foreach ($sections as $section) {
            switch ($section) {
                case 'users':
                    $users = getAllUsers($this->ldap);
                    $data['Users'] = $this->formatUserData($users);
                    break;
                case 'groups':
                    $groups = getAllGroups($this->ldap);
                    $data['Groups'] = $this->formatGroupData($groups);
                    break;
                case 'computers':
                    $computers = getAllComputers($this->ldap);
                    $data['Computers'] = $this->formatComputerData($computers);
                    break;
                case 'ous':
                    $ous = getAllOUs($this->ldap);
                    $data['OUs'] = $this->formatOUData($ous);
                    break;
                case 'gpos':
                    $gpos = $this->getAllGPOs();
                    $data['GPOs'] = $this->formatGPOData($gpos);
                    break;
            }
            
            // Ensure each section has at least an empty array, not null
            if (!isset($data[$section]) || $data[$section] === null) {
                $data[$section] = [];
            }
        }
        return $data;
    }

    private function formatUserData($users) {
        if (empty($users) || !is_array($users)) {
            return [];
        }
        
        $formatted = [];
        foreach ($users as $user) {
            if (!isset($user['samaccountname'])) continue;
            $formatted[] = [
                'Username' => $user['samaccountname'][0] ?? '',
                'Full Name' => $user['displayname'][0] ?? '',
                'Email' => $user['mail'][0] ?? '',
                'Department' => $user['department'][0] ?? '',
                'Status' => ($user['useraccountcontrol'][0] & 2) ? 'Disabled' : 'Enabled',
                'Last Logon' => isset($user['lastlogon'][0]) ? date('Y-m-d H:i:s', ($user['lastlogon'][0] / 10000000) - 11644473600) : 'Never'
            ];
        }
        return $formatted;
    }

    private function formatGroupData($groups) {
        if (empty($groups) || !is_array($groups)) {
            return [];
        }
        
        return array_map(function($group) {
            return [
                'Name' => $group['name'] ?? '',
                'Members' => $group['memberCount'] ?? 0,
                'Type' => $group['type'] ?? 'Unknown',
                'Description' => $group['description'] ?? ''
            ];
        }, $groups);
    }

    private function formatComputerData($computers) {
        // Check if computers array is empty
        if (empty($computers) || !is_array($computers)) {
            return [];
        }
        
        return array_map(function($computer) {
            return [
                'Name' => $computer['name'] ?? '',
                'OS' => $computer['os'] ?? 'Unknown',
                'Last Logon' => $computer['lastLogon'] ?? 'Never',
                'Status' => isset($computer['enabled']) && $computer['enabled'] ? 'Enabled' : 'Disabled'
            ];
        }, $computers);
    }

    private function formatOUData($ous) {
        // Check if OUs array is empty
        if (empty($ous) || !is_array($ous)) {
            return [];
        }
        
        return array_map(function($ou) {
            return [
                'Name' => $ou['name'] ?? '',
                'Path' => $ou['path'] ?? '',
                'Description' => $ou['description'] ?? '',
                'Created' => $ou['created'] ?? '',
                'Members' => $ou['memberCount'] ?? 0,
                'Type' => $ou['type'] ?? 'Organizational Unit'
            ];
        }, $ous);
    }

    private function getAllGPOs() {
        $config = require(__DIR__ . '/../config/config.php');
        $base_dn = $config['ad_settings']['base_dn'];
        $gpo_container = "CN=Policies,CN=System," . $base_dn;
        
        $filter = "(objectClass=groupPolicyContainer)";
        $attributes = ["displayName", "flags", "gPCFileSysPath", "whenCreated", "whenChanged", "description"];
        
        $result = ldap_search($this->ldap, $gpo_container, $filter, $attributes);
        return ldap_get_entries($this->ldap, $result);
    }

    private function formatGPOData($gpos) {
        if (empty($gpos) || !is_array($gpos) || !isset($gpos['count'])) {
            return [];
        }
        
        $formatted = [];
        for ($i = 0; $i < $gpos['count']; $i++) {
            $gpo = $gpos[$i];
            $formatted[] = [
                'Name' => $gpo['displayname'][0] ?? 'Unknown',
                'Type' => $this->determineGPOType($gpo['flags'][0] ?? 0),
                'Path' => $gpo['gpcfilesyspath'][0] ?? '',
                'Created' => isset($gpo['whencreated'][0]) ? formatLDAPDate($gpo['whencreated'][0]) : '',
                'Modified' => isset($gpo['whenchanged'][0]) ? formatLDAPDate($gpo['whenchanged'][0]) : '',
                'Description' => $gpo['description'][0] ?? ''
            ];
        }
        return $formatted;
    }

    private function determineGPOType($flags) {
        $flags = intval($flags);
        if ($flags & 1) return 'User';
        if ($flags & 2) return 'Computer';
        return 'Both';
    }

    public function saveToCSV($data, $filename) {
        if (!is_dir(__DIR__ . '/../reports')) {
            mkdir(__DIR__ . '/../reports', 0777, true);
        }

        $filepath = __DIR__ . '/../reports/' . $filename;
        
        // Add BOM for Excel UTF-8 support
        $output = "\xEF\xBB\xBF";
        $fp = fopen($filepath, 'w');
        fwrite($fp, $output);

        foreach ($data as $section => $items) {
            fputcsv($fp, [$section]); // Section header
            if (!empty($items)) {
                // Make sure we have at least one item before accessing array keys
                $headers = !empty($items[0]) && is_array($items[0]) ? array_keys($items[0]) : ['No data available'];
                fputcsv($fp, $headers); // Column headers
                
                foreach ($items as $item) {
                    fputcsv($fp, array_values($item));
                }
            } else {
                // Handle empty data
                fputcsv($fp, ['No data available']);
            }
            fputcsv($fp, []); // Empty line between sections
        }

        fclose($fp);
        chmod($filepath, 0644);
        
        return [
            'path' => '/reports/' . $filename,
            'fullpath' => $filepath,
            'filename' => $filename
        ];
    }

    public function saveToExcel($data, $filename) {
        if (!is_dir(__DIR__ . '/../reports')) {
            mkdir(__DIR__ . '/../reports', 0777, true);
        }

        $filepath = __DIR__ . '/../reports/' . $filename;
        
        // Create Excel-compatible HTML file
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>';
        
        foreach ($data as $sheetName => $items) {
            $html .= '<x:ExcelWorksheet><x:Name>' . $sheetName . '</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
        }
        
        $html .= '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
        </head><body>';
        
        foreach ($data as $section => $items) {
            $html .= '<table border="1">';
            $html .= '<tr><th colspan="5">' . $section . '</th></tr>';
            
            // Headers
            if (!empty($items)) {
                $html .= '<tr>';
                // Make sure we have at least one item before accessing array keys
                $headers = !empty($items[0]) && is_array($items[0]) ? array_keys($items[0]) : ['No data available'];
                foreach ($headers as $header) {
                    $html .= '<th style="background-color: #f0f0f0; font-weight: bold;">' . htmlspecialchars($header) . '</th>';
                }
                $html .= '</tr>';
                
                // Data
                foreach ($items as $item) {
                    $html .= '<tr>';
                    foreach ($item as $value) {
                        $html .= '<td>' . htmlspecialchars($value) . '</td>';
                    }
                    $html .= '</tr>';
                }
            } else {
                // Handle empty data
                $html .= '<tr><td>No data available</td></tr>';
            }
            $html .= '</table><br>';
        }
        
        $html .= '</body></html>';
        
        // Save file
        file_put_contents($filepath, $html);
        chmod($filepath, 0644);
        
        return [
            'path' => '/reports/' . $filename,
            'fullpath' => $filepath,
            'filename' => $filename
        ];
    }
}
