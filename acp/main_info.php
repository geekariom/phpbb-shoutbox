<?php
/**
 * This file is a part of Shoutbox for phpbb
 *
 * @author Matthieu YK <yk@openmailbox.org>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @link https://bitbucket.org/matthieuy/phpbb-shoutbox
 * @package language
 */

namespace matthieuy\shoutbox\acp;

/**
 * Class main_info
 *
 * @package matthieuy\shoutbox\acp
 */
class main_info
{
    /**
     * Module config
     * @return array
     */
    public function module()
    {
        return array(
            'filename' => '\matthieuy\shoutbox\acp\main_module',
            'title' => 'ACP_SHOUTBOX_TITLE',
            'version' => '0.1.3',
            'modes' => array(
                'settings' => array(
                    'title' => 'SHOUTBOX',
                    'auth' => 'ext_matthieuy/shoutbox && acl_a_board',
                    'cat' => 'ACP_SHOUTBOX_TITLE'
                )
            )
        );
    }
}
