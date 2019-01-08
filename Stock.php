<?php

namespace App;

require_once __DIR__ . '/vendor/autoload.php';

use Swoole\Coroutine\Http\Client;
use Swoole\Runtime;

class Stock
{
    CONST STOCK_SEARCH_HOST = 'xueqiu.com';

    private $cookies = [];

    private $totalPage = 0;

    private $pageSize = 30;

    private $headers = [
        'Content-Type'    => 'text/plain; charset=gb2312',
        'host'            => self::STOCK_SEARCH_HOST,
        'Referer'         => 'http://vip.stock.finance.sina.com.cn/quotes_service/api/json_v2.php/Market_Center.getHQNodeData?page=1&num=40&sort=amount&asc=0&node=hs_a&symbol=&_s_r_a=page',
        'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36',
        'Accept'          => 'text/html,application/xhtml+xml,application/xml',
        'Accept-Encoding' => 'gzip',
    ];

    public function __construct()
    {
        Runtime::enableCoroutine();

        (new Database())->connect();
    }

    public function run()
    {
        go(function () {
            $result = $this->getStockList(10);
        });
    }

    /**
     *
     */
    public function initCookie()
    {
        if (!$this->cookies) {
            $client = new Client(self::STOCK_SEARCH_HOST, 443, true);
            $client->get('/');
            $this->cookies = $client->cookies;
            $client->close();

        }

        return $this->cookies;
    }

    /**
     * @return float
     * @throws \Exception
     */
    public function initTotalPage()
    {
        $client = new Client(self::STOCK_SEARCH_HOST, 443, true);
        $client->setHeaders($this->headers);
        $client->setCookies($this->getReqCookie());
        $client->set(['timeout' => 3]);
        $time = time();
        $client->get('/stock/screener/screen.json?category=SH&exchange=&areacode=&indcode=&orderby=mc&order=desc&current=ALL&pct=ALL&page=1&mc=ALL&volume=ALL&_=' . $time);
        $count = optional(json_decode($client->body))->count;
        if ($count <= 0) {
            throw new \Exception('initTotalPage error');
        }
        $this->totalPage = ceil($count / $this->pageSize);
        return $this->totalPage;
    }

    public function getReqCookie()
    {
        return [
            'xq_a_token' => array_get($this->cookies, 'xq_a_token', ''),
            'xq_r_token' => array_get($this->cookies, 'xq_r_token', ''),
            'device_id'  => array_get($this->cookies, 'device_id', ''),
        ];
    }


    /**
     * @param int $c
     * @return array
     * @throws \Exception
     */
    public function getStockList($c = 10)
    {
        $this->initCookie();
        $this->initTotalPage();
        $j = ceil($this->totalPage / $c);
        $page = 1;
        for ($i = 1; $i <= $j; $i++) {
            $clients = [];
            for ($k = 1; $k <= $c; $k++) {
                $client = new Client(self::STOCK_SEARCH_HOST, 443, true);
                $client->setHeaders($this->headers);
                $client->setCookies($this->getReqCookie());
                $client->set(['timeout' => 10]);
                $client->setDefer();
                $time = time();
                $client->get('/stock/screener/screen.json?category=SH&exchange=&areacode=&indcode=&orderby=symbol&order=desc&current=ALL&pct=ALL&page=' . $page . '&mc=ALL&volume=ALL&_=' . $time);
                $page++;
                $clients[] = $client;
            }
            $result = [];
            foreach ($clients as $key => $client) {
                $clients[$key]->recv();
                $result[] = $clients[$key]->body;
                collect($result)->each(function ($item) {
                    $data = optional(json_decode($item));
                    if (!is_null($data)) {
                        if ($data->count > 0) {
                            collect($data->list)->each(function ($item) {
                                $code = (string)str_replace([
                                    'SZ',
                                    'SH',
                                ], '', $item->symbol);
                                echo $item->name . "\n";
                                \Models\Stock::firstOrCreate([
                                    'code' => $code
                                ], [
                                    'name' => $item->name,
                                ]);
                            });
                        }
                    } else {
                        var_dump($data);
                    }
                });
            }
        }
    }
}

(new Stock())->run();


