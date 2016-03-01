<?php

namespace matthieuy\shoutbox\notification;

use phpbb\controller\helper;
use phpbb\notification\type\base;
use phpbb\user_loader;

/**
 * Class shoutbox_quote
 *
 * @package matthieuy\shoutbox\notification
 */
class shoutbox_quote extends base
{
    protected $language_key = 'SHOUT_NOTIF_TXT';
    private $helper;

    public static $notification_option = array(
        'group' => 'NOTIFICATION_GROUP_MISCELLANEOUS',
        'lang' => 'NOTIFICATION_SHOUTBOX_QUOTE'
    );

    /**
     * Constructeur (DIC)
     * @param helper                               $helper
     * @param user_loader                          $user_loader
     * @param \phpbb\db\driver\driver_interface    $db
     * @param \phpbb\cache\driver\driver_interface $cache
     * @param \phpbb\user                          $user
     * @param \phpbb\auth\auth                     $auth
     * @param \phpbb\config\config                 $config
     * @param string                               $phpbb_root_path
     * @param string                               $php_ext
     * @param string                               $notification_types_table
     * @param string                               $notifications_table
     * @param string                               $user_notifications_table
     */
    public function __construct(helper $helper, user_loader $user_loader, \phpbb\db\driver\driver_interface $db, \phpbb\cache\driver\driver_interface $cache, $user, \phpbb\auth\auth $auth, \phpbb\config\config $config, $phpbb_root_path, $php_ext, $notification_types_table, $notifications_table, $user_notifications_table)
    {
        $this->helper = $helper;
        parent::__construct($user_loader, $db, $cache, $user, $auth, $config, $phpbb_root_path, $php_ext, $notification_types_table, $notifications_table, $user_notifications_table);
    }

    /**
     * Get notification type name
     * @return string
     */
    public function get_type()
    {
        return 'matthieuy.shoutbox.notification.type.shoutbox_quote';
    }

    /**
     * Is notification available
     * @return bool
     */
    public function is_available()
    {
        return $this->auth->acl_get('u_shoutbox_view');
    }

    /**
     * Get the id of the item
     * @param array $data The type specific data
     *
     * @return int
     */
    public static function get_item_id($data)
    {
        return (int) $data['id'];
    }

    /**
     * Get the id of the parent
     * @param array $data The type specific data
     *
     * @return int
     */
    public static function get_item_parent_id($data)
    {
        return (int) $data['id'];
    }

    /**
     * Find the users who want to receive notifications
     * @param array $data The type specific data
     * @param array $options Options for finding users for notification
     *        ignore_users => array of users and user types that should not receive notifications from this type because they've already been notified
     *                        e.g.: array(2 => array(''), 3 => array('', 'email'), ...)
     *
     * @return array
     */
    public function find_users_for_notification($data, $options)
    {
        $options = array_merge(array(
            'ignore_users' => array()//$data['user_id'])
        ), $options);

        $users = $this->getUsersQuote($data);

        return $this->check_user_notification_options($users, $options);
    }

    /**
     * Get author avatar
     * @return string
     */
    public function get_avatar()
    {
        return $this->user_loader->get_avatar($this->get_data('user_id'));
    }

    /**
     * Function for preparing the data for insertion in an SQL query
     * (The service handles insertion)
     * @param array $data Data unique to this notification type
     * @param array $pre_create_data Data from pre_create_insert_array()
     *
     * @return array Array of data ready to be inserted into the database
     */
    public function create_insert_array($data, $pre_create_data = array())
    {
        $this->set_data('user_id', $data['user_id']);
        $this->set_data('id', $data['id']);
        $this->set_data('text', $data['text']);
        $this->set_data('date', $data['date']);

        return parent::create_insert_array($data, $pre_create_data);
    }

    /**
     * Users needed to query before this notification can be displayed
     * @return array Array of user_ids
     */
    public function users_to_query()
    {
        $data = $this->get_data(false);

        return $this->getUsersQuote($data);
    }

    /**
     * Get the HTML formatted title of this notification
     * @return string
     */
    public function get_title()
    {
        $user_id = (int) $this->get_data('user_id');
        $username = $this->user_loader->get_username($user_id, 'username');

        return $this->user->lang($this->language_key, $username);
    }

    /**
     * Get the url to this item
     * @return string URL
     */
    public function get_url()
    {
        return $this->helper->route('matthieuy_shoutbox_fullscreen').'#m'.$this->get_data('id');
    }

    /**
     * Ref
     * @return mixed
     */
    public function get_reference()
    {
        return $this->user->lang('SHOUTBOX');
    }

    /**
     * Get email template
     *
     * @return string|bool
     */
    public function get_email_template()
    {
        return '@matthieuy_shoutbox/shoutbox_email';
    }

    /**
     * Get email template variables
     * @return array
     */
    public function get_email_template_variables()
    {
        return array(
            'AUTHOR_NAME' => $this->user_loader->get_username($this->get_data('user_id'), 'username'),
            'U_SHOUTBOX' => $this->helper->route('matthieuy_shoutbox_fullscreen').'#m'.$this->get_data('id'),
            'U_NOTIFICATION_SETTINGS' => generate_board_url() . '/ucp.' . $this->php_ext . '?i=ucp_notifications',
        );
    }

    private function getUsersQuote($data)
    {
        $matchs = array();
        preg_match_all('#@([\S]+)#', $data['text'], $matchs, PREG_SET_ORDER);

        // Get username list
        $listUser = array();
        foreach ($matchs as $match) {
            $listUser[] = $match[1];
        }
        if (empty($listUser)) {
            return array();
        }

        // Get user_id from DB
        $sql = 'SELECT user_id FROM '.USERS_TABLE.' WHERE '.$this->db->sql_in_set('username', $listUser);
        $result = $this->db->sql_query($sql);
        $listUserId = array();
        while ($row = $this->db->sql_fetchrow($result)) {
            $listUserId[] = $row['user_id'];
        }
        $this->db->sql_freeresult($result);

        return $listUserId;
    }
}
