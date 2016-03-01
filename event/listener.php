<?php
/**
 * This file is a part of Shoutbox for phpbb
 *
 * @author Matthieu YK <yk@openmailbox.org>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @link https://bitbucket.org/matthieuy/phpbb-shoutbox
 */

namespace matthieuy\shoutbox\event;

use matthieuy\shoutbox\services\ShoutboxManager;
use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\notification\manager;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class listener
 *
 * @package matthieuy\shoutbox\event
 */
class listener implements EventSubscriberInterface
{
    private $helper;
    private $template;
    private $manager;
    private $auth;
    private $config;
    private $request;
    private $user;
    private $notification;

    /**
     * Constructor (DIC)
     * @param helper          $helper
     * @param template        $template
     * @param ShoutboxManager $manager
     * @param auth            $auth
     * @param config          $config
     * @param request         $request
     * @param user            $user
     * @param manager         $notification
     */
    public function __construct(helper $helper, template $template, ShoutboxManager $manager,
                                auth $auth, config $config, request $request, user $user, manager $notification)
    {
        $this->helper = $helper;
        $this->template = $template;
        $this->manager = $manager;
        $this->auth = $auth;
        $this->config = $config;
        $this->request = $request;
        $this->user = $user;
        $this->notification = $notification;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            'core.user_setup'  => 'load_language_on_setup',
            'core.page_footer' => 'assign_vars',
            'core.permissions' => 'permissions',
            'core.viewonline_overwrite_location' => 'viewonline_page',
            'core.index_modify_page_title' => 'stats',
            'matthieuy.shoutbox.insert' => 'insert',
        );
    }

    /**
     * Load language file
     * @param \phpbb\event\data $event
     */
    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = array(
            'ext_name' => 'matthieuy/shoutbox',
            'lang_set' => 'common',
        );
        $lang_set_ext[] = array(
            'ext_name' => '',
            'lang_set' => 'posting'
        );
        $event['lang_set_ext'] = $lang_set_ext;
    }

    /**
     * Assign vars to template
     */
    public function assign_vars()
    {
        // Right : can't see shoutbox
        if (!$this->auth->acl_get('u_shoutbox_view')) {
            return;
        }

        $messages = $this->manager->getMessages();
        $this->template->assign_vars(array(
            'shoutbox_msg'          => $messages,
            'U_SHOUTBOX'            => $this->helper->route('matthieuy_shoutbox_fullscreen'),
            'U_SHOUTBOX_AJAX'       => $this->helper->route('matthieuy_shoutbox_ajax'),
            'U_SHOUTBOX_THEME'      => 'ext/matthieuy/shoutbox/styles/prosilver/theme',
            'SHOUTBOX_CAN_SEND'     => $this->auth->acl_get('u_shoutbox_send'),
            'SHOUTBOX_MIN_INTERVAL' => $this->config['shoutbox_min'],
            'SHOUTBOX_MAX_INTERVAL' => $this->config['shoutbox_max'],
            'SHOUTBOX_LAST_MSG'     => (isset($messages[0]['TIMESTAMP'])) ? $messages[0]['TIMESTAMP'] : 0,
            'SHOUTBOX_QUOTE'        => $this->config['shoutbox_quote'],
            'SHOUTBOX_SCROLL'       => $this->config['shoutbox_scroll'],
            'SHOUTBOX_TIMEAGO'      => $this->config['shoutbox_timeago'],
            'SHOUTBOX_SOUND'        => $this->config['shoutbox_sound'],
            'SHOUTBOX_VOLUME'       => $this->request->variable('shoutbox_sound', 90, false, request::COOKIE),
        ));
    }

    /**
     * Translate permission
     * @param \phpbb\event\data $event
     */
    public function permissions($event)
    {
        // Categories
        $categories = array_merge($event['categories'], array(
            'shoutbox' => 'SHOUTBOX'
        ));

        // Permissions
        $permissions = array_merge($event['permissions'], array(
            'u_shoutbox_view'   => array('lang' => 'ACL_U_SHOUTBOX_VIEW', 'cat' => 'shoutbox'),
            'u_shoutbox_send'   => array('lang' => 'ACL_U_SHOUTBOX_SEND', 'cat' => 'shoutbox'),
            'u_shoutbox_self'   => array('lang' => 'ACL_U_SHOUTBOX_SELF', 'cat' => 'shoutbox'),
            'm_shoutbox_delete' => array('lang' => 'ACL_M_SHOUTBOX_DELETE', 'cat' => 'shoutbox'),
        ));

        $event['permissions'] = $permissions;
        $event['categories'] = $categories;
    }

    /**
     * Show user on Who is online page
     * @param \phpbb\event\data $event core.viewonline_overwrite_location
     */
    public function viewonline_page($event)
    {
        if (
            $event['row']['session_page'] == 'app.php/shoutbox' ||
            strpos($event['row']['session_page'], 'viewforum.php/shoutbox') === 0
        ) {
            $event['location'] = $this->user->lang('SHOUTBOX');
            $event['location_url'] = $this->helper->route('matthieuy_shoutbox_fullscreen');
        }
    }

    /**
     * Add number of message in stats on the homepage
     */
    public function stats()
    {
        $nb = $this->user->lang('SHOUTBOX_STATS_NB', $this->manager->getNbTotalMessage());
        $this->template->assign_var('TOTAL_SHOUTBOX', $nb);
    }

    public function insert($event)
    {
        if (strpos($event['text'], '@') !== false) {
            $notification_data = array(
                'id' => $event['id'],
                'user_id' => $event['user_id'],
                'text' => $event['text'],
                'date' => $event['create_at']
            );
            $this->notification->add_notifications(array('matthieuy.shoutbox.notification.type.shoutbox_quote'), $notification_data);
        }
    }
}
