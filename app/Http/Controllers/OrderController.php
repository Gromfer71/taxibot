<?php

namespace App\Http\Controllers;

use App\Models\OrderHistory;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function delete($id)
    {
        $order = OrderHistory::find($id);
        if($order) {
            $order->delete();
        }

        return back()->with('ok', 'Заказ успешно удален');
    }
}
