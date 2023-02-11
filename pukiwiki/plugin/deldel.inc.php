<?php
/**
 * $Id: deldel.inc.php 148 2005-03-30 15:52:06Z okkez $
 *
 * 色んなものを一括削除するプラグイン
 * @version 1.40
 * ライセンス:PukiWiki本体と同じGPL2
 *
 */
require_once(PLUGIN_DIR.'attach.inc.php');
require_once(PLUGIN_DIR.'backup.inc.php');
/**
 * plugin_deldel_action
 * 色んなものを一括削除する
 *
 * @access    private
 * @param     String    NULL        ページ名
 *
 * @return    Array     ページタイトルと内容。
 */
function plugin_deldel_action()
{
    global $_attach_messages,$_deldel_messages;
    global $vars,$script;

    //変数の初期化
    $mode = isset($vars['mode']) ? $vars['mode'] : NULL;
    $page = isset($vars['page']) ? $vars['page'] : NULL;
    $date_begin_str = isset($vars['date_begin']) ? $vars['date_begin'] : NULL;
    $date_end_str = isset($vars['date_end']) ? $vars['date_end'] : NULL;
    $amount = isset($vars['amount']) ? $vars['amount'] : NULL;
    $status = array(0 => $_deldel_messages['title_delete_error'],
                    1 => $_deldel_messages['btn_delete']);
    $body = '';
    
    if(!isset($mode)){
        //最初のページ
        $body .= "<form method='post' action=\"$script?cmd=deldel\"><div>";
        $body .= '<select name="dir" size="1">';
        $body .= '<option value="DATA">wiki</option>';
        $body .= '<option value="BACKUP">backup</option>';
        $body .= '<option value="UPLOAD">attach</option>';
        $body .= '<option value="DIFF">diff</option>';
        $body .= '<option value="CACHE">cache</option>';
        $body .= '<option value="COUNTER">counter</option></select></div>';
        $body .= "<div>password:<input type=\"password\" name=\"pass\" size=\"12\"/>\n";
        $body .= "<div>page:<input type=\"text\" name=\"page\" size=\"5\"/>\n";
        $body .= "<div>amount:<input type=\"text\" name=\"amount\" size=\"5\"/>\n";    
        $body .= "<div>date begin:<input type=\"text\" name=\"date_begin\" size=\"5\"/>\n";    
        $body .= "<div>date end:<input type=\"text\" name=\"date_end\" size=\"5\"/>\n";        
        $body .= "<input type=\"hidden\" name=\"mode\" value=\"select\"/>\n";
        $body .= "<input type=\"submit\" value=\"{$_deldel_messages['btn_search']}\" /></div></form>";
        $body .= "<p>{$_deldel_messages['msg_body_start']}</p>";
        return array('msg'=>$_deldel_messages['title_deldel'],'body'=>$body);
    }elseif(isset($mode) && $mode === 'select'){
        if(isset($vars['pass']) && pkwk_login($vars['pass'])) {
            //認証が通ったらそれぞれページ名やファイル名の一覧を表示する
            $vars['pass'] = '';//認証が終わったのでパスを消去
            if(isset($vars['dir']) && $vars['dir']==="DATA") {
                //ページ
                $body .= make_body($vars['cmd'], DATA_DIR);
                return array('msg'=>$_deldel_messages['title_list'],'body'=>$body);
            }elseif(isset($vars['dir']) && $vars['dir']==="BACKUP"){
                //バックアップ
                $body .= make_body($vars['cmd'], BACKUP_DIR);
                return array('msg'=>$_deldel_messages['title_backuplist'],'body'=>$body);
            }elseif(isset($vars['dir']) && $vars['dir']==="UPLOAD"){
                //添付ファイル
                $body .= "\n<form method=\"post\" action=\"$script?cmd=deldel\"><div>";
                $retval = attach_list2($page, $amount, $date_begin_str,  $date_end_str);
                $body .= $retval['body'];
                $body .= "<input type=\"hidden\" name=\"mode\" value=\"confirm\"/>\n<input type=\"hidden\" name=\"dir\" value=\"{$vars['dir']}\"/>\n";
                $body .= "<input type=\"submit\" value=\"{$_deldel_messages['btn_concern']}\"/></div>\n</form>";
                $body .= $_deldel_messages['msg_check'];
                return array('msg'=>$retval['msg'],'body'=>$body);
            }elseif(isset($vars['dir']) && $vars['dir']==="DIFF") {
                //diff
                $body .= make_body($vars['cmd'], DIFF_DIR);
                return array('msg'=>$_deldel_messages['title_difflist'], 'body'=>$body);
            }elseif(isset($vars['dir']) && $vars['dir']==="CACHE") {
                //cache
                $body .= "<ul>\n<li>rel\n<ul>";
                $deleted_caches = sweap_cache();
                foreach($deleted_caches['rel'] as $key => $value) {
                    $body .= '<li>'. $value. '<ul><li>'. $key. '</li></ul></li>'."\n";
                }
                $body .= "</ul></li></ul>\n";
                $body .= "<ul><li>ref\n<ul>";
                foreach($deleted_caches['ref'] as $key => $value) {
                    $body .= '<li>'. $value. '<ul><li>'. $key. '</li></ul></li>'."\n";
                }
                $body .= '</ul></li></ul>';
                $body .= '<p>'. $_deldel_messages['msg_delete_success']. '</p>';
                return array('msg'=>$_deldel_messages['title_cachelist'], 'body'=>$body);
            }elseif(isset($vars['dir']) && $vars['dir']==="COUNTER") {
                //カウンター*.count
                $body .= make_body($vars['cmd'], COUNTER_DIR);
                return array('msg'=>$_deldel_messages['title_counterlist'], 'body'=>$body);
            }
        }elseif(isset($vars['pass']) && !pkwk_login($vars['pass'])){
            //認証エラー
            return array('msg' => $_deldel_messages['title_delete_error'],'body'=>$_deldel_messages['msg_auth_error']);
        }
    }elseif(isset($mode) && $mode === 'confirm'){
        //確認画面+もう一回認証要求？
        if(array_key_exists('page',$vars) and $vars['page'] != ''){
            return make_confirm('deldel', $vars['dir'], $vars['page']);
        }elseif(array_key_exists('regexp',$vars) && $vars['regexp'] != ''){
            $pattern = $vars['regexp'];
            foreach ( get_existpages() as $page ) {
                if (mb_ereg($pattern, $page)) {
                    $target[] = $page;
                }
            }
            if(is_null($target)){
                $error_msg = "<p>{$_deldel_messages['msg_regexp_error']}</p>\n";
                $error_msg .= "<p>". htmlspecialchars($pattern) ."</p>";
                $error_msg .= "<p><a href=\"$script?cmd=deldel\">".$_deldel_messages['msg_back_word']."</a></p>";
                return array('msg'=>$_deldel_messages['title_delete_error'] ,'body'=>$error_msg);
            }
            return make_confirm('deldel', $vars['dir'], $target);
        }else{
            //選択がなければエラーメッセージを表示する
            $error_msg = "<p>{$_deldel_messages['msg_error']}</p><p><a href=\"$script?cmd=deldel\">".$_deldel_messages['msg_back_word']."</a></p>";
            return array('msg'=>$_deldel_messages['title_delete_error'] ,'body'=>$error_msg);
        }
    }elseif(isset($mode) && $mode === 'exec'){
        //削除
        if(isset($vars['pass']) && pkwk_login($vars['pass'])) {
            switch($vars['dir']){
              case 'DATA':
                $mes = 'page';
                foreach($vars['page'] as $page){
                    $s_page = htmlspecialchars($page, ENT_QUOTES);
                    if(file_exists(get_filename($s_page)) && !is_freeze($s_page)){
                        $flag[$s_page] = true;
                        file_write(DATA_DIR, $s_page, '');
                    }else{
                        $flag[$s_page] = false;
                    }
                }
                break;
              case 'BACKUP':
                $mes = 'backup';
                foreach($vars['page'] as $page){
                    $s_page = htmlspecialchars($page, ENT_QUOTES);
                    if(function_exists('_backup_file_exists') ? _backup_file_exists($s_page) : backup_file_exists($s_page)){
                        $flag[$s_page] = true;
                        function_exists('_backup_delete') ? _backup_delete($s_page) : backup_delete($s_page);
                    }else{
                        $flag[$s_page] = false;
                    }
                }
                break;
              case 'UPLOAD':
                $mes = 'attach';
                $size = count($vars['file_a']);
                for($i=0;$i<$size;$i++){
                    foreach (array('refer', 'file', 'age') as $var) {
                        $vars[$var] = isset($vars[$var.'_a'][$i]) ? $vars[$var.'_a'][$i] : '';
                    }
                    $result = attach_delete();
                    //それぞれのファイルについて成功|失敗のフラグを立てる
                    switch($result['msg']){
                      case $_attach_messages['msg_deleted']:
                        $flag["{$vars['refer']}/{$vars['file']}"] = true;
                        break;
                      case $_attach_messages['err_notfound'] || $_attach_messages['err_noparm']:
                        $flag["{$vars['refer']}/{$vars['file']}"] = false;
                        break;
                      default:
                        $flag["{$vars['refer']}/{$vars['file']}"] = false;
                        break;
                    }
                }
                break;
              case 'DIFF' :
                $mes = 'diff';
                foreach($vars['page'] as $page){
                    $s_page = htmlspecialchars($page, ENT_QUOTES);
                    $f_page = get_filename2($mes,$s_page);
                    if(file_exists($f_page) && !is_freeze($s_page)){
                        $flag[$s_page] = unlink($f_page);
                    }else{
                        $flag[$s_page] = false;
                    }
                }
                break;
              case 'COUNTER':
                $mes = 'counter';
                foreach($vars['page'] as $page){
                    $s_page = htmlspecialchars($page, ENT_QUOTES);
                    $f_page = get_filename2($mes,$s_page);
                    if(file_exists($f_page) && !is_freeze($s_page)){
                        $flag[$s_page] = unlink($f_page);
                    }else{
                        $flag[$s_page] = false;
                    }
                }
                break;
            }
            if(in_array(false,$flag)){
                //削除失敗したものが一つでもある
                foreach($flag as $key=>$value){
                    $body .= "$key =&gt; {$status[$value]}<br/>\n";
                }
                $body .= "<p>{$_deldel_messages['msg_delete_error']}</p>";
                return array('msg' => $_deldel_messages['title_delete_error'],'body'=>$body);
            }else{
                //削除成功
                foreach($flag as $key=>$value){
                    $body .= "$key<br/>\n";
                }
                $body .= "<p>{$_deldel_messages['msg_delete_success']}</p>";
                return array('msg' => $_deldel_messages['title_delete_'.$mes] ,'body' => $body);
            }
        }
        elseif(isset($vars['pass']) && !pkwk_login($vars['pass'])){
            //認証エラー
            return array('msg' => $_deldel_messages['title_delete_error'],'body'=>$_deldel_messages['msg_auth_error']);
        }
    }
}
/**
 * page_list2
 * ページ一覧の作成 page_list()の一部を変更
 *
 * @access public
 * @param  Array   $pages        ページ名配列
 * @param  String  $cmd          コマンド
 * @param  Boolean $withfilename ファイルネームを返す(true)返さない(false)
 *
 * @return String                整形済みのページリスト
 */
