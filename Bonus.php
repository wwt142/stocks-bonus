<?php

namespace App;

require_once __DIR__ . '/Bootstrap.php';

use Models\Stock;
use PHPHtmlParser\Dom;
use Swoole\Coroutine\Http\Client;
use Swoole\Runtime;

class Bonus
{
    const BONUS_HOST = 'basic.10jqka.com.cn';
    const BONUS_URL = 'http://basic.10jqka.com.cn/601009/bonus.html';

    private $headers = [
        'Content-Type'    => 'text/plain; charset=gb2312',
        'host'            => self::BONUS_HOST,
        'Referer'         => 'http://basic.10jqka.com.cn/601009/bonus.html',
        'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36',
        'Accept'          => 'text/html,application/xhtml+xml,application/xml',
        'Accept-Encoding' => 'gzip',
    ];

    public function __construct()
    {
        ini_set('memory_limit', '512M');
        Runtime::enableCoroutine();
    }

    public function run()
    {
        $stocks = Stock::select(['code'])->get()->toArray();


        go(function () use ($stocks) {
            $size = 20;
            $total = count($stocks);
            $totalSize = ceil($total / $size);
            for ($i = 0; $i < $totalSize; $i++) {
                $data = array_slice($stocks, $i * $size, $size);
                $clients = [];
                foreach ($data as $item) {
                    $cli = new Client(self::BONUS_HOST, 80);
                    $cli->setHeaders($this->headers);
                    $cli->get("/{$item['code']}/bonus.html");
                    $cli->setDefer();
                    $clients[$item['code']] = $cli;
                }
                $result = [];
                foreach ($clients as $code => $client) {
                    $clients[$code]->recv();
                    $result[$code] = $clients[$code]->body;
                }
                foreach ($result as $code => $body) {
                    $dom = new Dom();
                    $dom->load($body);
                    $data = $dom->find('#bonus_table tbody tr');
                    $insertData = [];
                    $bonusMoney = [];
                    $bonus = optional($dom->find("#bonusData", 0))->text;
                    if (!empty($bonus)) {
                        $bonusMoney = collect(json_decode($bonus, true))->keyBy('0')->toArray();
                    }
                    foreach ($data as $tbody) {
                        /**
                         * @var $tbody Dom
                         */
                        $res = $tbody->find('td');
                        if (!empty($res)) {
                            $insertData[] = [
                                'stock_code'              => $code,
                                'program_desc'            => $res[4]->text,
                                'report_date'             => $res[0]->text,
                                'meeting_date'            => $res[1]->text,
                                'announcement_date'       => trim($res[2]->text, '--'),
                                'material_date'           => $res[3]->text,
                                'stock_registration_date' => $res[5]->text,
                                'ex_dividend_date'        => $res[6]->text,
                                'programme_progress'      => $res[7]->text,
                                'payout_ratio'            => (float)str_replace([
                                    '%',
                                    '--',
                                ], '', $res[8]->text),
                                'dividend_rate'           => (float)str_replace([
                                    '%',
                                    '--',
                                ], '', $res[9]->text),
                                'dividend_money'          => array_get($bonusMoney, $res[1]->text . '.1', 0.00),
                            ];
                        }
                    }
                    if (!empty($insertData)) {
                        foreach ($insertData as $insertDatum) {
                            if (!\Models\Bonus::where('report_date', $insertDatum['report_date'])->where('stock_code', $code)->first()) {
                                $insertDatum = array_map(function ($item) {
                                    return trim($item, '--');
                                }, $insertDatum);
                                $insertDatum = collect($insertDatum)->filter(function ($item) {
                                    return $item !== '';
                                })->toArray();
                                \Models\Bonus::create($insertDatum);
                            }
                        }
                    }
                }
            }
        });
    }
}

(new Bonus())->run();