<?php

class steamAuth
{
    private $api_key = '';//Steam api key
    private $domain_name = '';//Domain name (can be 127.0.0.1)
    private $login_page = '';//(index.php for this example)
    private $logout_page = '';//(index.php for this example)
    private $user_data = '';

    public function __construct()//Run checks on empty properties
    {
        if (empty($this->api_key)) {
            echo "<div style='display: block; width: 100%; background-color: #e33434; text-align: center;'>Please supply a Steam API key<br>Put it in class.php at line 5</div>";
            exit;
        }
        if (empty($this->domain_name))
            $this->domain_name = $_SERVER['SERVER_NAME'];
        if (empty($this->logout_page))
            $this->logout_page = $_SERVER['PHP_SELF'];
        if (empty($this->login_page))
            $this->login_page = $_SERVER['PHP_SELF'];
    }

    public function logoutButton(): void
    {
        echo "<form action='' method='get'><button name='logout' type='submit'>Logout</button></form>";
    }

    public function loginButton(string $button_style = "square"): void
    {
        ($button_style == 'square') ? $btn = '02' : $btn = '01';
        echo "<a href='?login'><img src='https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_$btn.png'></a>";
    }

    public function headerExit(string $location, bool $exit = true): void
    {
        header("Location: $location");
        if ($exit) {
            exit;
        }
    }

    public function logout(): void
    {
        $this->sessionDestroy();//Destroys session
        $this->headerExit($this->logout_page);
    }

    public function doAuth(): void
    {//Openid auth
        require_once('openid.php');
        try {
            $openid = new LightOpenID($this->domain_name);
            if (!$openid->mode) {
                $openid->identity = 'https://steamcommunity.com/openid';
                $this->headerExit($openid->authUrl(), false);
            } elseif ($openid->mode == 'cancel') {
                echo 'User has canceled authentication!';
            } else {
                if ($openid->validate()) {
                    $id = $openid->identity;
                    $ptn = "/^https?:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
                    preg_match($ptn, $id, $matches);
                    $_SESSION['steamid'] = $matches[1];
                    if (!headers_sent()) {
                        $this->headerExit($this->login_page);
                    } else {
                        ?>
                        <script type="text/javascript">
                            window.location.href = "<?=$this->login_page?>";
                        </script>
                        <noscript>
                            <meta http-equiv="refresh" content="0;url=<?= $this->login_page ?>"/>
                        </noscript>
                        <?php
                        exit;
                    }
                } else {
                    echo "User is not logged in";
                }
            }
        } catch (ErrorException $e) {
            echo $e->getMessage();
        }
    }

    public function userData(): array
    {//Player's steam data
        if (empty($_SESSION['steam_uptodate']) or empty($_SESSION['steam_personaname'])) {
            $content = json_decode(file_get_contents("https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $this->api_key . "&steamids=" . $_SESSION['steamid']), true);
            $_SESSION['steam_steamid'] = $content['response']['players'][0]['steamid'];
            $pd = $content['response']['players'][0];
        }
        (isset($pd['realname'])) ? $full_name = $pd['realname'] : $full_name = null;
        (isset($pd['lastlogoff'])) ? $llo = $pd['lastlogoff'] : $llo = null;
        $this->user_data = array(
            'steam_id' => $_SESSION['steam_steamid'],
            'name' => $pd['personaname'],
            'full_name' => $full_name,
            'avatar' => $pd['avatarfull'],
            'avatar_med' => $pd['avatarmedium'],
            'created' => $pd['timecreated'],
            'state' => $pd['personastate'],
            'last_logoff' => $llo,
            'profile_state' => $pd['profilestate'],
            'url' => $pd['profileurl'],
            'visibility' => $pd['communityvisibilitystate']
        );
        return $this->user_data;
    }

    public function sessionStart(): void
    {//Start session if none exists
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public function sessionDestroy(): void
    {//Destroys session
        session_unset();
        session_destroy();
    }

    public function doDateFormat(string $datetime, string $format_as = 'Y-m-d H:i:s'): string
    {//Format a date (i.e players account create time which comes as unix)
        return date($format_as, $datetime);
    }

    public function examplePlayerInfo(): void
    {
        (empty($this->user_data)) ? $user_data = $this->userData() : $user_data = $this->user_data;//Stored users data
        echo "Hello {$user_data['name']}</br>";
        echo "<img src=' " . $user_data['avatar'] . "' alt='{$user_data['name']} avatar'/><br>";
        echo "Account made: {$this->doDateFormat($user_data['created'], 'g:ia D jS M Y')}";
    }
}