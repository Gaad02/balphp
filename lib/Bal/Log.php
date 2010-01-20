<?php
/**
 * Balupton's Resource Library (balPHP)
 * Copyright (C) 2008-2009 Benjamin Arthur Lupton
 * http://www.balupton.com/
 *
 * This file is part of Balupton's Resource Library (balPHP).
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Balupton's Resource Library (balPHP).  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package balphp
 * @subpackage bal
 * @version 0.2.0-final, December 9, 2009
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008-2009, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */
require_once 'Zend/Log.php';
class Bal_Log extends Zend_Log {
	
    /*const INSERT	= 8;
    const SAVE		= 9;
    const DELETE	= 10;
    const SUCCESS	= 11;*/

	protected $Writer = null;
	
	public function __construct ( ) {
		# Writer
		$this->Writer = new Zend_Log_Writer_Mock();
		$this->addWriter($this->Writer);
		$Formatter_Rich = new Zend_Log_Formatter_Simple('hello %message%' . PHP_EOL);
		$this->Writer->setFormatter($Formatter_Rich);
		
		# Parent Construct
		return parent::__construct(); // will handle priorities for us
	}
	
	
    /**
     * Get the Log Instance
     * @return Zend_Log
     */
	public static function getInstance ( ) {
		return Zend_Registry::get('Log');
	}
	
    /**
     * Get the log entries
     * @return array
     */
	public function getEvents ( ) {
		return $this->Writer->events;
	}
	
    /**
     * Get a rendered list of log entries
     * @return array
     */
	public function render ( ) {
		return '<pre>'.var_export($this->getEvents(),true).'</pre>';
	}
	
    /**
     * Log a message at a priority with extras
     * @param  string   $message   Message to log
     * @param  integer  $priority  Priority of message
     * @param  array    $extras    Extras to add
     * @return void
     * @throws Zend_Log_Exception
     */
	public function log ( $message, $priority, array $extras = null ) {
		# Prepare Priority
		if ( $priority === null ) $priority = Bal_Log::INFO;
		
		# Perform Log
		if ( !empty($extras) ) foreach ( $extras as $key => $value ) {
			$this->setEventItem($key,$value);
		}
		parent::log($message, $priority);
		if ( !empty($extras) ) foreach ( $extras as $key => $value ) {
			$this->setEventItem($key,null);
		}
		
		# Chain
		return $this;
	}
	
}
