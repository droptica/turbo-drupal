<?php

namespace acceptance;

/**
 * Class BaseResponseCodeTestCest.
 */
abstract class BaseResponseCodeTestCest
{
    // Content types to be visited by anonymous user.
    public const ANONYMOUS_CONTENT_TYPES = [
      'article',
      'page',
    ];

    // Taxonomies to be visited by anonymous user.
    public const ANONYMOUS_TAXONOMY_VOCABULARIES = [
      'tags',
    ];

    // Content types to be visited by admin user.
    public const ADMIN_CONTENT_TYPES = [
    ];

    // Taxonomies to be visited by admin user.
    public const ADMIN_TAXONOMY_VOCABULARIES = [
    ];


    // URLs to be visited by anonymous user.
    public const ANONYMOUS_PAGES = [
      '/',
    ];

    // URLs to be visited by admin user.
    public const ADMIN_PAGES = [
      '/admin/modules',
      '/admin/reports/status',
      '/node/add/article',
      '/node/add/page',
    ];

    // How many nodes/terms to draw randomly?
    public const DRAW_QUANTITY = 5;
}
