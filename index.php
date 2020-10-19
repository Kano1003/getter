<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スパチャゲッター</title>
    <link rel="stylesheet" href="style.css" type="text/css">
    <link rel="shortcut icon" type="image/png" href="../favicon.ico">
</head>

<body>
    <div class="wrapper">
        <header>
            <h1>スパチャゲッター</h1>
        </header>
        <div id="contents">
            <div class="announcement-wrapper">
                <p class="top-announcement">使い方</p>
                <p class="announcement">YouTubeライブストリームIDを入力してください。</p>
                <p class="announcement">スーパーチャット、スーパーステッカーの情報が一定時間毎に取得され表示されます。</p>
            </div>
            <div class="form-wrapper">
                <div class="form-block">
                    <form>
                        <input id="textarea" name="streamId" type="text" placeholder="ライブストリームのIDを入力">
                    </form>
                    <button id="btn" onclick="getSupers()">送　信</button>
                </div>
            </div>

            <div id="resultbox">
                <div hidden="hidden"></div>
            </div>
            <div id="connectionbox">
                <div id="anime-wrapper">
                    <div class="loader">Loading...</div>
                </div>
            </div>

        </div>
        <footer></footer>
    </div>
</body>

</html>

<script type="text/javascript">
    // 接続用
    const connect = async (url, inputData) => {
        return fetch(url, {
            method: 'POST',
            headers: {
                "Content-Type": "application/json; charset=utf-8"
            },
            body: JSON.stringify(inputData)
        });
    };

    // APIからデータを取得
    const callAPI = async (callData) => {
        const called = await connect('api.php', callData).then(result => {
            return result.json();
        }).then(json => {
            return json;
        }).catch(error => {
            console.log('error:call error:' + error);
            return null;
        });

        // オブジェクトの処理
        if (called) {
            let callResults = {
                "liveChatId": called.livechat_id,
                "pageToken": called.page_token
            };

            // 取得したデータにスーパーチャットが含まれているかチェック
            if (called.supers.length !== 0 && called.supers.length === undefined) {
                let createSupers = await connect('supers.php', called.supers).then(result => {
                    return result.text();
                }).then(text => {
                    // 取得したスーパーチャットを出力
                    document.getElementById('resultbox').insertAdjacentHTML('beforeend', text);
                }).catch(error => {
                    console.log('error:create error');
                    return null;
                });
            }
            return callResults;
        }
    };

    // 一定間隔でAPIをたたく
    const connectRepeat = async (data) => {
        let sendData = data;
        // 渡されたデータをチェック
        if (sendData.liveChatId === null || sendData.pageToken === null) {
            return null;
        }

        const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));
        // 最初の通信からのクールタイム
        await sleep(5000);

        while (sendData) {
            try {
                sendData = await callAPI(sendData);
                // データを取得出来ているかチェック
                if (sendData.liveChatId === null || sendData.pageToken === null || sendData.liveChatId === undefined || sendData.pageToken === undefined) {
                    throw new Error();
                }
                await sleep(12000);

                // 取得出来ていない場合の処理
            } catch (e) {
                return null;
            }
        }
    };

    // ボタンの状態変化
    const buttonStateChange = (button, command) => {
        if (command === 'change') {
            button.disabled = true;
            button.innerText = '処理中';
            button.style.opacity = '0.5';
            button.style.pointerEvents = 'none';
        }

        if (command === 'reset') {
            button.disabled = false;
            button.innerText = '送　信';
            button.style.opacity = '1';
            button.style.pointerEvents = 'auto';
        }
    };

    // CSVファイル出力用
    const getCsv = () => {
        const supersData = document.querySelectorAll('.box');
        let supersArray = [];
        for (let i = 0; i < supersData.length; i++) {
            supersArray[i] = [supersData[i].dataset.name, supersData[i].dataset.amount, supersData[i].dataset.published, supersData[i].dataset.message];
        }
        console.log(supersArray);
        console.log(JSON.stringify(supersArray));
        fetch('csv.php', {
            method: 'POST',
            headers: {
                "Content-Type": "application/json; charset=utf-8"
            },
            body: JSON.stringify(supersArray)
        }).then(result => {
            return result.blob();
        }).then(blob => {
            console.log(blob);
            console.log(blob.text());
            const downloadData = new Blob([blob], {
                type: blob.type
            });
            let link = document.createElement('a');
            link.style.display = 'none';
            link.href = window.URL.createObjectURL(downloadData);
            link.download = '取得データ.csv';
            link.click();
        }).catch(error => {
            console.log('CSV create error:' + error);
            // エラーメッセージ処理
            if (document.getElementById('error-message')) {
                document.getElementById('error-message').remove();
            }
            document.getElementById('resultbox').insertAdjacentHTML('beforeend', '<p id=error-message>CSVファイルの出力に失敗しました。</p>');
        });
    };

    // 実行用
    const getSupers = async () => {
        // 二重送信防止
        let submitButton = document.getElementById('btn');
        buttonStateChange(submitButton, 'change');

        // 処理中のアニメーションON
        document.getElementById('anime-wrapper').style.display = 'block';

        // エラーメッセージ処理
        if (document.getElementById('error-message')) {
            document.getElementById('error-message').remove();
        }

        // チャンネルIDが入力されているかチェック
        if (!document.getElementById('textarea').value) {
            buttonStateChange(submitButton, 'reset');
            // 処理中のアニメーションOFF
            document.getElementById('anime-wrapper').style.display = 'none';
            return;
        }

        // CSVダウンロード用ボタン処理
        if (document.getElementById('csv-btn')) {
            document.getElementById('csv-btn').remove();
        }

        // 通信用データ作成
        const firstData = {
            "streamId": document.getElementById('textarea').value
        };

        // 接続（初回）
        const first = await callAPI(firstData);

        // 接続（ループ）
        const run = await connectRepeat(first);
        // 通信が終了した場合の処理
        if (run === null) {
            document.getElementById('resultbox').insertAdjacentHTML('beforeend', '<p id=error-message>ライブストリームの終了、または取得エラー。</p>');
            buttonStateChange(submitButton, 'reset');
            // 処理中のアニメーションOFF
            document.getElementById('anime-wrapper').style.display = 'none';

            // 取得データが1つ以上ある場合、CSVファイル出力用ボタンを作成
            if (document.getElementsByClassName('box').length) {
                document.getElementById('resultbox').insertAdjacentHTML('beforeend', '<button id="csv-btn" onclick="getCsv()">CSVファイルをダウンロード</button>');
            }

            return;
        }
    };
</script>