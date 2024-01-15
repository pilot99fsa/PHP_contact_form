<?php
  session_start();
  $mode = 'input';

  // エラーメッセージの初期化
  $errmessage = array();

  if( isset($_POST['back']) && $_POST['back'] ){
    // 何もしない
  } else if( isset( $_POST['confirm']) && $_POST['confirm']) {
    // 確認画面

    // 名前
    if (! $_POST['fullname']){
        $errmessage[] = "名前を入力して下さい。";
    } else if ( mb_strlen($_POST['fullname']) > 100 ) {
        $errmessage[] = "名前は100文字以内にして下さい。";
    }
    // プログラムのようなコードが入った文字列を無害化する
    $_SESSION['fullname'] = htmlspecialchars($_POST["fullname"], flags:ENT_QUOTES);

    // メールアドレス
    if (! $_POST['email']){
      $errmessage[] = "Eメールを入力して下さい。";
  } else if ( mb_strlen($_POST['email']) > 200 ) {
      $errmessage[] = "Eメールは200文字以内にして下さい。";
  } else if (!filter_var($_POST['email'], filter:FILTER_VALIDATE_EMAIL)) { // Eメールの形式になっているかチェックする(aaa@aaa.comみたいになってるか否か)
    $errmessage[] = "メールアドレスが不正です";
  } 
  $_SESSION['email'] = htmlspecialchars($_POST["email"], flags:ENT_QUOTES);
   
    // 入力内容(メッセージ)
    if (! $_POST['message']){
      $errmessage[] = "お問い合わせ内容を入力して下さい。";
  } else if ( mb_strlen($_POST['message']) > 500 ) {
      $errmessage[] = "お問い合わせ内容は500文字以内にして下さい。";
  }
  $_SESSION['message']  = htmlspecialchars($_POST["message"], flags:ENT_QUOTES);

    // 確認画面が来たら
  if ( $errmessage ) {
    $mode = 'input';
   } else {
    // CSRF(クロスサイトリクエストフォージェリ)の対策の処理
    $token = bin2hex(random_bytes(length:32)); // PHP7の場合はrandom_bytes関数を使う
    // $token = bin2hex(mcrypt_create_iv(length:32, source: MCRYPT_DEV_URANDOM)); PHP5の場合はこちらを使う
    $_SESSION['token'] = $token; //トークンを生成して$_SESSION['token']に保存する
    $mode = 'confirm';
   }
    
  } else if( isset( $_POST['send']) && $_POST['send']) {
    // 送信ボタンを押した時の処理を記述

    // 入力された情報が取得できなかった場合や、サーバーに情報がなかった場合のエラー処理
    if(!$_POST['token'] || !$_SESSION['email'] || !$_SESSION['email'] ) {
      $errmessage[] = '不正な処理が行われました';
      $_SESSION     = array(); //セッション情報も念の為に消去する
      $mode         = 'input'; // 入力画面に強制的に遷移させる
    // トークンが一致しているか確認するための処理
    } else if( $_POST['token'] != $_SESSION['token']) { // 「!」は「一致していなければ」を意味する判定記号
      $errmessage[] = '不正な処理が行われました';
      $_SESSION     = array(); 
      $mode         = 'input'; 
    } else {
      $message  = "お問い合わせを受け付けました \r\n"
      . "名前: " . $_SESSION['fullname'] . "\r\n"
      . "email: " . $_SESSION['email'] . "\r\n"
      . "お問い合わせ内容:\r\n"
      . preg_replace("/\r\n|\r|\n/", "\r\n", $_SESSION['message']);
mail($_SESSION['email'],'お問い合わせありがとうございます',$message);
mail('*****@*****.*****','お問い合わせありがとうございます',$message); //任意のメールアドレスを設定する
$_SESSION = array();
$mode = 'send';
    }
  } else {
    // セッションを初期化するコード。お問い合わせを送信後はセッションをクリアにする。セッションはいつまでも残しておくとセキュリティ上のリスクとなる
    $_SESSION['fullname'] = "";
    $_SESSION['email']    = "";
    $_SESSION['message']  = "";
    // $_SESSION = array()だけでも構わないが、要素ごとに空の配列をセットすると丁寧である
  }
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>お問い合わせフォーム</title>
</head>
<body>

    <?php if( $mode == 'input') { ?>
        
        <!-- 入力フォームの画面のHTML -->
      <?php
       if( $errmessage ){
         echo '<div style="color:red;">';
         echo implode('<br>', $errmessage );
         echo '</div>';
       }
      ?>
        <form action="./contactform.php" method="post">
            名前   <input type="text"  name="fullname" value="<?php echo $_SESSION['fullname'] ?>"><br>
            Eメール<input type="email" name="email"    value="<?php echo $_SESSION['email'] ?>"><br>
            お問い合わせ内容<br>
            <textarea cols="40" rows="8" name="message"><?php echo $_SESSION['message'] ?></textarea><br>
            <!-- textareaタグにはvalueではなくタグとタグの間にphpを埋め込む点に注意する -->
            <input type="submit" name="confirm" value="確認">
        </form>

    <?php } else if ($mode == 'confirm'){ ?>

        <!-- 確認の画面 -->
        <form action="./contactform.php" method="post">
            <input type= "hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
            名前<?php echo $_SESSION['fullname'] ?><br>
            Eメール<?php echo $_SESSION['email'] ?><br>
            お問い合わせ内容<br>
            <?php echo nl2br($_SESSION['message'])?><br> 
            <!-- nl2brとは？改行文字の前に改行タグを挿入する関数である。-->
            <input type="submit" name="back" value="戻る">
            <input type="submit" name="send" value="送信">
        </form>

    <?php } else {?>

        <!-- 完了画面 -->
        送信しました。お問い合わせいただき、ありがとうございます。
    <?php } ?>

    <?php
    print_r($_POST);
    ?>
</body>
</html>