function page_list2($pages, $cmd = 'read', $withfilename = FALSE)
{
    global $script, $list_index;
    global $_msg_symbol, $_msg_other;
    global $pagereading_enable;

    // ソートキーを決定する。 ' ' < '[a-zA-Z]' < 'zz'という前提。
    $symbol = ' ';
    $other = 'zz';

    $retval = '';

    if($pagereading_enable) {
        mb_regex_encoding(SOURCE_ENCODING);
        $readings = get_readings($pages);
    }

    $list = $matches = array();
    foreach($pages as $file=>$page) {
        $r_page  = rawurlencode($page);
        $s_page  = htmlspecialchars($page, ENT_QUOTES);
        $passage = get_pg_passage($page);
        // 変更ココから by okkez
        $freezed = is_freeze($page) ? '<span class="new1"> * </span>' : '';
        $exist_page = is_page($page) ? '' : '<span class="diff_added"> # </span>';
        $str = '   <li><input type="checkbox" name="page[]" value="' . $s_page . '"/><a href="' .
        $script . '?cmd=' . $cmd . '&amp;page=' . $r_page .
        '">' . $s_page . '</a>' . $passage . $freezed . $exist_page;
        // ココまで

        if ($withfilename) {
            $s_file = htmlspecialchars($file);
            $str .= "\n" . '    <ul><li>' . $s_file . '</li></ul>' .
            "\n" . '   ';
        }
        $str .= '</li>';

        // WARNING: Japanese code hard-wired
        if($pagereading_enable) {
            if(mb_ereg('^([A-Za-z])', mb_convert_kana($page, 'a'), $matches)) {
                $head = $matches[1];
            } elseif(mb_ereg('^([ァ-ヶ])', $readings[$page], $matches)) { // here
                $head = $matches[1];
            } elseif (mb_ereg('^[ -~]|[^ぁ-ん亜-熙]', $page)) { // and here
                $head = $symbol;
            } else {
                $head = $other;
            }
        } else {
            $head = (preg_match('/^([A-Za-z])/', $page, $matches)) ? $matches[1] :
            (preg_match('/^([ -~])/', $page, $matches) ? $symbol : $other);
        }

        $list[$head][$page] = $str;
    }
    ksort($list);

    $cnt = 0;
    $arr_index = array();
    $retval .= '<ul>' . "\n";
    foreach ($list as $head=>$pages) {
        if ($head === $symbol) {
            $head = $_msg_symbol;
        } else if ($head === $other) {
            $head = $_msg_other;
        }

        if ($list_index) {
            ++$cnt;
            $arr_index[] = '<a id="top_' . $cnt .
            '" href="#head_' . $cnt . '"><strong>' .
            $head . '</strong></a>';
            $retval .= ' <li><a id="head_' . $cnt . '" href="#top_' . $cnt .
            '"><strong>' . $head . '</strong></a>' . "\n" .
            '  <ul>' . "\n";
        }
        ksort($pages);
        $retval .= join("\n", $pages);
        if ($list_index)
        $retval .= "\n  </ul>\n </li>\n";
    }
    $retval .= '</ul>' . "\n";
    if ($list_index && $cnt > 0) {
        $top = array();
        while (! empty($arr_index))
        $top[] = join(' | ' . "\n", array_splice($arr_index, 0, 16)) . "\n";

        $retval = '<div id="top" style="text-align:center">' . "\n" .
        join('<br />', $top) . '</div>' . "\n" . $retval;
    }
    return $retval;
}

