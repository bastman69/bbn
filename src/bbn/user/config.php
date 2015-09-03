<?php
/**
 * @package bbn\user
 */
namespace bbn\user;

if ( !defined('BBN_SESS_NAME') ){
	define('BBN_SESS_NAME', 'define_me');
}

/**
 * A user authentication Class
 *
 *
 * @author Thomas Nabet <thomas.nabet@gmail.com>
 * @copyright BBN Solutions
 * @since Jan 2, 2011, 18:17:55 +0000
 * @category  Authentication
 * @license   http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version 0.3.0
 */
class config
{

	private 
		$_defaults=array(
			'fields' => array(
				'id' => 'id',
				'user' => 'email',
				'pass' => 'pass',
				'sess_id' => 'sess_id',
				'info_auth' => 'info_auth',
				'reset_link' => 'reset_link',
				'ip' => 'ip',
				'last_connection' => 'last_connection'
			),
			'encryption' => 'sha1',
			'table' => 'users',
			'condition' => "acces > 0",
			'additional_fields' => array(),
			'user_group' => false,
			'group' => false,
			'num_attempts' => 3,
			'sess_name' => BBN_SESS_NAME,
			'sess_user' => 'user'
		),
		$cfg,
		$db;

	/**
	 * @return void 
	 */
	public function __construct($cfg, \bbn\db\connection $db)
	{
		$this->db = $db;
		$this->cfg = $this->_defaults;
		foreach ( $cfg as $i => $v ){
			
			if ( isset($this->_defaults[$i]) ){
				
				if ( $i === 'fields' && is_array($v) ){
					foreach ( $v as $k => $w ){
						if ( isset($this->_defaults[$i][$k]) ){
							$this->cfg[$i][$k] = $w;
						}
					}
				}
				else{
					$this->cfg[$i] = $v;
				}
			}
		}
	}

	/**
	 * @return \bbn\user\connection 
	 */
	public function connect($credentials=array())
	{
		$a = func_get_args();
		if ( count($a) === 2 ){
			$credentials = array('user'=>$a[0],'pass'=>$a[1]);
		}
		return new connection($this->cfg, $this->db, $credentials);
	}

}
?>