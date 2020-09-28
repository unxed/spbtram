<?php

$d = file_get_contents('https://transphoto.org/show.php?t=1&cid=2');
$doc = new DOMDocument();
$doc->loadHTML($d);

$xpath = new DOMXpath($doc);
$el = $xpath->query('/html/body/table/tr[3]/td/center/div[4]/table')->item(0);

$trams = array();
foreach ($el->childNodes as $node) {
    $count = 0;
    $tram = array();
    if ($node->hasChildNodes()) {
        foreach ($node->childNodes as $node2) {
            if ($node2->nodeName == 'td') {
                if ($node2->nodeValue == 'Всего') {
                    // last row should be skipped
                    break;
                }
                $count++;
                $tram[$count] = $node2->nodeValue;
            }
        }
    }
    if ($tram) { $trams[] = $tram; }
} 

$low_floor = 0;
$high_floor = 0;
foreach ($trams as $tram) {
    $count = $tram[2];
    $count = explode(' / ', $count);
    $count = $count[0];
    if (
        ($tram[1] == 'ПР (18М)')
       )
    {
        // serivce
    } else if (
        ($tram[1] == 'ЛВС-86К') ||
        ($tram[1] == 'ЛВС-86М2') ||
        ($tram[1] == 'ЛВС-86К-М') ||
        ($tram[1] == '71-134К (ЛМ-99К)') ||
        ($tram[1] == '71-134А (ЛМ-99АВ)') ||
        ($tram[1] == 'ЛМ-68М') ||
        ($tram[1] == '71-88Г (23М0000)') ||
        ($tram[1] == '71-147К (ЛВС-97К)') ||
        ($tram[1] == '71-147А (ЛВС-97А)')
       )
    {
        $high_floor += $count;
    } else {
        $low_floor += $count;
    }
}

$now = date('d.m.Y');

// test
// $high_floor = 0;

if (($high_floor == 0) || ($low_floor == 0)) {
    $high_floor = 469;
    $low_floor = 314;
    $now = '<span style="color: red;">28.09.2020 ' .
        '(обновление сломалось, напишите <a href=https://t.me/unxed>админу</a>)</span>';
} else {

    $log = file('log.csv');
    $exists = false;
    foreach ($log as $line) {
        $parts = explode(',', $line);
        $exists = $exists || ($parts[0] == date('m.Y'));
    }
    if (!$exists) {
        $fp = fopen('log.csv', 'a');
        fwrite($fp, date('m.Y') . ',' . $high_floor . ',' . $low_floor . "\n");
        fclose($fp);            
    }
}

$percent = intval($low_floor / ($high_floor + $low_floor) * 100);

echo '
<html>
    <head>
        <title>Трамваи СПб</title>
        <style>
            * { font-family: Sans-Serif; }
            h1 { font-size: 48pt; }
            h3 { font-size: 14pt; }
        </style>
    </head>
    <body>
        <center>
            <h1>' . $high_floor . ' : ' . $low_floor . '</h1>
            <h3>[трамваев в СПб с высоким полом : трамваев в СПб с низким полом]</h3>
            <h3>вероятность встретить трамвай с низким полом — ' . $percent . '%</h3>
            <h3>по <a href=https://transphoto.org/show.php?t=1&cid=2>данным</a> на ' . $now . '</h3>
            <br/><br/><br/><br/>
            <small>
                Полностью высокопольными считаются ЛВС-86 и ЛВС-97 (во всех модификациях),<br/>
                ЛМ-99 (кроме модификации АВН) и ЛВС-68 (кроме модификаций М2 и М3).<br/>
                Служебный ПР (18М) не учитывается.<br/>
                Остальные модели считаются [хотя бы частично] низкопольными.
                <a href=https://t.me/unxed>Обратная связь</a>.
            </small>
        </center>
    </body>
</html>
';