/**
 * make_body
 * DATA_DIR,BACKUP_DIR,DIFF_DIR,COUNTER_DIRの一覧を作る。
 *
 * @access private
 * @param  String  $cmd コマンド
 * @param  String  $dir DATA_DIR or BACKUP_DIR のどちらか一方。省略不可
 *
 * @return String       一覧表示のbody部分を返す。
 */
function make_body($cmd, $dir)
{
    global $script, $_deldel_messages, $_freeze2_messages, $_unfreeze2_messages;
    $mes = "_{$cmd}_messages";
    if($dir === DATA_DIR) {
        $ext = '.txt';
    }elseif($dir === BACKUP_DIR) {
        $ext = BACKUP_EXT;
    }elseif($dir === DIFF_DIR) {
        $ext = '.txt';
    }elseif($dir === COUNTER_DIR) {
        $ext = '.count';
    }

    $body .= "<form method='post' action=\"$script?cmd=$cmd\"><div>\n";
    $body .= "{${$mes}['msg_regexp_label']}<input type='text' name='regexp' />\n";
    $body .= "<input type=\"submit\" value=\"{${$mes}['btn_concern']}\" />";
    $body .= page_list2(get_existpages($dir, $ext));
    if($dir === DATA_DIR) {
        $dir = 'DATA';
    }elseif($dir === BACKUP_DIR) {
        $dir = 'BACKUP';
    }elseif($dir === DIFF_DIR) {
        $dir = 'DIFF';
    }elseif($dir === COUNTER_DIR) {
        $dir = 'COUNTER';
    }
    $body .= "<input type=\"hidden\" name=\"mode\" value=\"confirm\"/>\n";
    $body .= "<input type=\"hidden\" name=\"dir\" value=\"{$dir}\"/>\n";
    $body .= "<input type=\"submit\" value=\"{${$mes}['btn_concern']}\" /></div></form>\n";
    $body .= ${$mes}['msg_check'];

    return $body;
}

