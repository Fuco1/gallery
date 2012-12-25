<?php

class Template {

    var $vars; /// Holds all the template variables

    /**
     * Constructor
     *
     * @param $file string the file name you want to load
     */

    function Template($file = null) {
        $this->file = $file;
        $this->vars = array();
    }

    function __toString() {
        return "Template";
    }

    /**
     * Set a template variable.
     */
    function set($name, $value) {
        if (is_array($value)) {
            //echo "setting array variable $name to $value <br>\n";
            $this->vars[$name] = $value;
            return;
        }
        if (is_object($value)) {
            //echo "setting object variable $name to $value <br>\n";
            $class = get_class($value);
            if ($class == 'Template') {
                $this->vars[$name] = $value->fetch();
            } else if ($class == 'CachedTemplate') {
                $this->vars[$name] = $value->fetch_cache($value->file);
            } else {
                $this->vars[$name] = $value;
            }
        }
        else {
            //echo "setting normal variable $name to $value <br>\n";
            $this->vars[$name] = $value;
        }
        //        $this->vars[$name] = (get_class($value) == 'Template' ||
        //            get_class($value) == 'CachedTemplate')
        //            ? $value->fetch() : $value;
    }

    /**
     * Open, parse, and return the template file.
     *
     * @param $file string the template file name
     */
    function fetch($file = null) {
        if (!$file)
            $file = $this->file;

        extract($this->vars);          // Extract the vars to local namespace
        ob_start();                    // Start output buffering
        require($file);                // Include the file
        $contents = ob_get_contents(); // Get the contents of the buffer
        ob_end_clean();                // End buffering and discard
        return $contents;              // Return the contents
    }

}

?>
