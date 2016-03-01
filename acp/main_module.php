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
 * Class main_module
 *
 * @package matthieuy\shoutbox\acp
 */
class main_module
{
    public $u_action;

    /**
     * Main
     * @param int $id
     * @param string $mode
     */
    public function main($id, $mode)
    {
        global $user, $request, $template, $config;

        $user->add_lang('acp/common');
        $this->tpl_name = 'shoutbox_config';
        $this->page_title = $user->lang('ACP_SHOUTBOX_TITLE');
        add_form_key('matthieuy/shoutbox');

        // Submit form
        if ($request->is_set_post('submit')) {
            if (!check_form_key('matthieuy/shoutbox')) {
                trigger_error('FORM_INVALID');
            }

            // Intervals
            $min = $this->getInt('shoutbox_min_interval', 3, 3, 30);
            $config->set('shoutbox_min', $min);
            $max = $this->getInt('shoutbox_max_interval', 30, 10, 300);
            $config->set('shoutbox_max', $max);

            // Radios
            $this->setBoolean('shoutbox_quote');
            $this->setBoolean('shoutbox_scroll');
            $this->setBoolean('shoutbox_timeago');
            $this->setBoolean('shoutbox_sound');

            // Success message
            trigger_error($user->lang('ACP_SHOUTBOX_SETTING_SAVED') . adm_back_link($this->u_action));
        }

        // Template
        $template->assign_vars(array(
            'U_ACTION' => $this->u_action,
            'SHOUTBOX_MIN_INT' => $config['shoutbox_min'],
            'SHOUTBOX_MAX_INT' => $config['shoutbox_max'],
            'SHOUTBOX_QUOTE' => $config['shoutbox_quote'],
            'SHOUTBOX_SCROLL' => $config['shoutbox_scroll'],
            'SHOUTBOX_TIMEAGO' => $config['shoutbox_timeago'],
            'SHOUTBOX_SOUND' => $config['shoutbox_sound'],
        ));
    }

    /**
     * Filter and get int value
     * @param string $name Config value name
     * @param int    $default Default value
     * @param int    $min Minimum value
     * @param int    $max Maximum value
     *
     * @return int Value
     */
    private function getInt($name, $default, $min, $max)
    {
        global $request;

        $value = intval($request->variable($name, $default));
        if (filter_var($value, FILTER_VALIDATE_INT, array(
            'options' => array(
                'default' => $default,
                'min_range' => $min,
                'max_range' => $max
            )
        )));

        return $value;
    }

    /**
     * Set radio value
     * @param string $name Config name
     * @param bool|true $default Default value
     */
    private function setBoolean($name, $default = true)
    {
        global $request, $config;

        $value = $request->variable($name, intval($default));
        if (in_array($value, array(0, 1))) {
            $config->set($name, $value);
        }
    }
}
