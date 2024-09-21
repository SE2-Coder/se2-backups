<?php
/**
 * Plugin Name: SE2 Backups
 * Description: Plugin para importar y exportar bases de datos, y comprimir la instalación de WordPress en un archivo ZIP.
 * Version: 1.0.2
 * Author: se2code
 * URI: https://www.se2code.com
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Crear los subdirectorios para los backups
function se2backups_create_backup_folders() {
    $backup_folder = plugin_dir_path(__FILE__) . 'db_backups';
    $zip_backup_folder = plugin_dir_path(__FILE__) . 'backups';
    
    if (!file_exists($backup_folder)) {
        mkdir($backup_folder, 0755, true);
    }

    if (!file_exists($zip_backup_folder)) {
        mkdir($zip_backup_folder, 0755, true);
    }
}
add_action('admin_init', 'se2backups_create_backup_folders');

// Función para exportar la base de datos
function se2backups_export_db() {
    $backup_folder = plugin_dir_path(__FILE__) . 'db_backups';
    $filename = 'db-backup-' . date('Ymd_His') . '.sql';
    
    $command = 'wp db export ' . escapeshellarg($backup_folder . '/' . $filename);
    shell_exec($command);
}

// Función para importar la base de datos
function se2backups_import_db($file) {
    $backup_folder = plugin_dir_path(__FILE__) . 'db_backups';
    
    $command = 'wp db import ' . escapeshellarg($backup_folder . '/' . $file);
    shell_exec($command);
}

// Función para crear un backup ZIP
function se2backups_create_zip_backup() {
    $backup_folder = plugin_dir_path(__FILE__) . 'backups';
    $zip_file_name = 'backup_' . date('Ymd_His') . '.zip';
    
    $root_path = ABSPATH;
    $zip_command = "zip -r {$zip_file_name} .";
    
    chdir($root_path);
    shell_exec($zip_command);

    if (file_exists($root_path . $zip_file_name)) {
        rename($root_path . $zip_file_name, $backup_folder . '/' . $zip_file_name);
    }
}

// Listar archivos de base de datos
function se2backups_list_db_files() {
    $backup_folder = plugin_dir_path(__FILE__) . 'db_backups';
    $files = array_diff(scandir($backup_folder), array('..', '.'));
    
    if (!empty($files)) {
        echo '<h2>Archivos de Base de Datos Disponibles</h2>';
        echo '<ul>';
        foreach ($files as $file) {
            $file_url = plugin_dir_url(__FILE__) . 'db_backups/' . $file;
            echo '<li><a href="' . $file_url . '" download>' . $file . '</a></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No hay backups de la base de datos disponibles.</p>';
    }
}

// Listar archivos ZIP
function se2backups_list_zip_files() {
    $backup_folder = plugin_dir_path(__FILE__) . 'backups';
    $files = array_diff(scandir($backup_folder), array('..', '.'));
    
    if (!empty($files)) {
        echo '<h2>Archivos de Backup Comprimidos Disponibles</h2>';
        echo '<ul>';
        foreach ($files as $file) {
            $file_url = plugin_dir_url(__FILE__) . 'backups/' . $file;
            echo '<li><a href="' . $file_url . '" download>' . $file . '</a></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No hay backups comprimidos disponibles.</p>';
    }
}

// Agregar opción al menú de administración
function se2backups_add_admin_menu() {
    add_menu_page(
        'SE2 Backups', 
        'SE2 Backups', 
        'manage_options', 
        'se2backups', 
        'se2backups_admin_page', 
        'dashicons-backup', 
        6
    );
}
add_action('admin_menu', 'se2backups_add_admin_menu');

// Crear la página de administración
function se2backups_admin_page() {
    if (isset($_POST['se2backups_export_db'])) {
        se2backups_export_db();
        echo '<div class="notice notice-success is-dismissible"><p>¡Backup de la base de datos creado exitosamente!</p></div>';
    }

    if (isset($_POST['se2backups_import_db']) && !empty($_POST['db_file'])) {
        se2backups_import_db($_POST['db_file']);
        echo '<div class="notice notice-success is-dismissible"><p>¡Base de datos importada exitosamente!</p></div>';
    }

    if (isset($_POST['se2backups_zip_backup'])) {
        se2backups_create_zip_backup();
        echo '<div class="notice notice-success is-dismissible"><p>¡Backup comprimido creado exitosamente!</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>SE2 Backups</h1>

        <h2>Opciones de la Base de Datos</h2>
        <form method="POST">
            <input type="submit" name="se2backups_export_db" class="button button-primary" value="Exportar Base de Datos">
        </form>

        <form method="POST">
            <h2>Importar Base de Datos</h2>
            <p>Selecciona un archivo de base de datos para importar:</p>
            <select name="db_file">
                <?php
                $backup_folder = plugin_dir_path(__FILE__) . 'db_backups';
                $files = array_diff(scandir($backup_folder), array('..', '.'));
                foreach ($files as $file) {
                    echo '<option value="' . $file . '">' . $file . '</option>';
                }
                ?>
            </select>
            <input type="submit" name="se2backups_import_db" class="button button-primary" value="Importar Base de Datos">
        </form>

        <hr>

        <h2>Crear backup de tu instalación de WordPress</h2>
        <form method="POST">
            <p>Presiona el botón para crear un backup comprimido (ZIP) de tu sitio.</p>
            <input type="submit" name="se2backups_zip_backup" class="button button-primary" value="Crear Backup ZIP">
        </form>

        <hr>

        <?php se2backups_list_db_files(); ?>
        <hr>
        <?php se2backups_list_zip_files(); ?>
    </div>
    <?php
}
