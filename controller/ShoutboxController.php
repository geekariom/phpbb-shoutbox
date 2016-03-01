<?php
/**
 * This file is a part of Shoutbox for phpbb
 *
 * @author Matthieu YK <yk@openmailbox.org>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @link https://bitbucket.org/matthieuy/phpbb-shoutbox
 */

namespace matthieuy\shoutbox\controller;

use matthieuy\shoutbox\services\ShoutboxManager;
use phpbb\auth\auth;
use phpbb\controller\helper;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ShoutboxController
 *
 * @package matthieuy\shoutbox\controller
 */
class ShoutboxController
{
    private $manager;
    private $request;
    private $auth;
    private $helper;
    private $template;
    private $user;

    /**
     * Constructor (DIC)
     * @param ShoutboxManager $manager
     * @param request         $request
     * @param auth            $auth
     * @param helper          $helper
     * @param template        $template
     * @param user            $user
     */
    public function __construct(ShoutboxManager $manager, request $request, auth $auth, helper $helper, template $template, user $user)
    {
        $this->manager = $manager;
        $this->request = $request;
        $this->auth = $auth;
        $this->helper = $helper;
        $this->template = $template;
        $this->user = $user;
    }

    /**
     * Full screen page
     * @return Response
     */
    public function fullscreen()
    {
        $title = $this->user->lang['SHOUTBOX'];
        $this->template->assign_var('SHOUTBOX_FULL', true);

        // Breadcrumbs
        $this->template->assign_block_vars('navlinks', array(
            'FORUM_NAME'	=> $title,
            'U_VIEW_FORUM'	=> $this->helper->route('matthieuy_shoutbox_fullscreen'),
        ));

        // View
        return $this->helper->render('shoutbox_full.html', $title);
    }

    /**
     * AJAX request from the shoutbox.js
     * @return JsonResponse|Response
     */
    public function ajax()
    {
        // Check request
        if (!$this->request->is_ajax()) {
            return new Response('Bad request', 400);
        }

        // Rights
        if (!$this->auth->acl_get('u_shoutbox_view')) {
            return new Response('Forbidden', 403);
        }

        $action = $this->request->variable('action', '');
        switch ($action) {
            // Smileys list
            case 'smileys':
                return $this->getSmileysList();

            // Check new message
            case 'check':
                return $this->checkMessage();

            // Scroll shoutbox : load more message
            case 'scroll':
                return $this->scroll();

            // send message
            case 'send':
                return $this->sendMessage();

            // Delete message
            case 'delete':
                return $this->deleteMessage();

            default:
                return new Response('Bad request', 400);
        }
    }

    /**
     * Get smilies list
     * @return JsonResponse
     */
    private function getSmileysList()
    {
        // Right
        if (!$this->auth->acl_get('u_shoutbox_send')) {
            return new Response('Forbidden', 403);
        }

        return new JsonResponse($this->manager->getSmileys());
    }

    /**
     * Get new messages
     * @return JsonResponse
     */
    private function checkMessage()
    {
        // Get news messages
        $last = intval($this->request->variable('last', 0));
        $liste = $this->manager->getMessageGreaterThan($last);

        return new JsonResponse($liste);
    }

    /**
     * Get more messages
     * @return JsonResponse
     */
    private function scroll()
    {
        $offset = intval($this->request->variable('offset', 0));
        $messages = $this->manager->getMessages($offset+1);

        return new JsonResponse($messages);
    }

    /**
     * Send a new message
     * @return JsonResponse
     */
    private function sendMessage()
    {
        $json = array('success' => false);

        // Right
        if (!$this->auth->acl_get('u_shoutbox_send')) {
            return new JsonResponse($json);
        }

        // Get message
        $msg = $this->request->variable('msg', '', true);

        if (!empty($msg)) {
            $messageId = $this->manager->insertMessage($msg);
            if ($messageId) {
                $json['success'] = true;
            }
        }

        return new JsonResponse($json);
    }

    /**
     * Delete a message
     * @return JsonResponse
     */
    private function deleteMessage()
    {
        $json = array('success' => false);

        // Get id
        $id = $this->request->variable('id', 0);
        if (!$id) {
            return new JsonResponse($json);
        }

        // Delete
        if ($this->manager->deleteById($id)) {
            $json['success'] = true;
        }

        return new JsonResponse($json);
    }
}
