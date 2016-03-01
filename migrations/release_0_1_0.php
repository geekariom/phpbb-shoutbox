<?php
/**
 * This file is a part of Shoutbox for phpbb
 *
 * @author Matthieu YK <yk@openmailbox.org>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @link https://bitbucket.org/matthieuy/phpbb-shoutbox
 */

namespace matthieuy\shoutbox\migrations;

use phpbb\db\migration\migration;

/**
 * Class release_0_1_0
 *
 * @package matthieuy\shoutbox\migrations
 */
class release_0_1_0 extends migration
{
    /**
     * Is install ?
     * @return bool
     */
    public function effectively_installed()
    {
        return isset($this->config['shoutbox_version']) && version_compare($this->config['shoutbox_version'], '0.1.3', '>=');
    }

    /**
     * Depends
     * @return array
     */
    public static function depends_on()
    {
        return array();
    }

    /**
     * Create table
     * @return array
     */
    public function update_schema()
    {
        return array(
            'add_tables' => array(
                $this->table_prefix . 'shoutbox' => array(
                    'COLUMNS' => array(
                        'id'        => array('UINT', null, 'auto_increment'),
                        'user_id'   => array('UINT', null),
                        'text'      => array('MTEXT', null),
                        'bitfield'  => array('VCHAR:255', null),
                        'uid'       => array('VCHAR:8', null),
                        'flags'     => array('INT:8', null),
                        'create_at' => array('TIMESTAMP', null)
                    ),
                    'PRIMARY_KEY'	=> 'id',
                ),
            )
        );
    }

    /**
     * Update data
     * @return array
     */
    public function update_data()
    {
        return array(
            array('config.add', array('shoutbox_version', '0.1.3')),
            array('config.add', array('shoutbox_min', 3)),
            array('config.add', array('shoutbox_max', 30)),
            array('config.add', array('shoutbox_quote', 1)),
            array('config.add', array('shoutbox_scroll', 1)),
            array('config.add', array('shoutbox_timeago', 1)),
            array('config.add', array('shoutbox_sound', 0)),
            array('permission.add', array('u_shoutbox_view')),
            array('permission.add', array('u_shoutbox_send')),
            array('permission.add', array('u_shoutbox_self')),
            array('permission.add', array('m_shoutbox_delete')),
            array('permission.permission_set', array('REGISTERED', 'u_shoutbox_view', 'group')),
            array('permission.permission_set', array('REGISTERED', 'u_shoutbox_send', 'group')),
            array('permission.permission_set', array('REGISTERED', 'u_shoutbox_self', 'group')),
            array('permission.permission_set', array('ROLE_MOD_STANDARD', 'm_shoutbox_delete')),
            array('permission.permission_set', array('ROLE_MOD_SIMPLE', 'm_shoutbox_delete')),
            array('permission.permission_set', array('ROLE_MOD_FULL', 'm_shoutbox_delete')),
            array('permission.permission_set', array('ROLE_USER_LIMITED', 'u_shoutbox_send', 'role', false)),
            array('permission.permission_set', array('ROLE_USER_LIMITED', 'u_shoutbox_self', 'role', false)),
            array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_SHOUTBOX_TITLE')),
            array('module.add', array('acp', 'ACP_SHOUTBOX_TITLE', array(
                'module_basename'	=> '\matthieuy\shoutbox\acp\main_module',
                'modes'				=> array('settings'),
            ))),
        );
    }

    /**
     * Remove table
     * @return array
     */
    public function revert_schema()
    {
        return array(
            'drop_tables' => array(
                $this->table_prefix . 'shoutbox',
            ),
        );
    }

    /**
     * Remove data
     * @return array
     */
    public function revert_data()
    {
        return array(
            array('config.remove',
                array('shoutbox_version'), array('shoutbox_min'), array('shoutbox_max'), array('shoutbox_quote'),
                array('shoutbox_scroll'), array('shoutbox_timeago'), array('shoutbox_sound'),
            ),
            array('permission.permission_unset', array('REGISTERED', 'u_shoutbox_view', 'group')),
            array('permission.permission_unset', array('REGISTERED', 'u_shoutbox_send', 'group')),
            array('permission.permission_unset', array('REGISTERED', 'u_shoutbox_self', 'group')),
            array('permission.permission_unset', array('ROLE_MOD_STANDARD', 'm_shoutbox_delete')),
            array('permission.permission_unset', array('ROLE_MOD_SIMPLE', 'm_shoutbox_delete')),
            array('permission.permission_unset', array('ROLE_MOD_FULL', 'm_shoutbox_delete')),
            array('permission.permission_unset', array('ROLE_USER_LIMITED', 'u_shoutbox_send')),
            array('permission.permission_unset', array('ROLE_USER_LIMITED', 'u_shoutbox_self')),
            array('permission.remove', array('u_shoutbox_view')),
            array('permission.remove', array('u_shoutbox_send')),
            array('permission.remove', array('u_shoutbox_self')),
            array('permission.remove', array('m_shoutbox_delete')),
            array('module.remove', array('acp', 'ACP_SHOUTBOX_TITLE')),
        );
    }
}
