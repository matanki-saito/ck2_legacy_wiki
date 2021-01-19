<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: divregion.inc.php,v 1.1 2018.Mar.
//
// H.Tomose
// region.inc.php を参考に作成。
// Tableを使っていた Region をアレンジし、
// <div> で実現する。
// 
// 書式は別途css で定義すること。必要なものは以下：
//div.divregion{ 標準でのヘッダ行
//div.divregion_contents{ 標準での本文部分
// div.divregion_h1{ h1指定時のヘッダ行
//div.divregion_h2{ h2指定時のヘッダ行
//
//----
// Ver1.1 では、スタイル指定を拡張しました。
// ・h1,h2 以外のスタイルを指定できるように。
//   divregion_xxx,divregion_h1_xxx を事前定義しておいて、
//   上記xxx 部分を文字列指定できるようにしました。
// ・body 部分の文字色・背景色を指定できるようにしました。

function plugin_divregion_convert()
{
	static $builder = 0;
	if( $builder==0 ) $builder = new DivRegionPluginHTMLBuilder();

	// static で宣言してしまったので２回目呼ばれたとき、前の情報が残っていて変な動作になるので初期化。
	$builder->setDefaultSettings();

	// 引数が指定されているようなので解析
	if (func_num_args() >= 1){
		$args = func_get_args();
		$builder->setDescription( array_shift($args) );
		foreach( $args as $value ){
			// opened が指定されたら初期表示は開いた状態に設定
			if( preg_match("/^open/i", $value) ){
				$builder->setOpened();
			// closed が指定されたら初期表示は閉じた状態に設定。
			}elseif( preg_match("/^close/i", $value) ){
				$builder->setClosed();
			// h1 が指定されたら、べたぬりへっど
			}elseif( preg_match("/^h1/i", $value) ){
				$builder->setH1();
			// h2 が指定されたら、アンダーバーへっど
			}elseif( preg_match("/^h2/i", $value) ){
				$builder->setH2();
			}elseif( preg_match("/^hstyle:([0-9a-zA-Z]*)/i", $value,$match) ){
				$builder->setHCSS($match[1]);
			}elseif( preg_match("/^cstyle:([0-9a-zA-Z]*)/i", $value,$match) ){
				$builder->setCCSS($match[1]);

			}elseif( preg_match("/^color:(#[0-9a-fA-F]*)/i", $value,$match) ){
				$builder->AddCSS( $value);
			}elseif( preg_match("/^background-color:(#[0-9a-fA-F]*)/i", $value,$match) ){
				$builder->AddCSS( 'background-color:'.$match[1]);
			}elseif( preg_match("/^content-color:(#[0-9a-fA-F]*)/i", $value,$match) ){
				$builder->AddBodyCSS( 'color:'.$match[1]);
			}elseif( preg_match("/^content-bgcolor:(#[0-9a-fA-F]*)/i", $value,$match) ){
				$builder->AddBodyCSS( 'background-color:'.$match[1]);

			}

		}
	}
	// ＨＴＭＬ返却
	return $builder->build();
} 


// クラスの作り方⇒http://php.s3.to/man/language.oop.object-comparison-php4.html
class DivRegionPluginHTMLBuilder
{
	var $description;
	var $headchar;
	var $isopened;
	var $scriptVarName;
	var $borderstyle;
	var $headerstyle;
	var $divclass;
	var $contentclass;

	//↓ buildメソッドを呼んだ回数をカウントする。
	//↓ これは、このプラグインが生成するJavaScript内でユニークな変数名（被らない変数名）を生成するために使います
	var $callcount;

	function DivRegionPluginHTMLBuilder() {
		$this->callcount = 0;
		$this->setDefaultSettings();
	}
	function setDefaultSettings(){
		$this->description = "...";
		$this->isopened = false;
		$this->headerstyle = 'cursor:pointer;'; 
		$this->borderstyle = ''; 
		$this->headchar = "▼";
		$this->divclass = 'divregion';
		$this->contentclass = 'divregion_contents';
	}
	function setClosed(){ $this->isopened = false; }
	function setOpened(){ $this->isopened = true; }
	function setH1(){ $this->divclass = 'divregion_h1'; }
	function setH2(){ $this->divclass = 'divregion_h2'; }
	function setHCSS($foo){ $this->divclass = 'divregion_'.$foo; }
	function setCCSS($foo){ $this->contentclass = 'divregion_contents_'.$foo; }
	function AddCSS($foo){ $this->headerstyle .= $foo.';'; }
	function AddBodyCSS($foo){ $this->borderstyle .= $foo.';'; }
	// convert_html()を使って、概要の部分にブランケットネームを使えるように改良。
	function setDescription($description){
		$this->description = convert_html($description);
		// convert_htmlを使うと <p>タグで囲まれてしまう。Mozzilaだと表示がずれるので<p>タグを消す。
		$this->description = preg_replace( "/^<p>/i", "", $this->description);
		$this->description = preg_replace( "/<\/p>$/i", "", $this->description);
	}
	function build(){
		$this->callcount++;
		$html = array();
		// 以降、ＨＴＭＬ作成処理
		array_push( $html, $this->buildSummaryHtml() );
		array_push( $html, $this->buildContentHtml() );
		return join($html);
	}

	// ■ 縮小表示しているときの表示内容。
	function buildSummaryHtml(){
		$summarystyle = ($this->isopened) ? 
			$this->headerstyle."display:none;" : 
			$this->headerstyle."display:block;";
		$summarystyle2 = ($this->isopened) ? 
			$this->headerstyle."display:block;":
			$this->headerstyle."display:none;" ;
			
		return <<<EOD

<div class='$this->divclass' id='drgn_summary$this->callcount' style="$summarystyle" 
	onclick="
		document.getElementById('drgn_content$this->callcount').style.display='block';
		document.getElementById('drgn_summaryV$this->callcount').style.display='block';
		document.getElementById('drgn_summary$this->callcount').style.display='none';
">▼$this->description
</div>
<div class='$this->divclass' id='drgn_summaryV$this->callcount' style="$summarystyle2"
	onclick="
		document.getElementById('drgn_content$this->callcount').style.display='none';
		document.getElementById('drgn_summaryV$this->callcount').style.display='none';
		document.getElementById('drgn_summary$this->callcount').style.display='block';
	">▲$this->description
</div>
EOD;
	}

	// ■ 展開表示しているときの表示内容ヘッダ部分。ここの</div>の閉じタグは endregion 側にある。
	function buildContentHtml(){
		$contentstyle = ($this->isopened) ? 
			$this->borderstyle."display:block;" : 
			$this->borderstyle."display:none;";
		return <<<EOD
<div class='$this->contentclass' id='drgn_content$this->callcount' style="$contentstyle">
EOD;
	}
//valign='top' 

}// end class RegionPluginHTMLBuilder

?>