<?php
/* 
 * Copyright (C) 2014 BBN
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace bbn\appui;

class cron extends \bbn\obj{
  
	private 
          /* @var \bbn\db\connection The DB connection */
          $db = false,
          /* @var string The tables' prefix (the tables will be called ?cron and ?journal) */
          $prefix = 'bbn_',
          $primary = 'id',
          $date = false,
          $last_rows = false,
          $ok = false,
          $enabled = true;
	
  public function __construct(\bbn\mvc $mvc, $cfg = []) {
    if ( is_array($cfg) ){
      $this->mvc =& $mvc;
      if ( empty($mvc->db) ){
        die("Database mandatory in \\bbn\\appui\\cron !");
      }
      $this->db =& $mvc->db;
      $vars = get_class_vars('\\bbn\\appui\\cron');
      foreach ( $cfg as $cf_name => $cf_value ){
        if ( array_key_exists($cf_name, $vars) ){
          $this->{$cf_name} = $cf_value;
        }
      }
      $this->table = $this->prefix.'cron';
      $this->jtable = $this->prefix.'journal';
    }
  }
  
  public function check(){
    if ( $this->table ){
      return 1;
    }
    return false;
  }
  
  public function start($id_cron){
    if ( $this->check() && $this->db->insert($this->jtable, [
      'id_cron' => $id_cron,
      'start' => date('Y-m-d H:i:s')
    ]) ){
      return $this->db->last_id ();
    }
    return false;
  }
  
  public function get_article($id){
    if ( $this->check() ){
      $data = $this->db->rselect($this->jtable, [], ['id' => $id]);
      $data['cfg'] = json_decode($data['cfg'], 1);
      return $data;
    }
  }
  
  public function get_cron($id){
    if ( $this->check() ){
      $data = $this->db->rselect($this->table, [], ['id' => $id]);
      $data['cfg'] = json_decode($data['cfg'], 1);
      return $data;
    }
  }
  
  public function finish($id){
    if ( ($article = $this->get_article($id)) && ($cron = $this->get_cron($article['id_cron'])) ){
      $date = time();
      $this->db->update($this->jtable, [
          'finish' => date('Y-m-d H:i:s', $date)
        ], [
          'id' => $id
        ]);
      $this->db->update($this->table, [
        'next' => date('Y-m-d H:i:s', $date + $cron['cfg']['latency'])
      ], [
        'id' => $cron['id']
      ]);
      return 1;
    }
    return false;
  }
  
  public function get_next($id_cron = null){
    if ( $this->check() && ($data = $this->db->get_row("
        SELECT *
        FROM {$this->table}
        WHERE active = 1 
        AND next < NOW()".
        ( is_int($id_cron) ? " AND id_cron = $id_cron" : "" )."
        ORDER BY next ASC
        LIMIT 1")) ){
      // Dans cfg: timeout, et soit: latency, minute, hour, day of month, day of week, date
      $data['cfg'] = json_decode($data['cfg'], 1);
      return $data;
    }
  }
  
  public function is_running($id_cron){
    if ( $this->check() && is_int($id_cron) ){
      return $this->db->get_value("
        SELECT COUNT(*)
        FROM {$this->jtable}
        WHERE id_cron = ?
        AND finish IS NULL",
        $id_cron) ? true : false;
    }
  }
  
  private function get_runner($id_cron){
    if ( $this->check() && is_int($id_cron) ){
      $d = $this->db->get_row("
        SELECT *
        FROM {$this->jtable}
        WHERE id_cron = ?
        AND finish IS NULL",
        $id_cron);
      $d['cfg'] = json_decode($d['cfg'], 1);
    }
  }
  
  private function get_latency($id_journal){
    if ( $this->check() && is_int($id_journal) ){
      
    }
  }
  
  public function run($id_cron = null){
    if ( ($cron = $this->get_next($id_cron)) ){
      $ok  = 1;
      if ( is_running($cron['id_cron']) ){
        $runner = $this->get_runner($cron['id_cron']);
        $start = strtotime($runner['start']);
        $timeout = $runner['cfg']['timeout'];
        if ( ($start + $timeout) > time() ){
          $this->alert();
        }
        $ok = false;
      }
      if ( $ok ){
        $meth = ['post', 'get', 'files'];
        foreach ( $meth as $m ){
          if ( isset($cron['cfg'][$m]) ){
            $this->mvc->{$m} = $cron['cfg'][$m];
          }
        }
        $this->mvc->reroute($cron['cfg']['file']);
      }
    }
    die("non che niente");
  }
}