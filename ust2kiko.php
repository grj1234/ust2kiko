<?php
// ust2kiko Version 0.1 by grj1234
// https://github.com/grj1234/ust2kiko
//
// Usage: ust2kiko <infile> <outfile>
// License: NYSL Version 0.9982

// 設定 ここから
//  MML1行あたりのノート数を指定
define('NOTES_PER_LINE',12);
//  MMLでの音程表記設定 (半音を+で書くか-で書くかはお好みで選んでください)
define('NOTENUM2MML',['c','c+','d','d+','e','f','f+','g','g+','a','a+','b']);
//define('NOTENUM2MML',['c','d-','d','e-','e','f','g-','g','a-','a','b-','b']);
// 設定 ここまで

// テーブル ここから
//  MMLのティック数から音長指定へ変換する時に使うテーブル
//  (ᗝㅤᱝさんのユーザー記事を参考に作成 https://nico.ms/dic/5407677 ありがとうございました)
define('TICKS2LEN',[
	  1=>'384',
	  2=>'192',
	  3=>'128',
	  4=>'96',
	  6=>'64',
	  7=>'96..',
	  8=>'48',
	  9=>'64.',
	 12=>'32',
	 14=>'48..',
	 15=>'48...',
	 16=>'24',
	 18=>'32.',
	 21=>'32..',
	 24=>'16',
	 28=>'24..',
	 30=>'24...',
	 31=>'24....',
	 32=>'12',
	 36=>'16.',
	 42=>'16..',
	 45=>'16...',
	 48=>'8',
	 56=>'12..',
	 60=>'12...',
	 62=>'12....',
	 63=>'12.....',
	 64=>'6',
	 72=>'8.',
	 84=>'8..',
	 90=>'8...',
	 93=>'8....',
	 96=>'4',
	112=>'6..',
	120=>'6...',
	124=>'6....',
	126=>'6.....',
	127=>'6......',
	128=>'3',
	144=>'4.',
	168=>'4..',
	180=>'4...',
	186=>'4....',
	189=>'4.....',
	192=>'2',
	224=>'3..',
	240=>'3...',
	248=>'3....',
	252=>'3.....',
	254=>'3......',
	255=>'3.......',
	288=>'2.',
	336=>'2..',
	360=>'2...',
	372=>'2....',
	378=>'2.....',
	381=>'2......',
	384=>'1',
	576=>'1.',
	672=>'1..',
	720=>'1...',
	744=>'1....',
	756=>'1.....',
	762=>'1......',
	765=>'1.......'
]);
//  降順ソートしてあるティック数のリスト(検索用)を作る
$unsortedTicks2LenKeys=array_keys(TICKS2LEN);
rsort($unsortedTicks2LenKeys);
define('TICKS2LEN_KEYS',$unsortedTicks2LenKeys);
unset($unsortedTicks2LenKeys);
// テーブル ここまで

// 以下 メイン処理
echo 'ust2kiko Version 0.1 by grj1234'.PHP_EOL.
     'https://github.com/grj1234/ust2kiko'.PHP_EOL.PHP_EOL;
