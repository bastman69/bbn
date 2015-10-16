<?php
/* @var $this \bbn\mvc */

namespace bbn\ide;


class directories {

  public function __construct(\bbn\db\connection $db){
    $this->db = $db;
  }

  public function add($data){
    $data['position'] = $this->db->get_one('
      SELECT MAX(position) AS pos
      FROM bbn_ide_directories') + 1;
    if ( $this->db->insert('bbn_ide_directories', [
      'name' => $data['name'],
      'root_path' => \bbn\str\text::parse_path($data['root_path']),
      'fcolor' => $data['fcolor'],
      'bcolor' => $data['bcolor'],
      'outputs' => strlen($data['outputs']) ? $data['outputs'] : NULL,
      'files' => $data['files'],
      'position' => $data['position']
    ]) ){
      $data['id'] = $this->db->last_id();
      return $data;
    }
    return $this->error('Error: Add.');
  }

  public function edit($data){
    if ( $this->db->update('bbn_ide_directories', [
      'name' => $data['name'],
      'root_path' => \bbn\str\text::parse_path($data['root_path']),
      'fcolor' => $data['fcolor'],
      'bcolor' => $data['bcolor'],
      'outputs' => strlen($data['outputs']) ? $data['outputs'] : NULL,
      'files' => $data['files'],
      'position' => $data['position']
    ], ['id' => $data['id']]) ){
      return 1;
    }
    return $this->error('Error: Edit.');
  }

  public function delete($data){
    if ( $this->db->delete('bbn_ide_directories', ['id' => $data['id']]) ){
      return 1;
    }
    return $this->error('Error: Delete.');
  }

  public function get($name=''){
    return empty($name) ?
      $this->db->rselect_all('bbn_ide_directories', [], [], ['position' => 'ASC']) :
      $this->db->rselect_all('bbn_ide_directories', [], ['name' => $name]);
  }

  public function dirs($name=''){
    $dirs = [];
    foreach ( $this->get($name) as $d ){
      $files = json_decode($d['files']);
      $p = \bbn\str\text::parse_path(substr($d['root_path'], 0, strpos($d['root_path'], '/')));
      $d['root_path'] = \bbn\str\text::parse_path(constant($p).str_replace($p, '', $d['root_path']));
      foreach ( $files as $i => $f ){
        $f = (array)$f;
        $f['path'] = \bbn\str\text::parse_path($f['path']);
        $f['fpath'] = \bbn\str\text::parse_path($d['root_path'].$f['path']);
        if ( !empty($f['default']) ){
          $d['def'] = $f['url'];
        }

        $files[!empty($f['title']) ? $f['title'] : $i] = $f;
        if ( !empty($f['title']) ){
          unset($files[$i]);
        }
      }
      $d['files'] = $files;
      $dirs[$d['name']] = $d;
    }
    return $name && isset($dirs[$name]) ? $dirs[$name] : $dirs;
  }

  public function modes(){
    return [
      'html' => [
        'name' => 'HTML',
        'mode' => 'htmlmixed',
        'code' => is_file(BBN_DATA_PATH.'ide/defaults/default.html') ? file_get_contents(BBN_DATA_PATH.'ide/defaults/default.html') : ''
      ],
      'xml' => [
        'name' => 'XML',
        'mode' => 'text/xml',
        'code' => is_file(BBN_DATA_PATH.'ide/defaults/default.xml') ? file_get_contents(BBN_DATA_PATH.'ide/defaults/default.xml') : ''
      ],
      'js' => [
        'name' => 'JavaScript',
        'mode' => 'javascript',
        'code' => is_file(BBN_DATA_PATH.'ide/defaults/default.js') ? file_get_contents(BBN_DATA_PATH.'ide/defaults/default.js') : ''
      ],
      'svg' => [
        'name' => 'SVG',
        'mode' => 'text/xml',
        'code' => is_file(BBN_DATA_PATH.'ide/defaults/default.svg') ? file_get_contents(BBN_DATA_PATH.'ide/defaults/default.svg') : ''
      ],
      'php' => [
        'name' => 'PHP',
        'mode' => 'application/x-httpd-php',
        'code' => is_file(BBN_DATA_PATH.'ide/defaults/default.php') ? file_get_contents(BBN_DATA_PATH.'ide/defaults/default.php') : ''
      ],
      'css' => [
        'name' => 'CSS',
        'mode' => 'text/css',
        'code' => is_file(BBN_DATA_PATH.'ide/defaults/default.css') ? file_get_contents(BBN_DATA_PATH.'ide/defaults/default.css') : ''
      ],
      'less' => [
        'name' => 'LESS',
        'mode' => 'text/x-less',
        'code' => is_file(BBN_DATA_PATH.'ide/defaults/default.css') ? file_get_contents(BBN_DATA_PATH.'ide/defaults/default.css') : ''
      ],
      'sql' => [
        'name' => 'SQL',
        'mode' => 'text/x-sql',
        'code' => is_file(BBN_DATA_PATH.'ide/defaults/default.sql') ? file_get_contents(BBN_DATA_PATH.'ide/defaults/default.sql') : ''
      ],
      'def' => [
        'mode' => 'application/x-httpd-php',
        'code' => is_file(BBN_DATA_PATH.'ide/defaults/default.php') ? file_get_contents(BBN_DATA_PATH.'ide/defaults/default.php') : ''
      ]
    ];
  }

  // Error
  private function error($msg = "Error: DB."){
    return ["error" => $msg];
  }
}