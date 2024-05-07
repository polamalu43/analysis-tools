<?php
use Carbon\Carbon;

// envの設定を取得
function env(string $string): string
{
  return $_ENV[$string];
}

//値が入力されているかチェック
function isRequireError(array $datas): bool
{
  foreach ($datas as $data) {
    if (empty($data)) {
        return true;
    }
  }

  return false;
}

//デバッグ用関数
function printLog($value)
{
  $jsonData = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

  return error_log($jsonData, 3, env('LOG_PATH'));
}

function printPre($value): void
{
  echo '<pre>';
  print_r($value);
  echo '</pre>';
}

//クロスドメイン対策用関数
function setCrossOriginHeaders(): void
{
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: POST");
  header("Access-Control-Allow-Headers: Content-Type");
}

//データベースへ接続
function dbConnect(
  string $host,
  string $dbName,
  string $userName='',
  string $password=''
): PDO
{
  $dsn = 'mysql:host='. $host. '; dbname='. $dbName. '; charset=utf8';
  $dbh = new PDO($dsn, $userName, $password);
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  return $dbh;
}

//ファイルのアップロード
function fileUpload(string $uploadDir, array $files)
{
  foreach ($files['name'] as $idx => $name) {
    $uploadedFile = $uploadDir . basename($name);

    if (move_uploaded_file($files['tmp_name'][$idx], $uploadedFile)) {
        printLog('ファイルのアップロードに成功しました'. $name);
    } else {
        printLog('ファイルのアップロードに失敗しました');
    }
  }
}

//ファイルから必要な情報を抽出
function parseLogFiles(string $uploadDir): array
{
  $files = getFiles($uploadDir);
  $results = [];
  foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) !== 'log') {
      printLog('拡張子が「log」でないファイルです。');
      continue;
    }

    $contents = getLogContents($uploadDir. $file);
    $results[] = formatLogContents($contents);
  }


  return $results;
}

//フォルダー内のファイルの一覧を取得
function getFiles(string $uploadDir)
{
  return array_values(array_diff(scandir($uploadDir), array('.', '..') ));
}

//ログファイルの中身を配列に変換して出力
function getLogContents(string $dir): array
{
  $contents = file_get_contents($dir);
  return explode("\n", $contents);
}

//ログファイルの中身を整形して出力
function formatLogContents(array $contents): array
{
  if (empty($contents)) {
    return [];
  }

  $results = [];
  foreach ($contents as $key => $line) {

    $parts = explode('"', $line);
    $address = getAddressLog($parts[0]);
    $date = getDateLog($parts[0]);
    $requestMethod = getRequestMethodLog($parts[1]);
    // printLog($requestMethod);
    // $target_page = $method_and_target[1];
    // $type = substr($parts[5], 1, strpos($parts[5], '"') - 1);

    // $results[] = [
    //     'address' => $address,
    //     'date' => $date,
    //     'method' => $method,
    //     'target_page' => $target_page,
    //     'type' => $type
    // ];

  }

  return $results;
}

//addressを取得
function getAddressLog(string $str): string
{
  return strtok($str, ' ');
}

//dateを取得
function getDateLog(string $str): string
{
  $startPos = strpos($str, '[');
  $endPos = strpos($str, ']');

  if ($startPos === false || $endPos === false) {
    return '';
  }
  $date = substr($str, $startPos + 1, $endPos - $startPos - 1);
  $carbonDate = Carbon::createFromFormat('d/M/Y:H:i:s O', $date);
  $formattedDate = $carbonDate->format('Y-m-d H:i:s');

  return $formattedDate;
}

//requestMethodを取得
function getRequestMethodLog(string $str): string
{
  return strtok($str, ' ');
}
