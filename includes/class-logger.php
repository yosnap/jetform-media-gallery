<?php
/**
 * Clase para el sistema de registro y depuración
 *
 * @package JetForm_Media_Gallery
 * @since 1.0.0
 */

// No ejecutar directamente
if (!defined('ABSPATH')) {
    die('No direct script access allowed');
}

class JetForm_Media_Gallery_Logger {
    
    /**
     * Instancia de la clase principal
     */
    private $main;
    
    /**
     * Ruta del archivo de log
     */
    private $log_file;
    
    /**
     * Modo de depuración
     */
    private $debug_mode = false;
    
    /**
     * Tamaño máximo del archivo de log
     */
    private $max_log_size = 5242880; // 5MB en bytes
    
    /**
     * Número máximo de archivos de log
     */
    private $max_log_files = 5;
    
    /**
     * Constructor
     */
    public function __construct($main) {
        $this->main = $main;
        $this->log_file = WP_CONTENT_DIR . '/debug-media-gallery.log';
        
        $settings = $this->main->get_settings();
        
        // Configurar modo de depuración desde los ajustes
        $this->debug_mode = isset($settings['debug_mode']) && $settings['debug_mode'] === true;
        $this->max_log_size = absint($settings['max_log_size']) * 1024 * 1024; // Convertir MB a bytes
        $this->max_log_files = absint($settings['max_log_files']);
    }
    
    /**
     * Recargar configuraciones para garantizar que usamos el valor más actualizado
     */
    public function reload_settings() {
        $settings = $this->main->get_settings();
        $this->debug_mode = isset($settings['debug_mode']) && $settings['debug_mode'] === true;
        $this->max_log_size = absint($settings['max_log_size']) * 1024 * 1024;
        $this->max_log_files = absint($settings['max_log_files']);
        return $this->debug_mode;
    }
    
    /**
     * Registrar mensaje de depuración
     */
    public function log_debug($message) {
        // Recargar configuraciones para asegurar que estamos usando el valor actual
        if (!$this->reload_settings()) {
            return;
        }
        
        $this->maybe_rotate_log();
        
        $timestamp = current_time('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] {$message}\n";
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
    }
    
    /**
     * Rotar archivo de log si es necesario
     */
    public function maybe_rotate_log() {
        if (!file_exists($this->log_file)) {
            return;
        }

        $size = filesize($this->log_file);
        if ($size >= $this->max_log_size) {
            // Rotar archivos existentes
            for ($i = $this->max_log_files - 1; $i >= 1; $i--) {
                $old_file = $this->log_file . '.' . $i;
                $new_file = $this->log_file . '.' . ($i + 1);
                if (file_exists($old_file)) {
                    rename($old_file, $new_file);
                }
            }

            // Mover el archivo actual
            rename($this->log_file, $this->log_file . '.1');

            // Crear nuevo archivo vacío
            file_put_contents($this->log_file, '');
        }
    }
    
    /**
     * Limpiar todos los archivos de log
     */
    public function clear_logs() {
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
        
        // También eliminar archivos rotados
        for ($i = 1; $i <= $this->max_log_files; $i++) {
            $rotated_file = $this->log_file . '.' . $i;
            if (file_exists($rotated_file)) {
                unlink($rotated_file);
            }
        }
    }
    
    /**
     * Obtener el tamaño actual del archivo de log
     */
    public function get_log_size() {
        if (file_exists($this->log_file)) {
            return size_format(filesize($this->log_file), 2);
        }
        return '0 B';
    }
    
    /**
     * Obtener el contenido del archivo de log
     */
    public function get_log_content($lines = 1000) {
        if (!file_exists($this->log_file)) {
            return [];
        }
        
        $logs = file_get_contents($this->log_file);
        if (empty($logs)) {
            return [];
        }
        
        // Dividir en líneas y obtener las últimas
        $log_lines = array_filter(explode("\n", $logs));
        return array_slice($log_lines, -$lines);
    }
} 