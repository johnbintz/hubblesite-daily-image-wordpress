<?php

class DailyImageWidget {
  function DailyImageWidget() {
    $this->default_display_options = array(
      'title',
      'image',
      'styles'
    ); 
  }
  
  function get_display_options() {
    $display_options = get_option('hubblesite-daily-image-options');
    $this->display_options = array();
    if (!empty($display_options)) {
      $this->display_options = array_intersect(
        explode(",", $display_options),
        array("title", "image", "styles", "caption", "credits")
      );
    }
    
    if (empty($this->display_options)) {
      $this->display_options = $this->default_display_options;
    }

    return $this->display_options;
  }
  
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
}

?>