/**
 * make_confirm
 * 確認画面を作る
 * globalで変数を引き回すのはあまりよくない気がしたので引数で渡してみた
 *
 * @access public
 * @param  String  $cmd    コマンド [deldel|freeze2|unfreeze2]
 * @param  String  $dir    $vars['dir']を使う
 * @param  String  $pages  $vars['page']を使う
 *
 * @return Array   ページタイトルと内容
 */
function make_confirm($cmd, $dir, $pages)
{
    global $_deldel_messages, $_freeze2_messages, $_unfreeze2_messages;

    $i=0;
    $mes = "_{$cmd}_messages";
    $body .= "<form method=\"post\" action=\"$script?cmd=$cmd\">\n<ul>\n";
    switch($dir){
      case 'DATA' :
      case 'BACKUP' :
      case 'DIFF' :
      case 'COUNTER':
        foreach($pages as $page){
            $s_page = htmlspecialchars($page,ENT_QUOTES);
            $body .= "<li><input type=\"hidden\" name=\"page[$i]\" value=\"$s_page\"/>$s_page<br/></li>\n";
            $i++;
        }
        break;
      case 'UPLOAD' :
        foreach($pages as $page){
            $s_page = htmlspecialchars($page,ENT_QUOTES);
            $temp = preg_split("/=|&amp;/",$s_page);
            $file = rawurldecode($temp[1]);
            $refer = rawurldecode($temp[3]);
            $age = isset($temp[5])? rawurldecode($temp[5]) : 0 ;
            $body .= "<li><input type=\"hidden\" name=\"page[$i]\" value=\"$s_page\"/>$refer/$file";
            $body .= "<input type=\"hidden\" name=\"refer_a[$i]\" value=\"$refer\"/>";
            $body .= "<input type=\"hidden\" name=\"file_a[$i]\" value=\"$file\"/>";
            $body .= "<input type=\"hidden\" name=\"age_a[$i]\" value=\"$age\"/></li>\n";
            $i++;
        }
        break;
      default :
        return array('msg' => ${$mes}['title_delete_error'],'body'=>${$mes}['msg_fatal_error']);
        break;
    }
    $body .= "</ul>\n<div><input type=\"password\" name=\"pass\" size=\"12\"/>\n";
    $body .= '<input type="hidden" name="mode" value="exec"/><input type="hidden" name="dir" value="'.$dir.'"/>';
    $body .= "<input type=\"submit\" value=\"{${$mes}['btn_exec']}\"/>\n</div></form>\n";
    $body .= "<p>{${$mes}['msg_auth']}</p>";
    return array('msg'=>${$mes}['title_select_list'],'body'=>$body);
}

