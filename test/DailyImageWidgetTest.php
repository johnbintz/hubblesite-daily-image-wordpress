<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) . '/../classes/DailyImageWidget.php');
require_once('MockPress/mockpress.php');

class DailyImageWidgetTest extends PHPUnit_Framework_TestCase {
  function setUp() {
    _reset_wp();
    wp_create_nonce("hubble");
    $_POST = array();

    $this->diw = new DailyImageWidget(true);

    $this->sample_data = array(
      'title' => 'title',
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
          '//div[@id="hubblesite-daily-image"]' => false,
          '//a[@href="gallery_url&f=wpw" and @title="title"]' => true,
          '//a/img[@src="image_url" and @alt="title"]' => true,
        )
      ),
      array(
        "title",
        array(
          '//a[@href="gallery_url&f=wpw" and @id="hubblesite-daily-image-title"]' => "title"
        )
      ),
      array(
        "credits",
        array(
          '//div[@id="hubblesite-daily-image-credits"]' => 'credits'
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
    $this->diw->render(array(
      'before_widget' => "",
      'after_widget' => "",
      'before_title' => "",
      'after_title' => ""
    ));
    $result = ob_get_clean();

    $this->assertTrue(!empty($result));

    $this->assertTrue(($xml = _to_xml($result, true)) !== false);
    foreach ($xpath_tests as $xpath => $result) {
      $this->assertTrue(_xpath_test($xml, $xpath, $result), $xpath);
    }
  }

  function providerTestGetDisplayOptions() {
    return array(
      array("", array("title", "image")),
      array("meow", array("title", "image")),
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

  function providerTestGetCachedData() {
    return array(
      array(time() + 86500, true),
      array(time() + 86500, array('test' => 'test'), false),
      array(time() - 86500, false),
      array(null, false)
    );
  }

  /**
   * @dataProvider providerTestGetCachedData
   */
  function testGetCachedData($test_time, $has_sample_data, $expected_return = null) {
    if (!is_null($test_time)) {
    	if ($has_sample_data === true) {
    		$has_sample_data = $this->sample_data;
    	}
      update_option('hubblesite-daily-image-cache', array($test_time, $has_sample_data));
    } else {
      update_option('hubblesite-daily-image-cache', null);
    }

    if (is_null($expected_return)) {
    	$expected_return = $has_sample_data ? $has_sample_data : false;
    }

    $this->assertEquals($expected_return, $this->diw->_get_cached_data());
  }

  function providerTestLoadData() {
    return array(
      array(true, null, null, true),
      array(false, false, null, false),
      array(false, true, false, false),
      array(false, true, true, true)
    );
  }

  /**
   * @dataProvider providerTestLoadData
   */
  function testLoadData($get_cached_data, $get_from_data_source, $parse_xml_result, $expected_return) {
    $diw = $this->getMock('DailyImageWidget', array('_get_from_data_source', '_get_cached_data', 'parse_xml'));

    $diw->expects($this->once())->method('_get_cached_data')->will($this->returnValue($get_cached_data));
    if ($get_cached_data == false) {
      $diw->expects($this->once())->method('_get_from_data_source')->will($this->returnValue($get_from_data_source));
      if ($get_from_data_source) {
        $diw->expects($this->once())->method('parse_xml')->will($this->returnValue($parse_xml_result));
      }
    }

    $this->assertEquals($expected_return, $diw->_load_data());

    $this->assertEquals($parse_xml_result, is_array(get_option('hubblesite-daily-image-cache')));
  }


  function providerTestWidowProtection() {
    return array(
      array("this is fixed", "this is&nbsp;fixed"),
      array("<p>this is fixed</p>" ,"<p>this is&nbsp;fixed</p>"),
      array("<a>this is fixed</a>", "<a>this is&nbsp;fixed</a>"),
      array("<a href='meow'>word</a>", "<a href='meow'>word</a>"),
      array("<p>this is fixed</p><p>Also fixed</p>", '<p>this is&nbsp;fixed</p><p>Also&nbsp;fixed</p>')
    );
  }

  /**
   * @dataProvider providerTestWidowProtection
   */
  function testWidowProtection($source, $result) {
    $this->assertEquals($result, $this->diw->_fix_widows($source));
  }
}

?>
