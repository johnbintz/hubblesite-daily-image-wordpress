<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) . '/../classes/DailyImageWidget.php');
require_once(dirname(__FILE__) . '/../../mockpress/mockpress.php');

class DailyImageWidgetTest extends PHPUnit_Framework_TestCase {
  function setUp() {
    _reset_wp();
    
    $this->diw = new DailyImageWidget();
    
    $this->sample_data = array(
      'title' => 'title',
      'caption' => 'caption',
      'date' => '12345',
      'image_url' => 'image_url',
      'gallery_url' => 'gallery_url',
      'credits' => 'credits'
    );
    
    $this->diw->data = $this->sample_data;    
  }

  function testWidgetRegistered() {
     global $wp_test_expectations;     
     $this->assertEquals("hubblesite-daily-image", $wp_test_expectations['sidebar_widgets'][0]['id']);
     $this->assertEquals("hubblesite-daily-image", $wp_test_expectations['widget_controls'][0]['name']);
  }

  function providerTestRetrieveJunkData() {
    return array(
      array(0),
      array(null),
      array(false),
      array(true),
      array(array()),
      array((object)array())
    ); 
  }
  
  /** 
   * @dataProvider providerTestRetrieveJunkData
   */
  function testRetrieveJunkData($bad_data) {
    $this->diw->data = $bad_data;

    ob_start();
    $this->diw->render();
    $result = ob_get_clean();
    
    $this->assertTrue(empty($result));
  }
  
  
  function providerTestTemplateOptions() {
    return array(
      array(
        "image",
        array(
          '//div[@id="hubblesite-daily-image"]' => true,
          '//div/a[@href="gallery_url" and @title="title"]' => true,
          '//div/a/img[@src="image_url" and @alt="title"]' => true,
        )
      ),
      array(
        "title",
        array(
          '//div/a[@href="gallery_url" and @id="hubblesite-daily-image-title"]' => "title"        
        )
      ),
      array(
        "styles",
        array(
          '//style[@type="text/css"]' => true
        )
      ),
      array(
        "caption",
        array(
          '//div/div[@id="hubblesite-daily-image-caption"]' => 'caption'
        )
      ),
      array(
        "credits",
        array(
          '//div/div[@id="hubblesite-daily-image-credits"]' => 'credits'
        )
      )
    ); 
  }
  
  /**
   * @dataProvider providerTestTemplateOptions
   */
  function testTemplateOptions($option_string, $xpath_tests) {
    update_option('hubblesite-daily-image-options', $option_string);
    
    ob_start();
    $this->diw->render();
    $result = ob_get_clean();
    
    $this->assertTrue(!empty($result));
    
    $this->assertTrue(($xml = _to_xml($result, true)) !== false);
    foreach ($xpath_tests as $xpath => $result) {
      $this->assertTrue(_xpath_test($xml, $xpath, $result), $xpath);
    }
  }
  
  function providerTestGetDisplayOptions() {
    return array(
      array("", array("title", "image", "styles")),
      array("meow", array("title", "image", "styles")),
      array("title", array("title")),
      array("title,image", array("title", "image")),
      array("title,meow", array("title"))
    ); 
  }
  
  /**
   * @dataProvider providerTestGetDisplayOptions
   */  
  function testGetDisplayOptions($options, $result) {
    update_option('hubblesite-daily-image-options', $options);
    
    $this->assertEquals($result, $this->diw->get_display_options());
  }
  
  function providerTestParseBadXML() {
    return array(
      array(null),
      array(false),
      array("<xml"),
      array("<xml></yml>")
    );
  }
  
  /**
   * @dataProvider providerTestParseBadXML
   */
  function testParseBadXML($xml) {
    foreach (array(true, false) as $simplexml) {
      $this->diw->has_simplexml = $simplexml;

      $this->assertFalse($this->diw->parse_xml($xml));
    }    
  }
  
  function testParseXML() {
    foreach (array(true, false) as $simplexml) {
      $this->diw->has_simplexml = $simplexml;
      
      $result = $this->diw->parse_xml(
        "<gallery>" .
          "<title>title</title>" .
          "<caption>caption</caption>" .
          "<date>12345</date>" .
          "<image_url>image_url</image_url>" .
          "<gallery_url>gallery_url</gallery_url>" .
          "<credits>credits</credits>" .
        "</gallery>"
      );
      
      $this->assertEquals(
        $this->sample_data,
        $result,
        "simplexml? $simplexml"
      );
    }
  }
  
  function testWidgetUI() {
    ob_start();
    $this->diw->render_ui();
    $result = ob_get_clean();
    
    $this->assertTrue(!empty($result));
    
    $this->assertTrue(($xml = _to_xml($result, true)) !== false);
    foreach ($this->diw->_valid_options as $option => $label) {
      $xpath = "//label[contains(text(), '${label}')]";      
      $this->assertTrue(_xpath_test($xml, $xpath, true), $xpath);
    }    
  }
  
  function testGetCachedData() {
    $test_time = time() + 86500;
    update_option('hubblesite-daily-image-cache', serialize(array($test_time, $this->sample_data)));
    $this->assertEquals($this->sample_data, $this->diw->_get_cached_data());

    $test_time = time() - 86500;
    update_option('hubblesite-daily-image-cache', serialize(array($test_time, $this->sample_data)));
    $this->assertEquals(false, $this->diw->_get_cached_data());

    update_option('hubblesite-daily-image-cache', null);
    $this->assertEquals(false, $this->diw->_get_cached_data());
  }
}

?>