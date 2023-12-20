<?php

namespace acceptance;

require_once('BaseResponseCodeTestCest.php');

use Codeception\Example;
use AcceptanceTester;

/**
 * Class AnonymousResponseCodeTestCest.
 */
class AnonymousResponseCodeTestCest extends BaseResponseCodeTestCest
{
    /**
     * Provider for nodes accessed as anonymous.
     */
    protected function anonymousNodeProvider()
    {
        $nodes = [];
        foreach (self::ANONYMOUS_CONTENT_TYPES as $bundle) {
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

    /**
     * Provider for taxonomies accessed as anonymous.
     */
    protected function anonymousTaxonomyProvider()
    {
        $nodes = [];
        foreach (self::ANONYMOUS_TAXONOMY_VOCABULARIES as $vocabulary) {
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
     * Provider for pages accessed as anonymous.
     */
    protected function anonymousPageProvider()
    {
        $pages = [];
        foreach (self::ANONYMOUS_PAGES as $url) {
            $pages[] = [
              'url' => $url,
            ];
        }

        return $pages;
    }

    /**
     * Test nodes as anonymous.
     *
     * @dataprovider anonymousNodeProvider
     */
    public function anonymousNodeResponseCodeTest(AcceptanceTester $I, Example $example)
    {
        $I->wantTo('Response Code Test on ' . $example['bundle'] . ' anonymous node: http://yoursite.localhost/node/' . $example['url']);
        $I->amOnPage('/node/' . $example['url']);
        $I->seeResponseCodeIs(200);
        $I->dontSee('The website encountered an unexpected error.');
        $I->dontSee('Er is onverwacht een fout opgetreden.');
        $I->dontSeeElement('.messages--error');
    }

    /**
     * Test taxonomies as anonymous.
     *
     * @dataprovider anonymousTaxonomyProvider
     */
    public function anonymousTaxonomyResponseCodeTest(AcceptanceTester $I, Example $example)
    {
        $I->wantTo('Response Code Test on ' . $example['vocabulary'] . ' anonymous node: http://yoursite.localhost/taxonomy/term/' . $example['url']);
        $I->amOnPage('/taxonomy/term/' . $example['url']);
        $I->seeResponseCodeIs(200);
        $I->dontSee('The website encountered an unexpected error.');
        $I->dontSee('Er is onverwacht een fout opgetreden.');
        $I->dontSeeElement('.messages--error');
    }

    /**
     * Test pages as anonymous.
     *
     * @dataprovider anonymousPageProvider
     */
    public function anonymousPageResponseCodeTest(AcceptanceTester $I, Example $example)
    {
        $I->wantTo('Response Code Test on anonymous page: http://yoursite.localhost' . $example['url']);
        $I->amOnPage($example['url']);
        $I->seeResponseCodeIs(200);
        $I->dontSee('The website encountered an unexpected error.');
        $I->dontSee('Er is onverwacht een fout opgetreden.');
        $I->dontSeeElement('.messages--error');
    }
}
