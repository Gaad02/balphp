<?php
require_once 'Zend/View/Helper/Abstract.php';
class Bal_View_Helper_Widget extends Zend_View_Helper_Abstract {

	/**
	 * The App Plugin
	 * @var Bal_Controller_Plugin_App
	 */
	protected $_App = null;
	
	/**
	 * The View in use
	 * @var Zend_View_Interface
	 */
	public $view;
	
	/**
	 * Widgets list
	 * @var array
	 */
	protected $widgets = array();
	
	/**
	 * Widgets view path
	 * @var string
	 */
	public $widgets_view_path = 'widgets';
	
	/**
	 * Apply View
	 * @param Zend_View_Interface $view
	 */
	public function setView (Zend_View_Interface $view) {
		# Apply
		$this->_App = Zend_Controller_Front::getInstance()->getPlugin('Bal_Controller_Plugin_App');
		//$this->widgets_view_path = $this->_App->getConfig('widgets.viewpath');
		
		# Set
		$this->view = $view;
		
		# Done
		return true;
	}
	
	/**
	 * Self Reference
	 * @return Zend_View_Helper_Interface
	 */
	public function widget ( ) {
		return $this;
	}
	
	public function addWidgets ( array $widgets ) {
		foreach ( $widgets as $code => $params ) {
			$this->addWidget($code, $params);
		}
		return $this;
	}
	
	public function addWidget ( $code, array $params = array() ) {
		$this->widgets[$code] = $params;
		return $this;
	}
	
	public function getWidget ( $code ) {
		return $this->widgets[$code];
	}
	
	public function renderWidget ( $code, array $params = array() ) {
		# Prepare
		$widget = $this->getWidget($code);
		if ( !empty($widget['helper']) ) {
			$helper = $widget['helper'];
		} else {
			throw new Zend_Exception('Widget requires helper.');
		}
		if ( !empty($widget['name']) ) {
			$name = $widget['name'];
		} else {
			$name = $code;
		}
		if ( !empty($widget['action']) ) {
			$action = $widget['action'];
		} else {
			$action = 'render'.ucfirst($name).'Widget';
		}
		
		# Clean
		//$params = array_unique($params);
		
		# Handle
		$render = $this->view->getHelper($helper)->$action($params);
		
		# Done
		return $render;
	}
	
	public function renderWidgetView ( $widget, array $model = array() ) {
		# Handle
		$widget_view_path = $this->widgets_view_path . DIRECTORY_SEPARATOR . $widget.'.phtml';
		
		# Render
		return $this->view->partial($widget_view_path, $model);
	}
	
	protected function renderAllReplace ( $code, $innerContent = '', $attrs = array(), array $params = array() ) {
		# Prepare
		
		# Handle
		$params['innerContent'] = $innerContent;
		if ( empty($params['content']) ) $params['content'] = $innerContent;
		
		# Attributes
		if ( !is_array($attrs) ) {
			// Somehow turns these attributes into an array
			$attrs = stripslashes($attrs);
			$attrs = array_from_attributes($attrs);
		}
		
		# Apply
		$params = array_merge($params,$attrs);
		
		# Render
		$render =
			//'<!--['.$code.']-->'.
				$this->renderWidget($code, $params);
			//'<!--[/'.$code.']-->';
		
		# Done
		return $render;
	}
	
	public function renderAll ( $content, array $params = array() ) {
		# Prepare
		
		# Search
		$matches = array();
		$search =
		'/' .
			'\['.
				'(?P<code>'.
					'('.implode(array_keys($this->widgets),'|').')'.
				')'.
				'\s*(?P<attrs>[^\]]*)'.
			'\]'.
			'('.
				'(?P<content>[^\]]*)'.
				'\[\/' . '\1' . '\]' .
			')?'.
		'/e';
		$replace = '\$this->renderAllReplace( preg_unescape(\'${1}\'), preg_unescape(\'${5}\'), preg_unescape(\'${3}\'), $params )';
		
		# Replace
		$render = preg_replace($search, $replace, $content);
		
		# Done
		return $render;
	}
	
}