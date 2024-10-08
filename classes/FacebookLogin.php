<?php
/**
 * FacebookLogin Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

class FacebookLogin {
    public $fb;


    public function getFacebookObject() {
        if (!FACEBOOK_APP_ID) {
            return;
        }
        if (!isset($this->fb)) {
            $this->fb = new \Facebook\Facebook([
                'app_id' => FACEBOOK_APP_ID,
                'app_secret' => FACEBOOK_APP_SECRET,
                'default_graph_version' => 'v2.2',
            ]);
        }

        return $this->fb;
    }

    public function getLoginURL() {
        $fb = $this->getFacebookObject();
        if (!$fb) {
            return;
        }
        $helper = $fb->getRedirectLoginHelper();
        $permissions = ['email'];
        return $helper->getLoginUrl('https://' . DOMAIN . '/user/login/fb.php', $permissions);
    }

    public function handleFacebookRedirect() {
        $helper = $this->getFacebookObject()->getRedirectLoginHelper();

        $data = ['login_url' => $this->getLoginURL()];

        try {
            $accessToken = $helper->getAccessToken();
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            $data['error'] = 'Graph returned an error: ' . $e->getMessage();
            return $data;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            $data['error'] = 'Facebook SDK returned an error: ' . $e->getMessage();
            return $data;
        }

        $token = $this->checkAccessToken($accessToken);

        if ($token) {
            return ['token' => $token];
        } else {
            $data['error'] = 'Problem getting Facebook token';
            return $data;
        }
    }


    private function checkAccessToken($accessToken) {
        if (!isset($accessToken)) {
            return false;
        }

        if (! $accessToken->isLongLived()) {
            $oAuth2Client = $this->getFacebookObject()->getOAuth2Client();
            try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                return false;
            }
        }

        return $accessToken;
    }

    public function loginUser($accessToken) {
        global $THEUSER;
        $user = $this->getFacebookUser($accessToken);

        // check for FB id first in case they have changed their
        // email address at the FB end
        $user_id = $THEUSER->facebook_id_exists($user['id'], true);
        if (!$user_id) {
            $user_id = $THEUSER->email_exists($user['email'], true);
        }

        $expires = intval($accessToken->getExpiresAt()->format('U'));
        twfy_debug("THEUSER", "Facebook access token expires at " . $expires);

        $success = false;
        if ($user_id) {
            twfy_debug("THEUSER", "Faceook user exists in the database: " . $user_id);
            $success = $THEUSER->init($user_id);
            twfy_debug("THEUSER", "user inited: " . $success);
            if ($THEUSER->facebook_id() == "") {
                $THEUSER->add_facebook_id($user['id']);
            }
            twfy_debug("THEUSER", "inited user has id : " . $THEUSER->user_id());
        } else {
            twfy_debug("THEUSER", "Faceook user does not exist in the database");
            $success = $this->createUser($accessToken, $user);
        }

        if ($success) {
            twfy_debug("THEUSER", "logging in user: " . $THEUSER->user_id());
            $logged_in = $THEUSER->facebook_login("/user/", $expires, $accessToken);

            if ($THEUSER->email() != $user['email']) {
                twfy_debug("THEUSER", "updating user details from Facebook");
                $this->updateUser($user);
            }

            return $logged_in;
        }

        return false;
    }

    public function createUser($accessToken, $user) {
        $name_parts = explode(' ', $user['name']);
        $first_name = array_shift($name_parts);
        $last_name = implode(' ', $name_parts);
        global $THEUSER;
        $details = [
            'firstname' => $first_name,
            'lastname' => $last_name,
            'email' => $user['email'],
            'postcode' => '',
            'url' => '',
            'status' => '',
            'password' => '',
            'optin' => false,
            'mp_alert' => false,
            'facebook_id' => $user['id'],
        ];
        $added = $THEUSER->add($details, false);
        if ($added) {
            twfy_debug("THEUSER", "Added new user from facebook details " . $THEUSER->user_id());
            return $THEUSER->confirm_without_token();
        }

        return $added;
    }

    public function updateUser($user) {
        global $THEUSER;

        $name_parts = explode(' ', $user['name']);
        $first_name = array_shift($name_parts);
        $last_name = implode(' ', $name_parts);

        $details = [
            'firstname' => $first_name,
            'lastname' => $last_name,
            'email' => $user['email'],
            'postcode' => $THEUSER->postcode(),
            'url' => $THEUSER->url(),
            'optin' => $THEUSER->optin(),
            'password' => '',
        ];

        $THEUSER->update_self_no_confirm($details);
    }

    public function getFacebookUser($accessToken) {
        $response = $this->getFacebookObject()->get('/me?fields=id,name,email', $accessToken);

        return $response->getGraphUser();
    }

}
