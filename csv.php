<?php
$output = '"NO.","ユーザー名","金額","投稿日時","メッセージ"' . "\n";
$data = json_decode(file_get_contents('php://input', true));
$number = 1;

if (count((array)$data[0]) !== 1) {
    foreach ($data as $value) {
        $output .= '"' . $number . '"';
        foreach ($value as $key => $val) {
            $output .= ',"' . $val . '"';
        }
        if (count((array)$data) !== $number) {
            $output .= "\n";
        }
        $number++;
    }
}

if (count((array)$data[0]) === 1) {
    $output .= '"' . $number . '"';
    foreach ($data as $key => $value) {
        $output .= ',"' . $value . '"';
    }
}

header('Content-Type: application/octet-stream');

echo $output;
