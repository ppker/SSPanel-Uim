<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Invoice;
use App\Models\Paylist;
use App\Services\Payment;
use App\Utils\Tools;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use voku\helper\AntiXSS;

final class InvoiceController extends BaseController
{
    public static array $details = [
        'field' => [
            'op' => '操作',
            'id' => '账单ID',
            'order_id' => '订单ID',
            'price' => '账单金额',
            'status' => '账单状态',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'pay_time' => '支付时间',
        ],
    ];

    public function invoice(ServerRequest $request, Response $response, array $args)
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('user/invoice/index.tpl')
        );
    }

    public function detail(ServerRequest $request, Response $response, array $args)
    {
        $antiXss = new AntiXSS();
        $id = $antiXss->xss_clean($args['id']);

        $invoice = Invoice::where('user_id', $this->user->id)->where('id', $id)->first();

        if ($invoice === null) {
            return $response->withRedirect('/user/invoice');
        }

        $paylist = [];

        if ($invoice->status === 'paid_gateway') {
            $paylist = Paylist::where('invoice_id', $invoice->id)->where('status', 1)->first();
        }

        $invoice->status_text = Tools::getInvoiceStatus($invoice);
        $invoice->create_time = Tools::toDateTime($invoice->create_time);
        $invoice->update_time = Tools::toDateTime($invoice->update_time);
        $invoice->pay_time = Tools::toDateTime($invoice->pay_time);
        $invoice_content = \json_decode($invoice->content);

        return $response->write(
            $this->view()
                ->assign('invoice', $invoice)
                ->assign('invoice_content', $invoice_content)
                ->assign('paylist', $paylist)
                ->assign('payments', Payment::getPaymentsEnabled())
                ->fetch('user/invoice/view.tpl')
        );
    }

    public function payBalance(ServerRequest $request, Response $response, array $args)
    {
        $antiXss = new AntiXSS();
        $invoice_id = $antiXss->xss_clean($request->getParam('invoice_id'));

        $invoice = Invoice::where("user_id", $this->user->id)->where("id", $invoice_id)->first();

        if ($invoice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '账单不存在',
            ]);
        }

        $user = $this->user;

        if ($user->money < $invoice->price) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '余额不足',
            ]);
        }

        $user->money -= $invoice->price;
        $user->save();

        $invoice->status = 'paid_balance';
        $invoice->update_time = \time();
        $invoice->pay_time = \time();
        $invoice->save();

        return $response->withJson([
            'ret' => 1,
            'msg' => '支付成功',
        ]);
    }

    public function ajax(ServerRequest $request, Response $response, array $args)
    {
        $invoices = Invoice::orderBy('id', 'desc')->get();

        foreach ($invoices as $invoice) {
            $invoice->op = '<a class="btn btn-blue" href="/user/invoice/' . $invoice->id . '/view">查看</a>';
            $invoice->status = Tools::getInvoiceStatus($invoice);
            $invoice->create_time = Tools::toDateTime($invoice->create_time);
            $invoice->update_time = Tools::toDateTime($invoice->update_time);
            $invoice->pay_time = Tools::toDateTime($invoice->pay_time);
        }

        return $response->withJson([
            'invoices' => $invoices,
        ]);
    }
}
