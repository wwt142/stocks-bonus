<?php

namespace App;

require_once __DIR__ . '/Bootstrap.php';

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Swlib\Saber;
use Swoole\Runtime;
use Models\Stock as StockModel;

class Stock
{
    CONST STOCK_SEARCH_HOST = 'xueqiu.com';

    CONST STOCK_SEARCH_BASE_URL = 'https://xueqiu.com';

    protected $stocks = [];

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

    /**
     * @var Saber
     */
    private $client;

    public function __construct()
    {
        Runtime::enableCoroutine();
    }

    public function run()
    {
        go(function () {
            $this->client = Saber::session([
                'base_uri' => self::STOCK_SEARCH_BASE_URL,
                'redirect' => 0,
                'headers'  => $this->headers,
                'use_pool' => true
            ]);
            $this->getStockList(20);
            saber_pool_release();
        });
    }

    /**
     *
     */
    public function initCookie()
    {
        $this->client->get('/');
    }

    /**
     * @return float
     * @throws \Exception
     */
    public function initTotalPage()
    {
        $time = time();
        $res = $this->client->get('/stock/screener/screen.json?category=SH&exchange=&areacode=&indcode=&orderby=mc&order=desc&current=ALL&pct=ALL&page=1&mc=ALL&volume=ALL&_=' . $time);
        $count = optional(json_decode($res->body))->count;
        if ($count <= 0) {
            throw new \Exception('initTotalPage error');
        }
        $this->totalPage = ceil($count / $this->pageSize);
        return $this->totalPage;
    }

    /**
     * @param int $c
     * @throws \Exception
     */
    public function getStockList(int $c = 10)
    {
        $this->initCookie();
        $this->initTotalPage();
        $j = ceil($this->totalPage / $c);
        $page = 1;
        for ($i = 1; $i <= $j; $i++) {
            $requests = [];
            for ($k = 1; $k <= $c; $k++) {
                if ($this->totalPage < $page) {
                    break;
                }
                $requests[] = [
                    'uri' => '/stock/screener/screen.json?category=SH&exchange=&areacode=&indcode=&orderby=symbol&order=desc&current=ALL&pct=ALL&page=' . $page . '&mc=ALL&volume=ALL&dy=ALL&pb=ALL&pettm=ALL&current=ALL&bps=ALL&roediluted=ALL&_=' . time()
                ];
                $page++;
            }
            $results = $this->client->requests($requests);
            foreach ($results as $result) {
                $data = optional(json_decode($result->getBody()));
                if (!is_null($data)) {
                    if ($data->count > 0) {
                        $this->update($data->list);
                    }
                }
            }
        }
    }

    public function update(array $list)
    {
        foreach ($list as $item) {
            $code = (string)str_replace([
                'SZ',
                'SH',
            ], '', $item->symbol);
            /**
             * @var $stock Model
             */
            $roe = collect($item->roediluted)->sortKeysDesc()->first(null, 0.00);
            $stock = StockModel::where('code', $code)->first();
            $data = [
                'name'                 => $item->name,
                'price'                => $item->current,
                'pb'                   => $item->pb,
                'pe'                   => $item->pettm,
                'mc'                   => $item->mc,
                'roe'                  => $roe,
                'dy'                   => $item->dy,
                'code'                 => $code,
                'dividend_money_total' => \Models\Bonus::where('stock_code', $code)->sum('dividend_money'),
            ];
            if ($stock) {
                $stock->fill($data);
                $stock->save();
            } else {
                StockModel::create($data);
            }
            echo $item->name . "\n";
        }
    }
}

(new Stock())->run();
