# ust2kiko Version 0.1

UTAUのUSTファイルから歌書キコのボーカルパートを生成します。  

## 使い方
`ust2kiko <入力ファイル> <出力ファイル>`  
  
入力ファイルにはUSTファイルを設定します。  
このツールを使用する前に、USTファイルの歌詞を歌書キコの歌詞表記にしてください。(例: 「か」→「ka」)  
USTファイルに設定してあるピッチ情報、テンポ、各種フラグ等は全て無視されます。ベタ打ちのUSTから変換することをおすすめします。  

## 歌詞の記法について
基本的に歌書キコの歌詞表記と同一です。また、UTAUの仕様と同様に休符は「R」となります。  
歌詞に「-」を記述すると、前の音とタイ/スラーをつなげます。なお、USTの先頭だったり前の音が休符だったりする場合は休符扱いです。  

## 注意事項
- このツールを使用するには、PHPがインストールされている必要があります。
- このツールは出力ファイルが既に存在する場合も確認無しで上書きします。

## 設定事項
基本的にそのまま使用できますが、スクリプト上部の設定箇所を書き換えると、一部の動作をお好みで変更することができます。  
- MML1行あたりのノート数
	- 出力するMMLの1行にUSTのノートをいくつ分置くかを指定できます。
	- デフォルト: 12
- MMLでの音程表記
	- MMLで音程を表記するとき、どのような書き方をするか指定できます。
	- デフォルト: c, c+, d, d+, e, f, f+, g, g+, a, a+, b (+基準)
	- デフォルト設定の行の先頭に`//`を挿入して、その下の行の先頭にある`//`を取ると、-基準に変更することができます。
		- もちろん、書き方を自由に変更することもできます。

## ライセンス
[NYSL Version 0.9982](http://www.kmonos.net/nysl/)とします。  
"LICENSE"に日本語版、"LICENSE_en"に英語版を入れてあります。  

## 謝辞
FlMMLのティック数から音長指定に変換する処理について、[ᗝㅤᱝさんのユーザー記事](https://nico.ms/dic/5407677)にあります「ピコカキコにおける音長指定とtick数の対照表」を参考にさせていただきました。この場を借りて御礼申し上げます。  

## 更新履歴
### Version 0.1 (2022年12月22日)
- 初版公開

## お問い合わせ
- ニコニコ大百科: [u/88810887](https://nico.ms/dic/5634924)
- Twitter: [@user_88810887](https://twitter.com/user_88810887)
- メールアドレス: grj1234@protonmail.com
