<?php
/**
 * phpMyAdmin Configuration for SHE Inspection System
 * Generated: <?php echo date('Y-m-d'); ?>
 */

// Server configuration
$cfg['blowfish_secret'] = 'SHE_Inspection_System_2026_phpmyadmin_secret_key_change_this';

// Servers configuration
$i = 0;

// First server - Main MySQL Server
$i++;
$cfg['Servers'][$i]['host']          = 'localhost';
$cfg['Servers'][$i]['port']          = '3306';
$cfg['Servers'][$i]['socket']        = '';
$cfg['Servers'][$i]['auth_type']     = 'config';
$cfg['Servers'][$i]['user']          = 'root';
$cfg['Servers'][$i]['password']      = '';
$cfg['Servers'][$i]['AllowNoPassword'] = true;

// phpMyAdmin configuration storage
$cfg['Servers'][$i]['pmadb']         = 'phpmyadmin';
$cfg['Servers'][$i]['relation']      = 'pma_relation';
$cfg['Servers'][$i]['table_info']    = 'pma_table_info';
$cfg['Servers'][$i]['table_coords']  = 'pma_table_coords';
$cfg['Servers'][$i]['pdf_pages']     = 'pma_pdf_pages';
$cfg['Servers'][$i]['column_info']   = 'pma_column_info';
$cfg['Servers'][$i]['bookmarktable'] = 'pma_bookmark';
$cfg['Servers'][$i]['history']       = 'pma_history';
$cfg['Servers'][$i]['designer_coords'] = 'pma_designer_coords';
$cfg['Servers'][$i]['tracking']      = 'pma_tracking';
$cfg['Servers'][$i]['userconfig']    = 'pma_userconfig';
$cfg['Servers'][$i]['recent']        = 'pma_recent';
$cfg['Servers'][$i]['favorite']      = 'pma_favorite';
$cfg['Servers'][$i]['users']         = 'pma_users';
$cfg['Servers'][$i]['usergroups']    = 'pma_usergroups';
$cfg['Servers'][$i]['navigationhiding'] = 'pma_navigationhiding';
$cfg['Servers'][$i]['savedsearches'] = 'pma_savedsearches';
$cfg['Servers'][$i]['central_columns'] = 'pma_central_columns';
$cfg['Servers'][$i]['designer_settings'] = 'pma_designer_settings';
$cfg['Servers'][$i]['export']        = 'pma_export';
$cfg['Servers'][$i]['tracking']      = 'pma_tracking';

// User for advanced features (optional - comment out if not needed)
$cfg['Servers'][$i]['controlhost']   = '';
$cfg['Servers'][$i]['controlport']   = '';
$cfg['Servers'][$i]['controluser']   = 'pma';
$cfg['Servers'][$i]['controlpass']   = '';

// General settings
$cfg['DefaultLang'] = 'en';
$cfg['ServerDefault'] = 1;
$cfg['UploadDir'] = '';
$cfg['SaveDir'] = '';
$cfg['TempDir'] = '';

// Security settings
$cfg['AllowUserDropDatabase'] = true;
$cfg['AllowArbitraryServer'] = false;

// Interface settings
$cfg['NavigationTreeEnableGrouping'] = true;
$cfg['MaxNavigationItems'] = 50;
$cfg['NavigationWidth'] = 240;

// Error reporting
$cfg['DBG']['sql'] = false;
$cfg['DBG']['profiling'] = false;

// Performance settings
$cfg['ExecTimeLimit'] = 300;
$cfg['MemoryLimit'] = '256M';

// Theme settings
$cfg['ThemeDefault'] = 'pmahomme';
$cfg['ThemeManager'] = true;

// Features
$cfg['Export']['sql'] = true;
$cfg['Import']['sql'] = true;
?>
