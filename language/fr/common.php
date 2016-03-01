<?php
/**
 * This file is a part of Shoutbox for phpbb
 *
 * @author Matthieu YK <yk@openmailbox.org>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @link https://bitbucket.org/matthieuy/phpbb-shoutbox
 * @package language
 */

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

$lang = array_merge($lang, array(
    // General
    'SHOUTBOX' => 'Shoutbox',
    'SHOUTBOX_STRIKE' => 'Barrer un texte : [s]texte[/s]',
    'SHOUTBOX_SOUND' => 'Son',
    'SHOUTBOX_DELETE_MSG' => 'Supprimer ce message',
    'SHOUTBOX_SOUND_ENABLE' => 'Mettre le son',
    'SHOUTBOX_SOUND_DISABLE' => 'Couper le son',
    'SHOUTBOX_STATS_NB' => array(
        1 => '<strong>%d</strong> message shoutbox',
        2 => '<strong>%d</strong> messages shoutbox'
    ),

    // Timeago
    'SHOUTBOX_AGO_SUFFIX' => '',
    'SHOUTBOX_AGO_PREFIX' => 'il y a',
    'SHOUTBOX_AGO_SECONDS' => 'moins d\'une minute',
    'SHOUTBOX_AGO_MINUTE' => 'une minute',
    'SHOUTBOX_AGO_MINUTES' => '%d minutes',
    'SHOUTBOX_AGO_HOUR' => 'une heure',
    'SHOUTBOX_AGO_HOURS' => '%d heures',

    // Notification
    'SHOUT_NOTIF_TXT' => '<strong>Message</strong> de %1$s dans :',
    'NOTIFICATION_SHOUTBOX_QUOTE' => 'Quelqu\'un vous a cité dans la shoutbox',

    // ACP
    'ACP_SHOUTBOX_TITLE' => 'Extension Shoutbox',
    'ACP_SHOUTBOX_MIN_INTERVAL' => 'Interval minimum entre chaque vérification de nouveau message',
    'ACP_SHOUTBOX_MAX_INTERVAL' => 'Interval maximum entre chaque vérification de nouveau message',
    'ACP_SHOUTBOX_SETTING_SAVED'	=> 'La configuration a été enregistrée avec succès !',
    'ACP_SHOUTBOX_QUOTE' => 'Mettre en surbrillance les messages où l\'on est cité',
    'ACP_SHOUTBOX_SCROLL' => 'Charger dynamiquement les messages lors du défilement',
    'ACP_SHOUTBOX_TIMEAGO' => 'Actualiser dynamiquement la date des messages',
    'ACP_SHOUTBOX_SOUND' => 'Jouer un son lors de l\'envoi ou de la reception d\'un message',
));
