<?php
/**
 * This file is a part of Shoutbox for phpbb
 *
 * @author Matthieu YK <yk@openmailbox.org>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @link https://bitbucket.org/matthieuy/phpbb-shoutbox
 */

namespace matthieuy\shoutbox\services;

use phpbb\auth\auth;
use phpbb\cache\service;
use phpbb\config\config;
use phpbb\db\driver\factory;
use phpbb\event\dispatcher;
use phpbb\user;

/**
 * Class ShoutboxManager
 * @package matthieuy\shoutbox\services
 */
class ShoutboxManager
{
    private $db;
    private $user;
    private $auth;
    private $config;
    private $cache;
    private $dispatcher;

    /**
     * Constructor (DIC)
     * @param factory    $db
     * @param user       $user
     * @param auth       $auth
     * @param config     $config
     * @param service    $cache
     * @param dispatcher $dispatcher
     */
    public function __construct(factory $db, user $user, auth $auth, config $config, service $cache, dispatcher $dispatcher)
    {
        $this->db = $db;
        $this->user = $user;
        $this->auth = $auth;
        $this->config = $config;
        $this->cache = $cache->get_driver();
        $this->dispatcher = $dispatcher;
    }

    /**
     * Get message
     * @param int $first Offset
     * @param int $nb Limit
     *
     * @return array List of message
     */
    public function getMessages($first = 0, $nb = 30)
    {
        if (($listMsg = $this->cache->get('shoutbox_msg')) === false) {
            global $table_prefix;

            $sql = 'SELECT s.*, u.username
                    FROM ' . $table_prefix . 'shoutbox AS s
                    LEFT JOIN ' . USERS_TABLE . ' AS u ON s.user_id = u.user_id
                    ORDER BY s.create_at DESC';
            $result = $this->db->sql_query_limit($sql, $nb, $first);
            $rows = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);

            $listMsg = array();
            foreach ($rows as $row) {
                $listMsg[] = $this->convertToMsg($row);
            }

            $this->cache->put('shoutbox_msg', $listMsg, 3600);
        }

        return $listMsg;
    }

    /**
     * Get message greater (by timestamp)
     * @param int $timestamp
     *
     * @return array
     */
    public function getMessageGreaterThan($timestamp)
    {
        global $table_prefix;
        $timestamp = intval($timestamp);

        $sql = 'SELECT s.*, u.username
                FROM '.$table_prefix.'shoutbox AS s
                LEFT JOIN '.USERS_TABLE.' AS u ON s.user_id = u.user_id
                WHERE s.create_at > '.$timestamp.'
                ORDER BY s.create_at DESC';
        $result = $this->db->sql_query($sql);
        $rows = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);

        $list = array();
        foreach ($rows as $row) {
            $list[] = $this->convertToMsg($row);
        }

        return $list;
    }

    /**
     * Get the number of all message
     * @return int
     */
    public function getNbTotalMessage()
    {
        if (($nb = $this->cache->get('shoutbox_nb')) === false) {
            global $table_prefix;

            $sql = 'SELECT COUNT(*) AS nb
                    FROM ' . $table_prefix . 'shoutbox';
            $result = $this->db->sql_query($sql);
            $nb = (int)$this->db->sql_fetchfield('nb');
            $this->db->sql_freeresult($result);

            $this->cache->put('shoutbox_nb', $nb, 3600);
        }

        return $nb;
    }

    /**
     * Delete a message by this id
     * @param int $id id message
     *
     * @return bool Success
     */
    public function deleteById($id)
    {
        global $table_prefix;
        $id = intval($id);

        // Get message
        $sql = 'SELECT user_id FROM '.$table_prefix.'shoutbox WHERE id = '.$id;
        $result = $this->db->sql_query_limit($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!isset($row['user_id'])) {
            return false;
        }

        // Delete
        if ($this->auth->acl_get('m_shoutbox_delete') || ($row['user_id'] == $this->user->data['user_id'] && $this->auth->acl_get('u_shoutbox_self'))) {
            $sql = 'DELETE FROM '.$table_prefix.'shoutbox WHERE id = '.$id;
            $this->db->sql_query_limit($sql, 1);

            $this->cache->destroy('shoutbox_nb');
            $this->cache->destroy('shoutbox_msg');

            return true;
        }

        return false;
    }

    /**
     * Insert message into db
     * @param string $message The text message
     *
     * @return int message ID
     */
    public function insertMessage($message)
    {
        global $table_prefix;

        $uid = ''; $bitfield = ''; $flags = '';
        generate_text_for_storage($message, $uid, $bitfield, $flags, true, true, true);

        $sql_ary = [
            'user_id' => $this->user->data['user_id'],
            'text' => $message,
            'bitfield' => $bitfield,
            'uid' => $uid,
            'flags' => $flags,
            'create_at' => time()
        ];

        $sql = 'INSERT INTO '.$table_prefix.'shoutbox '.$this->db->sql_build_array('INSERT', $sql_ary);
        $this->db->sql_query($sql);
        $sql_ary['id'] = $this->db->sql_nextid();

        extract($this->dispatcher->trigger_event('matthieuy.shoutbox.insert', $sql_ary));

        $this->cache->destroy('shoutbox_nb');
        $this->cache->destroy('shoutbox_msg');

        return $sql_ary['id'];
    }

    /**
     * Get smilies
     * @return array
     */
    public function getSmileys()
    {
        if (($smilies = $this->cache->get('shoutbox_smilies')) === false) {
            global $phpbb_root_path, $config;

            $sql = 'SELECT code, emotion, smiley_url, smiley_width, smiley_height
                    FROM ' . SMILIES_TABLE . '
                    WHERE display_on_posting = 1
                    ORDER BY smiley_order';
            $result = $this->db->sql_query($sql);

            $smilies = array();
            while ($row = $this->db->sql_fetchrow($result)) {
                $smilies[] = array(
                    'code' => $row['code'],
                    'emotion' => $row['emotion'],
                    'url' => $phpbb_root_path . $config['smilies_path'] . '/' . $row['smiley_url'],
                    'height' => $row['smiley_height'],
                    'width' => $row['smiley_width']
                );
            }
            $this->db->sql_freeresult($result);

            $this->cache->put('shoutbox_smilies', $smilies, 3600);
        }

        return $smilies;
    }

    /**
     * Convert msg to array (for json)
     * @param array $row The row message
     *
     * @return array
     */
    private function convertToMsg(array $row)
    {
        return array(
            'ID' => $row['id'],
            'TIMESTAMP' => $row['create_at'],
            'DATE' => date('c', $row['create_at']),
            'DATE_USER' => $this->user->format_date($row['create_at']),
            'TEXT' => generate_text_for_display($row['text'], $row['uid'], $row['bitfield'], $row['flags']),
            'USER' => $row['username'],
            'QUOTE' => ($this->config['shoutbox_quote'] && strpos($row['text'], '@'.$this->user->data['username']) !== false),
            'CAN_DELETE' => ($this->auth->acl_get('m_shoutbox_delete') || ($row['user_id'] == $this->user->data['user_id'] && $this->auth->acl_get('u_shoutbox_self'))),
            'TIMEAGO' => ($this->config['shoutbox_timeago'] && $row['create_at'] >= time() - 3600),
        );
    }
}
