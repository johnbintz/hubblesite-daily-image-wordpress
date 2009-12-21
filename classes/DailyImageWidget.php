<?php

/**
 * Show a HubbleSite daily image as a widget.
 */
class DailyImageWidget {
	var $display_options = array();

  /**
   * Initialize the widget.
   * For unit testing purposes, you can disable remote data loading by passing true to this function.
   * @param boolean $skip_load_data True if data from the remote server should not be loaded.
   */
  function DailyImageWidget($skip_load_data = false) {
    $this->default_display_options = array(
      'title',
      'image'
    );

    $this->_cache_time = 86400;
    $this->_source_stamp = "&amp;f=wpw";

    $this->data_source = "http://hubblesite.org/gallery/album/daily_image.php";

    $this->has_simplexml = class_exists('SimpleXMLElement');

    $this->_valid_column_names = array('title', 'date', 'image_url', 'gallery_url', 'credits');
    $this->_valid_options = array(
      "image"   => __("Daily Image", "hubblesite-daily-image-widget"),
      "title"   => __("Image Title", "hubblesite-daily-image-widget"),
      "credits" => __("Credits", "hubblesite-daily-image-widget")
    );

    add_action('init', array($this, "_init"));
  }

  /**
   * WordPress init hook.
   */
  // @codeCoverageIgnoreStart
  function _init() {
    wp_register_sidebar_widget(
      'hubblesite-daily-image',
      __("HubbleSite Daily Image", "hubblesite-daily-image-widget"),
      array(&$this, "render"),
      array(
        'description' => __('Embed a daily HubbleSite Gallery image on your WordPress blog.', 'hubblesite-daily-image-widget')
      )
    );
    register_widget_control(__("HubbleSite Daily Image", "hubblesite-daily-image-widget"), array(&$this, "render_ui"));

    if (!$skip_load_data) {
      if (!$this->_load_data()) {
        add_action("admin_notices", array(&$this, "_connection_warning"));
      }
    } else {
      $this->data = false;
    }

    $this->handle_post();
    $this->get_display_options();
  }
  // @codeCoverageIgnoreEnd

  /**
   * Display a warning if the connection failed.
   */
  // @codeCoverageIgnoreStart
  function _connection_warning() {
    echo "<div class=\"updated fade\">";
      _e("<strong>HubbleSite Daily Image Widget</strong> was unable to retrieve new data from HubbleSite.", "hubblesite-daily-image-widget");
      _e("The widget will appear as empty in your site until data can be downloaded again.", "hubblesite-daily-image-widget");
    echo "</div>";
  }
  // @codeCoverageIgnoreEnd

  /**
   * Wrapper around a remote data call for unit testing purposes.
   * @return string The data from the remote source.
   */
  // @codeCoverageIgnoreStart
  function _get_from_data_source() {
    $response = wp_remote_request($this->data_source, array('method' => 'GET'));
    if (!is_wp_error($response)) {
      if (isset($response['body'])) {
        return $response['body'];
      }
    }
    return false;
  }
  // @codeCoverageIgnoreEnd

  /**
   * Load the remote data into the object.
   * This will try to pull from cache and, if necessary, retrieve and parse the XML from the
   * remote server. If any of this fails, returns false.
   * @return boolean True if data could be loaded, false otherwise.
   */
  function _load_data() {
    if (($result = $this->_get_cached_data()) === false) {
      if (($xml_text = $this->_get_from_data_source()) !== false) {
        if (($result = $this->parse_xml($xml_text)) !== false) {
          update_option('hubblesite-daily-image-cache', array(time(), $result));
          return true;
        }
      }
      return false;
    } else {
      return true;
    }
  }

  /**
   * Handle updating the widget options.
   */
  function handle_post() {
    if (isset($_POST['hubblesite']['_wpnonce'])) {
      if (wp_verify_nonce($_POST['hubblesite']['_wpnonce'], 'hubble')) {
        $options = array();
        foreach ($this->_valid_options as $option => $label) {
          if (isset($_POST['hubblesite'][$option])) {
            $options[] = $option;
          }
        }
        $this->display_options = $options;
        update_option('hubblesite-daily-image-options', implode(",", $this->display_options));
      }
    }
  }

  /**
   * Get the list of display options from the WordPress options database.
   */
  function get_display_options() {
    $display_options = get_option('hubblesite-daily-image-options');
    $this->display_options = array();
    if (!empty($display_options)) {
      $this->display_options = array_intersect(explode(",", $display_options), array_keys($this->_valid_options));
    }

    if (empty($this->display_options)) {
      $this->display_options = $this->default_display_options;
    }

    update_option('hubblesite-daily-image-options', implode(",", $this->display_options));

    return $this->display_options;
  }

