<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Symfony\Component\BrowserKit\Cookie;

class Acceptance extends \Codeception\Module
{
    public function getSessionCookie(): ?Cookie
    {
        if ($cookies = $this->getModule('PhpBrowser')->client->getCookieJar()->all()) {
            foreach ($cookies as $cookie) {
                $name = $cookie->getName();
                if (preg_match('/^SESS/', $name)) {
                    return $cookie;
                }
            }
        }
        return null;
    }

    public function setSessionCookie(Cookie $cookie)
    {
        $this->getModule('PhpBrowser')->client->getCookieJar()->set($cookie);
    }
}
