<?php

defined('_VALID_EXEC') or die('access denied');

abstract class view extends app_object {

    protected $_name = null;
    protected $_models = array();
    protected $_tpl = 'default';
    protected $_infoObject = null;
    protected $_path = null;
    protected $_app = null;

    protected function setPath($p) {
        $this->_path = $p;
    }

    public static function addIncludePath($prefix, $path = '') {
        static $paths;

        if (!isset($paths)) {
            $paths = array();
        }


        if (!isset($paths[$prefix])) {
            $paths[$prefix] = array();
        }



        if (!empty($path)) {


            if (!in_array($path, $paths[$prefix])) {
                array_unshift($paths[$prefix], Path::clean($path));
            }
        }

        return $paths[$prefix];
    }

    public static function getInstance($view, $prefix, $type = 'html') {

        static $cache = array();

        if (array_key_exists(md5($view . $prefix . $type), $cache)) {
            return $cache[md5($view . $prefix . $type)];
        }


        $type = preg_replace('/[^A-Z0-9_\.-]/i', '', $type);
        $view = preg_replace('/[^A-Z0-9_\.-]/i', '', $view);
        $viewClass = strtolower($prefix) . "View" . ucfirst($view);

        $file = "view." . $type . ".php";

        if (!class_exists($viewClass)) {

            $paths = View::addIncludePath($prefix);

            foreach ((array) $paths as $k => $path) {
                $paths[$k] = $path . DS . $view;
            }


            $path = Path::find($paths, $file);

            if ($path) {
                require_once $path;

                if (!class_exists($viewClass)) {
                    return false;
                }
            } else {
                return false;
            }
        }


        $obj = new $viewClass();
        if (method_exists($obj, 'setApp'))
            $obj->setApp(Factory::getApplication($prefix));
        if (method_exists($obj, 'init'))
            $obj->init();

        $obj->setPath(dirname($path));
        $cache[md5($view . $prefix . $type)] = $obj;
        return $cache[md5($view . $prefix . $type)];
    }

    public function setMessage($msg, $type = 'info') {



        Factory::getApplication()->setMessage($msg, $type);
    }

    public function set($property, $value = null) {
        $previous = isset($this->_infoObject->$property) ? $this->_infoObject->$property : null;
        $this->_infoObject->$property = $value;
        return $previous;
    }

    public function get($property, $default = null) {
        if (isset($this->_infoObject->$property)) {
            return $this->_infoObject->$property;
        }
        return $default;
    }

    public function __construct() {

        list($app, $type) = explode("view", strtolower(get_class($this)));

        $this->_app = Factory::getApplication($app);


        $this->_name = preg_replace("/(view)$/i", "", strtolower(get_class($this)));
    }

    public function app() {
        return $this->_app;
    }

    public function getInfo($p) {

        if ($p == 'view_layout_url') {

            $ap = str_replace('\\', '/', WP_PLUGIN_DIR);

            return WP_PLUGIN_URL . str_replace($ap, '', str_replace('\\', DS, $this->_path . '/' . $this->_name . '/templates'));
        }
        return $this->get($p);
    }

    public function setModel(model $model) {

        if ($model instanceof model) {

            return $this->_models[strtolower(get_class($model))] = $model;
        }

        return false;
    }

    protected function getModel($model) {

        if (array_key_exists(strtolower($model . "model"), $this->_models))
            return $this->_models[strtolower($model . "model")];

        return false;
    }

    private function __CLONE() {
        
    }

    public function assign() {



        // get the arguments; there may be 1 or 2.
        $arg0 = @func_get_arg(0);
        $arg1 = @func_get_arg(1);

        // assign by object
        if (is_object($arg0)) {
            // assign public properties
            foreach (get_object_vars($arg0) as $key => $val) {
                if (substr($key, 0, 1) != '_') {
                    $this->$key = $val;
                }
            }
            return true;
        }

        // assign by associative array
        if (is_array($arg0)) {
            foreach ($arg0 as $key => $val) {
                if (substr($key, 0, 1) != '_') {
                    $this->$key = $val;
                }
            }
            return true;
        }

        // assign by string name and mixed value.
        // we use array_key_exists() instead of isset() becuase isset()
        // fails if the value is set to null.
        if (is_string($arg0) && substr($arg0, 0, 1) != '_' && func_num_args() > 1) {
            $this->$arg0 = $arg1;



            return true;
        }

        // $arg0 was not object, array, or string.
        return false;
    }

    public function assignRef($key, &$val) {
        if (is_string($key) && substr($key, 0, 1) != '_') {
            $this->$key = & $val;
            return true;
        }

        return false;
    }

    public function display($tpl = 'default') {




        $mainframe = Factory::getMainframe();

        $ap = str_replace('\\', '/', WP_PLUGIN_DIR);

        $view_layout_url = WP_PLUGIN_URL . str_replace($ap, '', str_replace('\\', DS, $this->_path . '/' . $this->_name . '/templates'));



        $this->set('view_layout_url', $view_layout_url);


        if (!empty($this->_infoObject)) {


            $mainframe->addCustomHeadTag('shopinfo', "  <script type='text/javascript'>
                                                shopInfo = " . json_encode($this->_infoObject) . ";
                                            </script>   ");
        }






        $app = $this->app();

        if (Framework::is_admin() && !request::is_internal() && !Request::is_ajax()) {
            echo "<div class='wrap'>";
            $bar = toolbar::getInstance('toolbar');

            echo $bar->render();
        }


        $this->loadTemplate($tpl);
        if (Framework::is_admin() && !request::is_internal() && !Request::is_ajax()) {
            echo "</div>";
        }
    }

    public function loadTemplate($tpl = 'default') {

        static $overrides = array();

		
        $paths = array();

        $com = strtolower($this->app()->getName());

        if (array_key_exists($com, $overrides)) {
            $paths = (array) $overrides[$com];
        } else {
           
            $paths = (array) apply_filters('override_' . $com, $paths);


            $overrides[$com] = (array) $paths;
        }

        $paths = array_reverse($paths);



        $the_path = null;

        foreach ((array) $paths as $path) {
            $path = realpath($path);

            if (is_dir($path) && file_exists($path . DS . $tpl . ".tpl.php")) {
                $the_path = $path;
                break;
            }
        }

        if ($the_path) {

            require($path . DS . $tpl . ".tpl.php");
            return true;
        } else {
            $path = $this->_path . "/templates";



            if (file_exists($path . "/" . $tpl . ".tpl.php")) {

                require($path . "/" . $tpl . ".tpl.php");

                return true;
            }
        }

        throw new Exception("template:" . $tpl . __(" file cant be located!","com_shop"));
    }
}