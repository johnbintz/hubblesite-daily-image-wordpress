<?php

error_reporting(E_STRICT);
require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) . '/../classes/DailyImageWidget.php');
require_once(dirname(__FILE__) . '/../../mockpress/mockpress.php');

class DailyImageWidgetTest extends PHPUnit_Framework_TestCase {
  function setUp() {
    _reset_wp();
    wp_create_nonce("hubble");
    $_POST = array();
    
    $this->diw = new DailyImageWidget(true);
    
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
     $this->assertEquals("_init", $wp_test_expectations['actions']['init'][1]);
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
  
  function testGetDefaultDisplayOptions() {
    _reset_wp();
    $this->assertFalse(get_option('hubblesite-daily-image-options'));    
    $this->diw->get_display_options();
    $this->assertTrue(get_option('hubblesite-daily-image-options') !== false);
  }
  
  function testCheckedOptions() {
    $this->diw->display_options = array_keys($this->diw->_valid_options);
    
    ob_start();
    $this->diw->render_ui();
    $result = ob_get_clean();
    
    $this->assertTrue(($xml = _to_xml($result, true)) !== false);
    
    foreach ($this->diw->display_options as $option) {
      $this->assertTrue(_node_exists($xml, '//input[@name="hubblesite[' . $option . ']" and @checked="checked"]'));
    }
  }
  
  function providerTestUpdateOptions() {
    $d = new DailyImageWidget(true);
    $default_display_options = $d->default_display_options;

    return array(
      array(
        array(),
        $default_display_options
      ),
      array(
        array(
          'save-widgets' => "yes"
        ),
        $default_display_options
      ),
      array(
        array(
          'hubblesite' => array(
            '_wpnonce' => "~*NONCE*~"
          )
        ),
        $default_display_options
      ),
      array(
        array(
          'hubblesite' => array(
            '_wpnonce' => "~*NONCE*~",
            'credits' => "yes"
          )
        ),
        array("credits")
      ),
    );
  }
  
  /**
   * @dataProvider providerTestUpdateOptions
   */
  function testUpdateOptions($post, $result) {   
    $_POST = $post;
    
    if (isset($_POST['hubblesite']['_wpnonce'])) {
      $_POST['hubblesite']['_wpnonce'] = _get_nonce('hubble');
    }
    
    $this->diw->handle_post();
    $this->diw->get_display_options();
    $this->assertEquals($result, $this->diw->display_options);
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
    
    foreach (array(
      '//input[@type="hidden" and @name="hubblesite[_wpnonce]"]' => true
    ) as $xpath => $value) {
      $this->assertTrue(_xpath_test($xml, $xpath, $value), $xpath);
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
  
  function testLoadData() {
    $diw = $this->getMock('DailyImageWidget', array('_get_from_data_source', '_get_cached_data', 'parse_xml'));
    $diw->expects($this->once())->method('_get_cached_data')->will($this->returnValue(false));
    $diw->expects($this->once())->method('_get_from_data_source')->will($this->returnValue(false));
    _reset_wp();
    
    $this->assertFalse($diw->_load_data());
    $this->assertFalse(is_array(get_option('hubblesite-daily-image-cache')));

    $diw = $this->getMock('DailyImageWidget', array('_get_from_data_source', '_get_cached_data', 'parse_xml'));
    $diw->expects($this->once())->method('_get_cached_data')->will($this->returnValue(true));
    _reset_wp();
    
    $this->assertTrue($diw->_load_data());
    $this->assertFalse(is_array(get_option('hubblesite-daily-image-cache')));

    $diw = $this->getMock('DailyImageWidget', array('_get_from_data_source', '_get_cached_data', 'parse_xml'));
    $diw->expects($this->once())->method('_get_cached_data')->will($this->returnValue(false));
    $diw->expects($this->once())->method('_get_from_data_source')->will($this->returnValue(true));
    $diw->expects($this->once())->method('parse_xml')->will($this->returnValue(false));
    _reset_wp();
    
    $this->assertFalse($diw->_load_data());
    $this->assertFalse(is_array(get_option('hubblesite-daily-image-cache')));

    $diw = $this->getMock('DailyImageWidget', array('_get_from_data_source', '_get_cached_data', 'parse_xml'));
    $diw->expects($this->once())->method('_get_cached_data')->will($this->returnValue(false));
    $diw->expects($this->once())->method('_get_from_data_source')->will($this->returnValue(true));
    $diw->expects($this->once())->method('parse_xml')->will($this->returnValue(true));    
    _reset_wp();
    
    $this->assertTrue($diw->_load_data());
    $this->assertTrue(is_array(get_option('hubblesite-daily-image-cache')));
  }
}

?>