/**
 * AttachFile2
 * AttachFileを継承したクラス
 * toStringメソッドをcheckboxと凍結フラグを表示するように変更
 * &amp;の位置を変更している
 */
class AttachFile2 extends AttachFile
{
    /**
     * ページ名に対して色々なリンクを一つにまとめて返す。
     *
     * @param  hoge  $showicon
     * @param  hoge  $showinfo
     *
     * @return String
     */
    function toString($showicon, $showinfo)
    {
        global $script, $_attach_messages, $vars;

        $this->getstatus();
        $param  = 'file=' . rawurlencode($this->file) . '&amp;refer=' . rawurlencode($this->page) .
        ($this->age ? '&amp;age=' . $this->age : '');
        $title = $this->time_str . ' ' . $this->size_str;
        $label = ($showicon ? PLUGIN_ATTACH_FILE_ICON : '') . htmlspecialchars($this->file) . '(' . htmlspecialchars($this->time_str) . ')';
        if ($this->age) {
            $label .= ' (backup No.' . $this->age . ')';
        }
        $info = $count = $retval = $freezed = '';
        if ($showinfo) {
            $_title = str_replace('$1', rawurlencode($this->file), $_attach_messages['msg_info']);
            $info = "\n<span class=\"small\">[<a href=\"$script?plugin=attach&amp;pcmd=info$param\" title=\"$_title\">{$_attach_messages['btn_info']}</a>]</span>\n";
            $count = ($showicon && ! empty($this->status['count'][$this->age])) ?
            sprintf($_attach_messages['msg_count'], $this->status['count'][$this->age]) : '';
        }
        $freezed = $this->status['freeze'] ? '<span class="new1"> * </span>' : '';
        $retval .= $vars['cmd'] === 'deldel' |
        $vars['cmd'] === 'freeze2' |
        $vars['cmd'] === 'unfreeze2' ?
        "<input type=\"checkbox\" checked name=\"page[]\" value=\"$param\"/>" : '';
        $retval .= "<a href=\"$script?plugin=attach&amp;pcmd=open&amp;$param\" title=\"$title\">$label</a>$count$info$freezed";
        return $retval;
    }
}
/**
 * AttachFiles2
 * AttachFilesを継承したクラス
 * AttachFile2を使うようにだけ修正
 */
