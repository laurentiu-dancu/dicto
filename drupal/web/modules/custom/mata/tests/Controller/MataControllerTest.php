<?php

namespace Drupal\mata\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the mata module.
 */
class MataControllerTest extends WebTestBase {


  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "mata MataController's controller functionality",
      'description' => 'Test Unit for module mata and controller MataController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests mata functionality.
   */
  public function testMataController() {
    // Check that the basic functions of module mata.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
