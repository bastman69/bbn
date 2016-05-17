<?php
/**
 * Created by PhpStorm.
 * User: BBN
 * Date: 14/04/2016
 * Time: 20:38
 */

namespace bbn\appui;


class note extends \bbn\objdb
{
  private static
    $medias = [],
    $id_media;

  private static function get_id_media(){
    if ( !isset(self::$id_media) && ($opt = \bbn\appui\options::get_options()) ){
      self::$id_media = $opt->from_code('media', 'bbn_notes');
    }
    return self::$id_media;
  }

  protected static function get_medias($force = false){
    if ( empty(self::$medias) || $force ){
      if ( ($opt = \bbn\appui\options::get_options()) && self::get_id_media() ){
        $tree = $opt->tree(self::$id_media);
        self::$medias = isset($tree['items']) ? $tree['items'] : false;
      }
      else{
        self::$medias = false;
      }
    }
    return self::$medias;
  }

  protected static function get_media($code, $force = false){
    if ( !isset(self::$medias[$code]) || $force ){
      self::get_medias(1);
      if ( !isset(self::$medias[$code]) ){
        self::$medias[$code] = false;
      }
    }
    return isset(self::$medias[$code]) ? self::$medias[$code] : false;
  }

  public function medias(){
    return self::get_medias();
  }

  public function id_media($code){
    return self::get_media($code);
  }

  public function insert($title, $content, $private = false, $parent = null){
    if ( $usr = \bbn\user\connection::get_user() ){
      if ( $this->db->insert('bbn_notes', [
        'id_parent' => $parent,
        'private' => $private ? 1 : 0,
        'creator' => $usr->get_id()
      ]) ){
        $id_note = $this->db->last_id();
        $this->db->insert('bbn_notes_versions', [
          'id_note' => $id_note,
          'version' => 1,
          'title' => $title,
          'content' => $content,
          'id_user' => $usr->get_id(),
          'creation' => date('Y-m-d H:i:s')
        ]);
        return $id_note;
      }
    }
    return false;
  }

  public function latest($id){
    return $this->db->get_var("SELECT MAX(version) FROM bbn_notes_versions WHERE id_note = ?", $id);
  }

  public function get($id, $version = false){
    if ( !is_int($version) ){
      $version = $this->latest($id);
    }
    return $this->db->rselect('bbn_notes_versions', [], [
      'id_note' => $id,
      'version' => $version
    ]);
  }
}