<?php
date_default_timezone_set('Asia/Tokyo');

// 接続用
function curl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $connection_result = curl_exec($ch);
    curl_close($ch);
    return $connection_result;
}

// 変数の初期化
$url = $livechat_id = $page_token = null;
$res = $supers  = array();
$result = array('livechat_id' => $livechat_id, 'page_token' => $page_token, 'supers' => $supers);
$received_data = json_decode(file_get_contents('php://input', true));

// 初期接続かチェック
if (!empty($received_data->{'streamId'}) && empty($received_data->{'liveChatId'}) && empty($received_data->{'pageToken'})) {
    // チャットIDを取得
    $url = 'https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails&id=';
    $url .= $received_data->{'streamId'};
    $url .= '&key={API_KEY}';

    $res = json_decode(curl($url), true);

    // 取得が出来ていない場合の処理
    if (!count($res['items'])) {
        // 出力
        $json = json_encode($result, JSON_UNESCAPED_UNICODE);
        header("Content-Type: application/json; charset=utf-8");
        print($json);
        exit();
    }

    $livechat_id = $res['items'][0]['liveStreamingDetails']['activeLiveChatId'];

    // チャット内容を取得
    $url = 'https://www.googleapis.com/youtube/v3/liveChat/messages?liveChatId=';
    $url .= $livechat_id;
    $url .= '&part=authorDetails,snippet&hl=ja&maxResults=2000&key={API_KEY}';
} else {
    $url = 'https://www.googleapis.com/youtube/v3/liveChat/messages?liveChatId=';
    $url .= $received_data->{'liveChatId'};
    $url .= '&part=authorDetails,snippet&hl=ja&maxResults=2000&key={API_KEY}&pageToken=';
    $url .= $received_data->{'pageToken'};

    $livechat_id = $received_data->{'liveChatId'};
}

$res = json_decode(curl($url), true);

// 接続チェック
if (!isset($res['error'])) {

    $page_token = $res['nextPageToken'];
    for ($i = 0; $i < count($res['items']); $i++) {

        // スーパーチャット処理
        if ($res['items'][$i]['snippet']['type'] === 'superChatEvent') {
            $d = new DateTime($res['items'][$i]['snippet']['publishedAt']);
            if (!empty($res['items'][$i]['snippet']['superChatDetails']['userComment'])) {
                $user_comment = $res['items'][$i]['snippet']['superChatDetails']['userComment'];
            } else {
                $user_comment = null;
            }

            $supers = array_merge_recursive($supers, array(
                'name' => $res['items'][$i]['authorDetails']['displayName'],
                'published' => $d->format('Y-m-d H:i:s u'),
                'amount' => $res['items'][$i]['snippet']['superChatDetails']['amountDisplayString'],
                'message' => $user_comment,
                'tier' => $res['items'][$i]['snippet']['superChatDetails']['tier']
            ));
        }

        // スーパーステッカー処理
        if ($res['items'][$i]['snippet']['type'] === 'superStickerEvent') {
            $D = new DateTime($res['items'][$i]['snippet']['publishedAt']);
            $supers = array_merge_recursive($supers, array(
                'name' => $res['items'][$i]['authorDetails']['displayName'],
                'published' => $D->format('Y-m-d H:i:s u'),
                'amount' => $res['items'][$i]['snippet']['superStickerDetails']['amountDisplayString'],
                'message' => $res['items'][$i]['snippet']['superStickerDetails']['superStickerMetadata']['altText'],
                'tier' => $res['items'][$i]['snippet']['superStickerDetails']['tier']
            ));
        }
    }

    // 出力用データ
    $result['livechat_id'] = $livechat_id;
    $result['page_token'] = $page_token;
    $result['supers'] = $supers;
}

// 出力
$json = json_encode($result, JSON_UNESCAPED_UNICODE);
header("Content-Type: application/json; charset=utf-8");
print($json);
