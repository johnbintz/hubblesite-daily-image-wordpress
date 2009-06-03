<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) . '/../classes/DailyImageWidget.php');

class DailyImageWidgetTest extends PHPUnit_Framework_TestCase {
  function setUp() {
    $this->diw = new DailyImageWidget();
  }

  function testRetrieveDataFailure() {
    $this->diw->data = false;
    
    ob_start();
    $this->diw->render();
    $result = ob_get_clean();
    
    $this->assertTrue(empty($result));
  }
}

?>