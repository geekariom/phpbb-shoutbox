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
    'ACL_U_SHOUTBOX_VIEW' => 'Can see the shoutbox',
    'ACL_U_SHOUTBOX_SEND' => 'Can send a message',
    'ACL_U_SHOUTBOX_SELF' => 'Can delete him messages',
    'ACL_M_SHOUTBOX_DELETE' => 'Can delete any message',
));