// 引数が不足しているときはUsageを表示して終了
if($argc<3) {
	echo 'Usage: ust2kiko <infile> <outfile>'.PHP_EOL;
	exit(0);
}
$inputArg=trim($argv[1]); // 入力ファイル名
$outputArg=trim($argv[2]); // 出力ファイル名
if(!file_exists($inputArg)) {
	// 入力ファイルが存在しないとき
	throw new Exception('Error: infile not found');
}
if(!is_file($inputArg)) {
	// 入力ファイルが通常ファイルでないとき
	throw new Exception('Error: infile is not a file');
}
// USTはINIとしてロードできるはず。
// ※INI_SCANNER_RAWを指定して値がparseされないように。じゃないと「Lyric=no」がfalseにparseされたりする。
$ustFile=parse_ini_file($inputArg,true,INI_SCANNER_RAW);
if($ustFile===false) {
	// ロード失敗
	throw new Exception('Error: failed to load UST file');
}
unset($inputArg);
if(!isset($ustFile['#SETTING'])){
	// [#SETTING]がないならUSTじゃないか壊れてる可能性が高い
	throw new Exception('Error: invalid UST file');
}
if(!isset($ustFile['#TRACKEND'])) {
	// [#TRACKEND]がないなら警告を出す (ノート数があまりにも多かったりUST保存中にエラーが起きたりしない限り発生しない)
	// ※例えば、ノートイベントが32768個を超えた状態でUTAUで保存すると、32768個目までのノートイベントが出力され、[#TRACKEND]は出力されない。
	// ※※まあ、ノートイベントが32768個を超えた時点でUTAUが「オーバーフロー」とエラーメッセージを出しまくるからまともにUST編集できなくなるけど。
	echo 'Warning: [#TRACKEND] not found (corrupted UST file?)';
}
$notesArray=[]; // FlMMLの仕様に合わせたノート情報を格納する配列
for($i=0;$i<=32767;$i++) { // セクション名の通し番号の最大値は32767。(UTAU 0.4.18で確認)
	$sectionName='#'.sprintf('%04d',$i);
	if(!isset($ustFile[$sectionName])) {
		if($i==0) {
			// USTにノートイベントが無い
			throw new Exception('Error: UST file has no note events');
		}
		// セクションが無かったらその時点でUSTファイル終了と判定
		break;
	}
	$ustNoteInfo=$ustFile[$sectionName];
	unset($sectionName);
	// 音程、長さを抜き出してFlMMLの仕様に合わせる
	$ustNoteNum=intval($ustNoteInfo['NoteNum']);
	$ustNoteLength=intval($ustNoteInfo['Length']);
	$mmlOctave=intdiv($ustNoteNum,12);
	$mmlNoteNum=$ustNoteNum%12;
	unset($ustNoteNum);
	if(($ustNoteLength%5)!=0) {
		// USTのノートの長さが5の倍数でなければエラー (FlMMLのティック数への変換時に誤差が出ないように)
		throw new Exception('Error: note length in UST file must be a multiple of 5 to convert to FlMML');
	}
	$mmlNoteLength=intdiv($ustNoteLength,5);
	unset($ustNoteLength);
	// FlMMLの仕様に合わせたノート情報を配列に
	$notesArray[]=['lyric'=>$ustNoteInfo['Lyric'],'octave'=>$mmlOctave,'noteNum'=>$mmlNoteNum,'length'=>$mmlNoteLength];
	unset($ustNoteInfo);
	unset($mmlOctave);
	unset($mmlNoteNum);
	unset($mmlNoteLength);
}
unset($i);
unset($ustFile);
$nowOctave=5; // 歌書キコの標準設定では初期状態はo5
$nowLyric='';
$mmlOutput='';
$mmlNotesCount=0;
$isTieUsable=false; // 初期状態ではタイ/スラーの使用は不可
foreach($notesArray as $targetNote) {
	$targetLyric=$targetNote['lyric'];
	$targetOctave=$targetNote['octave'];
	$targetNoteNum=$targetNote['noteNum'];
	$targetLength=$targetNote['length'];
	$isTargetRest=($targetLyric=='R')||(($targetLyric==='-')&&(!$isTieUsable)); // 歌詞が「R」のとき、または歌詞が「-」でタイ/スラー使用不可のときは休符扱いに
	$targetMml='';
	if(!$isTargetRest) {
		// 休符でないとき
		if($targetLyric==='-') {
			// 歌詞が「-」ならタイ/スラーを付加
			$targetMml.='& ';
		}
		if($targetOctave!=$nowOctave) {
			// オクターブの変更が必要なら変更する
			$targetMml.=str_repeat((($targetOctave>$nowOctave)?'<':'>'),abs($targetOctave-$nowOctave)).' ';
			$nowOctave=$targetOctave;
		}
		if(($targetLyric!=='-')&&($targetLyric!==$nowLyric)) {
			// 歌詞の変更が必要なら変更する
			$targetMml.='$V{'.$targetLyric.'} ';
			$nowLyric=$targetLyric;
		}
		$targetMml.=NOTENUM2MML[$targetNoteNum].mmlTicksToLen($targetLength);
	} else {
		// 休符のとき
		$targetMml='r'.str_replace('&','r',mmlTicksToLen($targetLength)); // 休符に&を付けることはできないので&をrに置換
	}
	unset($targetLyric);
	unset($targetOctave);
	unset($targetNoteNum);
	unset($targetLength);
	$mmlOutput.=$targetMml.' ';
	unset($targetMml);
	$isTieUsable=!$isTargetRest; // 休符なら次のノートでタイ/スラーは使用不可
	unset($isTargetRest);
	$mmlNotesCount++;
	if($mmlNotesCount>=NOTES_PER_LINE){
		$mmlOutput.="\r\n";
		$mmlNotesCount=0;
	}
}
unset($nowOctave);
unset($nowLyric);
unset($mmlNotesCount);
$mmlOutput=str_replace(" \r\n","\r\n",$mmlOutput."\r\n");
if(file_put_contents($outputArg,$mmlOutput)) {
	echo 'Done.'.PHP_EOL;
} else {
	// 保存失敗
	throw new Exception('Error: failed to save MML file');
}
exit(0);

function mmlTicksToLen(int $ticks) {
	// FlMMLのティック数から音長指定へ
	$remainingTicks=$ticks;
	$lengthString='';
	if($remainingTicks>=768) {
		// 768ticks以上なら1&の繰り返しでそれ以下になるように崩す
		$repeatTimes=intdiv($remainingTicks,384)-1;
		$lengthString.=str_repeat('1&',$repeatTimes);
		$remainingTicks-=384*$repeatTimes;
		unset($repeatTimes);
	}
	while($remainingTicks>0) {
		if(isset(TICKS2LEN[$remainingTicks])) {
			// 残りティック数にピッタリとなる値がテーブルにある
			$lengthString.=TICKS2LEN[$remainingTicks];
			$remainingTicks=0;
			break;
		} else {
			// 残りティック数にピッタリとなる値がテーブルにない
			foreach(TICKS2LEN_KEYS as $ticksToSearch) {
				// 近い値を探す (TICKS2LEN_KEYSがrsort実行済でないとうまく動かないので注意)
				if($ticksToSearch<$remainingTicks) {
					$lengthString.=TICKS2LEN[$ticksToSearch]."&";
					$remainingTicks-=$ticksToSearch;
					break;
				}
			}
			unset($ticksToSearch);
		}
	}
	unset($remainingTicks);
	return $lengthString;
}