  /**
   * Render the widget.
   * @param array $args The theme's widget layout arguments.
   */
  function render($args = array()) {
    if (!empty($this->data) && is_array($this->data)) {
      extract($args);
      $options = $this->get_display_options();

      echo $before_widget;
        echo $before_title;
          echo "HubbleSite Daily Image";
        echo $after_title;
        if (in_array("image", $options)) {
          echo '<a href="' . $this->data['gallery_url'] . $this->_source_stamp . '" title="' . $this->data['title'] . '">';
            echo '<img src="' . $this->data['image_url'] . '" alt="' . $this->data['title'] . '" width="100%" />';
          echo '</a>';
        }

        if (in_array("title", $options)) {
          echo '<a id="hubblesite-daily-image-title" href="' . $this->data['gallery_url'] . $this->_source_stamp . '">';
            echo $this->_fix_widows($this->data['title']);
          echo '</a>';
        }

        if (in_array("credits", $options)) {
          echo '<div id="hubblesite-daily-image-credits">';
            echo $this->_fix_widows($this->data['credits']);
          echo '</div>';
        }
      echo $after_widget;
    }
  }

  /**
   * Render the widget admin UI.
   */
  function render_ui() {
    echo "<input type=\"hidden\" name=\"hubblesite[_wpnonce]\" value=\"" . wp_create_nonce('hubble') . "\" />";
    echo "<p>";
      _e("Show on Widget <em>(must select at least one)</em>:", "hubblesite-daily-image-widget");
    echo "</p>";

    foreach ($this->_valid_options as $option => $label) {
      echo "<label>";
        echo "<input type=\"checkbox\" name=\"hubblesite[${option}]\" " . (in_array($option, $this->display_options) ? "checked=\"checked\"" : "") . "/> ";
        echo $label;
      echo "</label>";
      echo "<br />";
    }
  }

  /**
   * Parse a string of XML from the HubbleSite Daily Gallery Image feed.
   * This will try to use SimpleXML if vailable. If not, will fall back on Expat.
   * @param string $xml_text The text to parse.
   * @return array|boolean The retrieved data, or false on failure.
   */
  function parse_xml($xml_text) {
    $parser = xml_parser_create();
    $this->_character_data = "";
    $this->_xml_data = array();
    xml_set_element_handler(
      $parser,
      array(&$this, "_start_element_handler"),
      array(&$this, "_end_element_handler")
    );
    xml_set_character_data_handler($parser, array(&$this, "_character_data_handler"));
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    $this->data = false;
    if (xml_parse($parser, $xml_text)) {
      if (count($this->_xml_data) == count($this->_valid_column_names)) {
        $this->data = $this->_xml_data;
      }
    }
    return $this->data;
  }

  /**
   * Expat start element handler.
   */
  function _start_element_handler($parser, $name, $attributes) {
    $this->_character_data = "";
  }

  /**
   * Expat end element handler.
   */
  function _end_element_handler($parser, $name) {
    $name = strtolower($name);
    if (in_array($name, $this->_valid_column_names)) {
      $this->_xml_data[$name] = $this->_character_data;
    }

    $this->_character_data = "";
  }

  /**
   * Expat character data handler.
   */
  function _character_data_handler($parser, $data) {
    $this->_character_data .= $data;
  }

  /**
   * Retrieve the cached data from WP Options.
   * @return array|boolean The cached data or false upon failure.
   */
  function _get_cached_data() {
    if (($result = get_option('hubblesite-daily-image-cache')) !== false) {
      list($timestamp, $cached_data) = $result;

      if (($timestamp + $this->_cache_time) > time()) {
        $is_valid = true;
        foreach ($this->_valid_column_names as $field) {
          if (!isset($cached_data[$field])) { $is_valid = false; break; }
        }

        if ($is_valid) {
          $this->data = $cached_data;
          return $cached_data;
        }
      }
    }
    return false;
  }

  /**
   * Try to ensure that no words in a paragraph or link are widowed.
   * @param string $text The text to process.
   * @return string The processed text.
   */
  function _fix_widows($text) {
    return preg_replace("#([^\ ]+)\ ([^\ \>]+)($|</p>|</a>)#", '\1&nbsp;\2\3', $text);
  }
}

// @codeCoverageIgnoreStart
function the_hubblesite_daily_image_widget() {
  $diw = new DailyImageWidget();
  $diw->render();
}
// @codeCoverageIgnoreEnd
