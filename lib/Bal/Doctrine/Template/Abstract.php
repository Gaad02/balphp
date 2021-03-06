<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Template_Item
 *
 * Easily track a balFramework changes
 *
 * @package     Doctrine
 * @subpackage  Template
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Benjamin "balupton" Lupton <contact@balupton.com>
 */
abstract class Bal_Doctrine_Template_Abstract extends Doctrine_Template {
	
	protected $_options = array();
	
	protected function optionEnabled ( $option ) {
		return
			array_key_exists($option, $this->_options)
			&&	(
					!array_key_exists('disabled', $this->_options[$option])
					||	!$this->_options[$option]['disabled']
				)
		;	
	}

	protected function hasColumnHelpers ( $details, array $columns = array() ) {
		# Prepare
		if ( empty($columns) ) {
			$columns = array_keys($columns);
		}
		
		# Handle
		foreach ( $columns as $column ) {
			$this->hasColumnHelper($details[$column]);
		}
		
		# Chain
		return $this;
	}

	protected function hasColumnHelper ( $column ) {
		# Prepare
		if ( isset($column['disabled']) && $column['disabled'] ) {
			# Skip
			return;
		}
		
		# Fetch
		$name = $column['name'];
		if ( !isset($column['name']) ) {
			die('asd');
		}
		if ( !empty($column['alias']) ) {
			$name .= ' as ' . $column['alias'];
		}
		if ( !array_key_exists('length', $column) ) {
			$column['length'] = null;
		}
		if ( !array_key_exists('options', $column) ) {
			$column['options'] = array();
		}
		
		# Handle
		$this->hasColumn($name, $column['type'], $column['length'], $column['options']);
		
		# Chain
		return $this;
	}

	protected function hasOneHelper ( $column ) {
		# Prepare
		if ( isset($column['disabled']) && $column['disabled'] )
			return;
			
		# Setup
		$this->hasOne($column['class'] . ' as ' . $column['relation'], array('local' => $column['name'], 'foreign' => 'id'));
		
		# Foreign
		if ( !empty($column['foreignAlias']) ) {
			$tableName = $this->_table->getComponentName();
			$relationName = $tableName.($column['foreignAlias'] !== true ? ' as '.$column['foreignAlias'] : '');
	        $options = array(
	            'local'    => $column['name'],
	            'foreign'  => 'id',
	            'refClass' => $tableName
	        );
	        Doctrine::getTable($column['class'])->bind(array($relationName, $options), Doctrine_Relation::MANY);
		}
		
		# Chain
		return $this;
	}

	protected function hasManyHelper ( $column ) {
		# Prepare
		extract($column);
		
		# Primary
		$this->hasMany("$class as $relation", array('refClass' => $refClass, 'local' => $local, 'foreign' => $foreign));
		
		# Secondary
		$this->hasMany($refClass, array('local' => 'id', 'foreign' => $local));
		
		# Chain
		return $this;
	}

}

	
	