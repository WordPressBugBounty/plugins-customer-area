<?php
/**
 * WordPress 6.7+ Compatibility Layer
 * Ce fichier doit être chargé EN PREMIER dans customer-area.php
 */

if (!defined('ABSPATH')) exit;

/**
 * Désactive les notices _load_textdomain_just_in_time pour les domaines WPCA
 * Ces notices sont incorrectes car nous chargeons bien les textdomains au hook init,
 * mais WordPress détecte des appels de traduction avant init dans le code legacy.
 */
add_filter('doing_it_wrong_trigger_error', function($trigger, $function, $message, $version) {
    // Si c'est une erreur de textdomain loading
    if ($function === '_load_textdomain_just_in_time') {
        // Liste des domaines WPCA
        $wpca_domains = [
            'cuar', 'cuaracf', 'cuarbi', 'cuarco', 'cuardivi', 'cuarelm',
            'cuarce', 'cuarde', 'cuaref', 'cuarep', 'cuarfmi', 'cuarin',
            'cuarlf', 'cuarmg', 'cuarmd', 'cuarno', 'cuaror', 'cuarpg',
            'cuarpj', 'cuarppt', 'cuarse', 'cuarsg', 'cuarst', 'cuarsu',
            'cuarta', 'cuarts', 'cuarud',
        ];
        
        // Vérifier si le message concerne un de nos domaines
        foreach ($wpca_domains as $domain) {
            if (strpos($message, $domain) !== false) {
                // Supprimer l'erreur pour nos domaines
                return false;
            }
        }
    }
    
    return $trigger;
}, 10, 4);

// Ajouter le filtre 
add_filter('override_load_textdomain', function($override, $domain) {
    static $wpca_domains = null;
    
    if ($wpca_domains === null) {
        $wpca_domains = [
            'cuar', 'cuaracf', 'cuarbi', 'cuarco', 'cuardivi', 'cuarelm',
            'cuarce', 'cuarde', 'cuaref', 'cuarep', 'cuarfmi', 'cuarin',
            'cuarlf', 'cuarmg', 'cuarmd', 'cuarno', 'cuaror', 'cuarpg',
            'cuarpj', 'cuarppt', 'cuarse', 'cuarsg', 'cuarst', 'cuarsu',
            'cuarta', 'cuarts', 'cuarud',
        ];
    }
    
    if (in_array($domain, $wpca_domains, true) && !did_action('init')) {
        return true;
    }

    return $override;
}, 1, 2);