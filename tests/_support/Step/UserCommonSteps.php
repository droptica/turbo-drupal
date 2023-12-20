<?php

namespace Step;

use Drupal\Pages\UserLoginPage;
use Drupal\Pages\HomePage;

/**
 * Class UserCommonSteps
 * @package Step
 */
trait UserCommonSteps
{
    /**
     * Login user.
     *
     * @param string $username
     *   Username.
     * @param string $password
     *   Password.
     */
    public function login($username = 'admin', $password = '123')
    {
        /** @var \JSCapableTester $I */
        $I = $this;
        $I->amOnPage(UserLoginPage::route());
        $url = $I->grabFromCurrentUrl();
        if ($url != '/user/login') {
            $this->logout();
            $I->amOnPage(UserLoginPage::route());
        }
        $I->fillField(UserLoginPage::$loginFormUsername, $username);
        $I->fillField(UserLoginPage::$loginFormPassword, $password);
        $I->click('Log in', UserLoginPage::$loginFormSubmit);
        $I->see('Admin');
    }

    /**
     * Logout user.
     */
    public function logout()
    {
        /** @var \JSCapableTester $I */
        $I = $this;
        $I->amOnPage(HomePage::route());
        $I->click('#block-topmenu a:last-child');
    }

    /**
     * Check if user is logged in.
     */
    public function userIsLoggedIn()
    {
        /** @var \JSCapableTester $I */
        $I = $this;
        $I->amOnPage(HomePage::route());
        $I->see('Log out');
    }
}
