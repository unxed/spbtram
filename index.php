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
    $count = $count[1]; // считаем только действующий ПС
    if (
        ($tram[1] == 'ПР (18М)')               // на transphoto перенесен в служебные
       )
    {
        // serivce
    } else if (
        ($tram[1] == 'ЛМ-68М')            ||   // на transphoto перенесен в служебные
        ($tram[1] == 'ТС-77')             ||   // он же ЛМ-68МЧ
        ($tram[1] == 'ЛВС-86К')           ||
        ($tram[1] == 'ЛВС-86М2')          ||
        ($tram[1] == 'ЛВС-86К-М')         ||
        ($tram[1] == '71-147К (ЛВС-97К)') ||
        ($tram[1] == '71-147А (ЛВС-97А)') ||
        ($tram[1] == '71-134К (ЛМ-99К)')  ||
        ($tram[1] == '71-134А (ЛМ-99АВ)') ||
        ($tram[1] == '71-88Г (23М0000)')       // на transphoto перенесен в служебные
       )
    {
        $high_floor += $count;
    } else {
        $low_floor += $count;
    }

    // get canonical model name
    $tkk = false;
    if (strpos($tram[1], '71-931М') === 0) {
        $name = preg_replace('/(71-931М).*/', '$1', $tram[1]);
    } else if (strpos($tram[1], '71-') === 0) {
        $name = preg_replace('/((\d+)-(\d+)).*/', '$1', $tram[1]);
    } else if (strpos($tram[1], 'ЛМ-68М2') === 0) {
        $name = preg_replace('/(ЛМ-68М2).*/', '$1', $tram[1]);
    } else if (strpos($tram[1], 'ЛМ-68М3') === 0) {
        $name = $tram[1];
    } else if (strpos($tram[1], 'Л') === 0) {
        $name = preg_replace('/(([ЛВСМ])-(\d+)).*/', '$1', $tram[1]);
    } else if (strpos($tram[1], 'ТС') === 0) {
        $name = 'ЛМ-68МЧ';
    } else if (strpos($tram[1], 'Stadler B85600M') === 0) {
        $tkk = true;
        $name = $tram[1];
    } else if (strpos($tram[1], 'БКМ 84300М') === 0) {
        $name = 'БКМ-84300М';
    } else {
        $name = $tram[1];
    }

    if ($count > 0) {
        if ($tkk) {
            $trams_norm2[$name] += $count;
        } else {
            $trams_norm[$name] += $count;
        }
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
            <br/>
            <small>
                Полностью высокопольными считаются
                ЛМ-68 (кроме модификаций М2 и М3),
                ТС-77 (он же <a href=https://twitter.com/Convoker/status/1298329066043576320>ЛМ-68МЧ</a>),
                <br/>
                ЛВС-86 и ЛВС-97 (во всех модификациях),
                а также ЛМ-99 (кроме модификации АВН).
                <br/>
<!--
                Служебный ПР (18М) не учитывается.<br/>
-->
                Остальные модели считаются [хотя бы частично] низкопольными.
                <a href=https://t.me/unxed>Обратная связь</a>.
            </small>

            <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
            <script type="text/javascript">
              google.charts.load(\'current\', {\'packages\':[\'corechart\']});
              google.charts.setOnLoadCallback(drawChart);

              function drawChart() {
                var data = google.visualization.arrayToDataTable([
                  [\'Месяц.Год\', \'Низкий пол\', \'Высокий пол\'],
';

foreach ($log as $line) {
    $parts = explode(',', $line);
    echo "['" . $parts[0] . "', " . $parts[2] . ", " . $parts[1] . "],";
}

echo '
                ]);

                var options = {
                  title: \'Трамваи в СПб\',
                  curveType: \'function\',
                  legend: { position: \'bottom\' }
                };

                var chart = new google.visualization.LineChart(document.getElementById(\'curve_chart\'));

                chart.draw(data, options);
              }
            </script>

            <div id="curve_chart" style="width: 900px; height: 500px"></div>

        </center>
    </body>
</html>

';

echo "<!--\n\n";
foreach ($trams_norm as $k=>$v) {
    if ($k == '71-134')  { $k = '71-134|ЛМ-99'; }
    if ($k == '71-147')  { $k = '71-147|ЛВС-97'; }
    if ($k == '71-152')  { $k = '71-152|ЛВС-2005'; }
    if ($k == '71-153')  { $k = '71-153|ЛМ-2008'; }
    if ($k == '71-923')  { $k = '71-923|Богатырь'; }
    if ($k == '71-923М') { $k = '71-923|Богатырь-М'; }
    if ($k == '71-931')  { $k = '71-931|Витязь'; }
    if ($k == '71-931М') { $k = '71-931|Витязь-М'; }
    echo "|-\n|[[" . $k . "]]\n|" . $v . "\n";
}
echo "\n\n";
foreach ($trams_norm2 as $k=>$v) {
    echo "|-\n|[[" . $k . "]]\n|" . $v . "\n";
}
echo "\n\n-->";

