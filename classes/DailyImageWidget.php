<?php

class DailyImageWidget {
  function DailyImageWidget($skip_load_data = false) {
    $this->default_display_options = array(
      'title',
      'image',
      'styles'
    );
    
    $this->_cache_time = 86400;
    
    $this->data_source = "http://hubblesite.org/gallery/album/daily_image.php";
    
    $this->has_simplexml = class_exists('SimpleXMLElement');
    
    $this->_valid_column_names = array('title', 'caption', 'date', 'image_url', 'gallery_url', 'credits');
    $this->_valid_options = array(
      "image"   => __("Daily Image", "hubblesite-daily-image-widget"),
      "title"   => __("Image Title", "hubblesite-daily-image-widget"),
      "caption" => __("Image Caption", "hubblesite-daily-image-widget"),
      "credits" => __("Credits", "hubblesite-daily-image-widget"),
      "styles"  => __("HubbleSite Styles", "hubblesite-daily-image-widget"),
    );
    
    add_action('init', array($this, "_init"));
    
    if (!$skip_load_data) {
      if (!$this->_load_data()) {
        add_action("admin_notices", array($this, "_connection_warning"));
      }
    } else {
      $this->data = false;
    }
  }
  
  function _init() {
    register_sidebar_widget(__("HubbleSite Daily Image", "hubblesite-daily-image-widget"), array($this, "render"));  
    register_widget_control(__("HubbleSite Daily Image", "hubblesite-daily-image-widget"), array($this, "render_ui"));  
    
    $this->handle_post();
    $this->get_display_options();
  }
  
  function _connection_warning() {
    echo "<div class=\"updated fade\">";
      _e("<strong>HubbleSite Daily Image Widget</strong> was unable to retrieve new data from HubbleSite.", "hubblesite-daily-image-widget");
      _e("The widget will appear as empty in your site until data can be downloaded again.", "hubblesite-daily-image-widget");
    echo "</div>";
  }
  
  function _get_from_data_source() {
    return @file_get_contents($this->data_source);
  }

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
   */
  function render() {
    if (!empty($this->data) && is_array($this->data)) {
      $options = $this->get_display_options();
      
      echo '<div id="hubblesite-daily-image">';
        if (in_array("image", $options)) {
          echo '<a href="' . $this->data['gallery_url'] . '" title="' . $this->data['title'] . '">';
            echo '<img src="' . $this->data['image_url'] . '" alt="' . $this->data['title'] . '" />';
          echo '</a>';
        }
        
        if (in_array("title", $options)) {
          echo '<a id="hubblesite-daily-image-title" href="' . $this->data['gallery_url'] . '">';
            echo $this->data['title'];
          echo '</a>';
        }

        if (in_array("caption", $options)) {
          echo '<div id="hubblesite-daily-image-caption">';
            echo $this->data['caption'];
          echo '</div>';
        }

        if (in_array("credits", $options)) {
          echo '<div id="hubblesite-daily-image-credits">';
            echo $this->data['credits'];
          echo '</div>';
        }
      echo '</div>';
      
      if (in_array("styles", $options)) {
        echo '<style type="text/css">';
          echo "div#hubblesite-daily-image { text-align: center }";
        echo '</style>';
      }
    }
  }
  
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
   */
  function parse_xml($xml_text) {
    if ($this->has_simplexml) {
      try {
        $xml = new SimpleXMLElement($xml_text);

        if ($xml !== false) {
          $data = array();
          $is_valid = true;
          foreach ($this->_valid_column_names as $node) {
            if ($xml->{$node}) {
              $data[$node] = (string)$xml->{$node}; 
            } else {
              $is_valid = false; break; 
            }
          }
          
          if ($is_valid) {
            $this->data = $data; 
          } else {
            $this->data = false; 
          }
        }
      } catch (Exception $e) {
        $this->data = false; 
      }
    } else {
      $parser = xml_parser_create();
      $this->_character_data = "";
      $this->_xml_data = array();
      xml_set_element_handler(
        $parser,
        array($this, "_start_element_handler"),
        array($this, "_end_element_handler")
      );
      xml_set_character_data_handler($parser, array($this, "_character_data_handler"));
      xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
      $this->data = false;
      if (xml_parse($parser, $xml_text)) {
        if (count($this->_xml_data) == count($this->_valid_column_names)) {
          $this->data = $this->_xml_data; 
        }
      }
    }
    return $this->data;
  }
  
  function _start_element_handler($parser, $name, $attributes) {
    $this->_character_data = ""; 
  }
  
  function _end_element_handler($parser, $name) {
    $name = strtolower($name);
    if (in_array($name, $this->_valid_column_names)) {
      $this->_xml_data[$name] = $this->_character_data;
    }
              
    $this->_character_data = "";
  }
  
  function _character_data_handler($parser, $data) {
    $this->_character_data .= $data;
  }
  
  function _get_cached_data() {
    $result = get_option('hubblesite-daily-image-cache');
    
    if (is_string($result)) {
      if (($data = @unserialize($result)) !== false) {
        list($timestamp, $cached_data) = $data;
        
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
    }
    return false;
  }
}

?>