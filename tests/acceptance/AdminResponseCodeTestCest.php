<?php

namespace acceptance;

require_once('BaseResponseCodeTestCest.php');

use Codeception\Example;
use Step\Acceptance\UserSteps;
use AcceptanceTester;
use Symfony\Component\BrowserKit\Cookie;

/**
 * Class ResponseCodeTestCest.
 */
class AdminResponseCodeTestCest extends BaseResponseCodeTestCest
{
    public Cookie $cookie;

    /**
     * Before the test.
     */
    public function beforeAllTests(AcceptanceTester $I, UserSteps $U)
    {
        $U->login();
        $this->cookie = $I->getSessionCookie();
    }

    /**
     * Provider for admin-access nodes.
     */
    protected function adminNodeProvider()
    {
        $nodes = [];
        $all_content_types = array_merge(
            self::ANONYMOUS_CONTENT_TYPES,
            self::ADMIN_CONTENT_TYPES
        );
        foreach ($all_content_types as $bundle) {
            $query = \Drupal::database()->select('node_field_data', 'nd');
            $query->fields('nd', ['nid', 'type', 'status']);
            $query->range(0, self::DRAW_QUANTITY);
            $query->condition('type', $bundle);
            $query->condition('status', '1');
            $query->orderRandom();
            $result = $query->execute();

            while ($row = $result->fetchAssoc()) {
                $nodes[] = [
                  'url' => $row['nid'],
                  'bundle' => $bundle,
                ];
            }
        }
        return $nodes;
    }

    protected function adminTaxonomyProvider()
    {
        $nodes = [];
        $all_vocabularies = array_merge(
            self::ANONYMOUS_TAXONOMY_VOCABULARIES,
            self::ADMIN_TAXONOMY_VOCABULARIES
        );
        foreach ($all_vocabularies as $vocabulary) {
            $query = \Drupal::database()->select('taxonomy_term_field_data', 'td');
            $query->fields('td', ['tid', 'vid', 'status']);
            $query->range(0, self::DRAW_QUANTITY);
            $query->condition('vid', $vocabulary);
            $query->condition('status', '1');
            $query->orderRandom();
            $result = $query->execute();

            while ($row = $result->fetchAssoc()) {
                $nodes[] = [
                  'url' => $row['tid'],
                  'vocabulary' => $vocabulary,
                ];
            }
        }
        return $nodes;
    }

  /**
   * Provider for pages accessed as admin.
   */
    protected function adminPageProvider()
    {
        $all_pages = array_merge(
            self::ANONYMOUS_PAGES,
            self::ADMIN_PAGES
        );
        $pages = [];
        foreach ($all_pages as $url) {
            $pages[] = [
              'url' => $url,
            ];
        }

        return $pages;
    }

    /**
     * Test nodes as admin.
     *
     * @dataprovider adminNodeProvider
     */
    public function adminNodeResponseCodeTest(AcceptanceTester $I, Example $example)
    {
        if (!empty($this->cookie)) {
            $I->setSessionCookie($this->cookie);
        }
        $I->wantTo('Response Code Test on ' . $example['bundle'] . ' admin node: http://yoursite.localhost/node/' . $example['url']);
        $I->amOnPage('/node/' . $example['url']);
        $I->seeResponseCodeIs(200);
        $I->see('Admin');
        $I->dontSee('The website encountered an unexpected error.');
        $I->dontSee('Er is onverwacht een fout opgetreden.');
        $I->dontSeeElement('.messages--error');
    }

    /**
     * Test taxonomies as anonymous.
     *
     * @dataprovider adminTaxonomyProvider
     */
    public function anonymousTaxonomyResponseCodeTest(AcceptanceTester $I, Example $example)
    {
        $I->wantTo('Response Code Test on ' . $example['vocabulary'] . ' admin node: http://yoursite.localhost/taxonomy/term/' . $example['url']);
        $I->amOnPage('/taxonomy/term/' . $example['url']);
        $I->seeResponseCodeIs(200);
        $I->see('Admin');
        $I->dontSee('The website encountered an unexpected error.');
        $I->dontSee('Er is onverwacht een fout opgetreden.');
        $I->dontSeeElement('.messages--error');
    }

    /**
     * Test pages as admin.
     *
     * @dataprovider adminPageProvider
     */
    public function adminPageResponseCodeTest(AcceptanceTester $I, Example $example)
    {
        if (!empty($this->cookie)) {
            $I->setSessionCookie($this->cookie);
        }
        $I->wantTo('Response Code Test on admin page: http://yoursite.localhost' . $example['url']);
        $I->amOnPage($example['url']);
        $I->seeResponseCodeIs(200);
        $I->dontSee('Er is onverwacht een fout opgetreden.');
        $I->dontSee('The website encountered an unexpected error.');
        $I->dontSeeElement('.messages--error');
    }
}
