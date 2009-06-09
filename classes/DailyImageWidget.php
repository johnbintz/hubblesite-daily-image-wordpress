<?php

class DailyImageWidget {
  function DailyImageWidget() {
    $this->default_display_options = array(
      'title',
      'image',
      'styles'
    );
    
    $this->_cache_time = 86400;
    
    $this->data_source = "http://hubblesite.org/gallery/album/daily_image.php";
    $this->data = false;
    
    $this->has_simplexml = class_exists('SimpleXMLElement');
    
    $this->_valid_column_names = array('title', 'caption', 'date', 'image_url', 'gallery_url', 'credits');
    $this->_valid_options = array(
      "image"   => "Daily Image",
      "title"   => "Image Title",
      "caption" => "Image Caption",
      "credits" => "Credits",
      "styles" => "HubbleSite Styles",
    );
    
    wp_register_sidebar_widget("hubblesite-daily-image", "HubbleSite Daily Image", array($this, "render"));
    register_widget_control("hubblesite-daily-image", array($this, "render_ui"));
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
    echo "<p>Show on Widget:</p>";
    
    foreach ($this->_valid_options as $option => $label) {
      echo "<label>";
        echo "<input type=\"checkbox\" name=\"hubblesite[${option}]\" />";
        echo $label;
      echo "</label>";
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
          $is_valid = (count(array_intersect()) == count($this->_valid_column_names));
          
        }
      }
    }
    return false;
  }
}

?>