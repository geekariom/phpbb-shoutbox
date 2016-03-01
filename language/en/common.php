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
    'SHOUTBOX_STRIKE' => 'Strike text : [s]text[/s]',
    'SHOUTBOX_SOUND' => 'Sound',
    'SHOUTBOX_DELETE_MSG' => 'Delete this message',
    'SHOUTBOX_SOUND_ENABLE' => 'Enable sound',
    'SHOUTBOX_SOUND_DISABLE' => 'Mute sound',
    'SHOUTBOX_STATS_NB' => array(
        1 => '<strong>%d</strong> shoutbox message',
        2 => '<strong>%d</strong> shoutbox messages'
    ),

    // Timeago
    'SHOUTBOX_AGO_SUFFIX' => 'ago',
    'SHOUTBOX_AGO_PREFIX' => '',
    'SHOUTBOX_AGO_SECONDS' => 'less than a minute',
    'SHOUTBOX_AGO_MINUTE' => 'about a minute',
    'SHOUTBOX_AGO_MINUTES' => '%d minutes',
    'SHOUTBOX_AGO_HOUR' => 'about an hour',
    'SHOUTBOX_AGO_HOURS' => 'about %d hours',

    // Notification
    'SHOUT_NOTIF_TXT' => '<strong>Message</strong> from %1$s in :',
    'NOTIFICATION_SHOUTBOX_QUOTE' => 'Someone quote you in the shoutbox',

    // ACP
    'ACP_SHOUTBOX_TITLE' => 'Shoutbox extension',
    'ACP_SHOUTBOX_MIN_INTERVAL' => 'Minimum interval between every check new message',
    'ACP_SHOUTBOX_MAX_INTERVAL' => 'Maximum interval between every check new message',
    'ACP_SHOUTBOX_SETTING_SAVED'	=> 'Settings have been saved successfully!',
    'ACP_SHOUTBOX_QUOTE' => 'Highlight message where you are quote',
    'ACP_SHOUTBOX_SCROLL' => 'Dynamic load when scroll shoutbox',
    'ACP_SHOUTBOX_TIMEAGO' => 'Dynamic refresh the date of message',
    'ACP_SHOUTBOX_SOUND' => 'Play a sound when you send or receive a message',
));
