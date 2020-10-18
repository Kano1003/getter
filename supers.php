<?php

$received_data = json_decode(file_get_contents('php://input', true));

// 受け取ったデータを整形
foreach ($received_data as $key => $value) {
    // データが複数あるかチェック
    if (count((array)$value) > 1) {
        for ($i = 0; $i < count((array)$value); $i++) {
            if ($key === 'name') {
                ${'group' . $i} = array();
            }
            ${'group' . $i}[$key] = $value[$i];
        }
    } else {
        $i = 1;
        $group0[$key] = $value;
    }
}

// 出力用データを作成
for ($j = 0; $j < $i; $j++) {
    switch (${'group' . $j}['tier']) {
        case 1:
            $color = '#0066CC';
            break;
        case 2:
            $color = '#66FFCC';
            break;
        case 3:
        case 12:
            $color = '#30F9B2';
            break;
        case 4:
            $color = '#FFFF33';
            break;
        case 5:
        case 15:
            $color = '#FF9933';
            break;
        case 6:
            $color = '#FF6666';
            break;
        default:
            $color = '#FF0000';
            break;
    }

    $out = null;
    foreach (${'group' . $j} as $key_out => $value_out) {
        if ($key_out !== 'tier') {
            if (empty($value_out)) {
                $value_out = 'null';
            }
            $out .= 'data-' . $key_out . '="' . $value_out . '"';
            if ($key_out !== 'message') {
                $out .= ' ';
            }
        }
    }

    // 出力
    echo '<div class="box" ' . $out . '>';
    echo '<div class="box-title tier' . ${'group' . $j}['tier'] . '"style="background:' . $color . ';">' . ${'group' . $j}['amount'] . '  ' . ${'group' . $j}['name'] . '</div>';
    echo '<p>' . ${'group' . $j}['message'] . '</p>';
    echo '</div>';
}