class AttachFiles2 extends AttachFiles
{
    function add($file, $age)
    {
        $result = new AttachFile2($this->page, $file, $age);
        $this->files[$file][$age] = $result;
        return $result;
    }

}
/**
 * AttachPages2
 * AttachPagesを継承したクラス
 * コンストラクタをちょこっと変更
 */
class AttachPages2 extends AttachPages
{
    function AttachPages2($page = '', $age = NULL, $begin= 0, $end= 100, $date_begin_str, $date_end_str)
    {

        $dir = opendir(UPLOAD_DIR) or
        die('directory ' . UPLOAD_DIR . ' is not exist or not readable.');

        $page_pattern = ($page == '') ? '(?:[0-9A-F]{2})+' : preg_quote(encode($page), '/');
        $age_pattern = ($age === NULL) ?
        '(?:\.([0-9]+))?' : ($age ?  "\.($age)" : '');
        $pattern = "/^({$page_pattern})_((?:[0-9A-F]{2})+){$age_pattern}$/";

        $date_begin = strtotime($date_begin_str);
        $date_end = strtotime($date_end_str);

        $matches = array();
        $idx = 0;
        while ($file = readdir($dir)) {
            if (! preg_match($pattern, $file, $matches)) continue;

            $file_date = filemtime(UPLOAD_DIR.$file) - LOCALZONE;

            if( ($file_date < $date_begin) || ($file_date > $date_end)) continue;

            if($idx >= $end) break;

            if($idx >= $begin){
                $_page = decode($matches[1]);
                $_file = decode($matches[2]);
                $_age  = isset($matches[3]) ? $matches[3] : 0;

                if (! isset($this->pages[$_page])) {
                    $this->pages[$_page] = new AttachFiles2($_page);
                }

                $elem = $this->pages[$_page]->add($_file, $_age);
            }

            $idx++;
        }
        closedir($dir);
    }
}
/**
 * attach_list2
 * 添付ファイルの一覧取得 attach_list()をちょこっと改変
 *
 * @access private
 * @param  Void    引数はなし
 *
 * @return Array   PukiWikiのプラグイン仕様に従ったもの
 *
 */
function attach_list2($page = 1, $amount = 100, $date_begin_str = '1999/01/01', $date_end_str='2999/01/01')
{
    global $vars, $_attach_messages;

    $refer = isset($vars['refer']) ? $vars['refer'] : '';

    $begin = ($page - 1) * $amount;
    $end = $begin + $amount;

    $obj = new AttachPages2($refer,NULL,$begin, $end, $date_begin_str, $date_end_str);

    $msg = $_attach_messages[($refer == '') ? 'msg_listall' : 'msg_listpage'];
    $body = ($refer == '' || isset($obj->pages[$refer])) ?
    $obj->toString($refer, FALSE) :
    $_attach_messages['err_noexist'];

    return array('msg'=>$msg, 'body'=>$body);
}

/**
 * get_filename2
 * Get physical file name of the page
 *
 * @param  String $dir    ディレクトリ名 counter or diff
 * @param  String $page   ページ名
 * @return String         物理ファイル名
 */
function get_filename2($dir,$page)
{
    switch($dir){
      case 'counter' :
        return COUNTER_DIR . encode($page) . '.count' ;
        break;
      case 'diff' :
        return DIFF_DIR . encode($page) . '.txt' ;
        break;
    }
}

/**
 * sweap_cache();
 * キャッシュのお掃除。元ファイルの存在しないキャッシュを問答無用で削除する。
 * @return Array 削除したファイル名=>削除したファイル名をデコードしたもの
 */
function sweap_cache()
{
    $rel = get_existpages(CACHE_DIR, '.rel');
    foreach($rel as $key => $value){
        if (is_page($value)){
            continue;
        }else{
            unlink(CACHE_DIR.$key);
            $delete_rel[$key] = $value;
        }
    }
    $ref = get_existpages(CACHE_DIR, '.ref');
    foreach($ref as $key => $value){
        if (is_page($value)){
            continue;
        }else{
            unlink(CACHE_DIR.$key);
            $delete_ref[$key] = $value;
        }
    }
    natcasesort($delete_rel);
    natcasesort($delete_ref);
    return array('rel' => $delete_rel,
                 'ref' => $delete_ref);
}
?>