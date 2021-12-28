<?php
namespace SCart\Core\Front\Models;

use SCart\Core\Front\Models\ShopCurrency;
use Illuminate\Database\Eloquent\Model;

class ShopOrderTotal extends Model
{
    protected $table = SC_DB_PREFIX.'shop_order_total';
    protected $fillable = ['order_id', 'title', 'code', 'value', 'text', 'sort'];
    protected $connection = SC_CONNECTION;
    protected $guarded = [];
    const POSITION_SUBTOTAL = 1;
    const POSITION_TAX = 2;
    const POSITION_SHIPPING_METHOD = 10;
    const POSITION_TOTAL_METHOD = 20;
    const POSITION_OTHER_FEE = 80;
    const POSITION_TOTAL = 100;
    const POSITION_RECEIVED = 200;
    const NOT_YET_PAY = 0;
    const PART_PAY = 1;
    const PAID = 2;
    const NEED_REFUND = 3;

    /**
     * Get object total for order
     * Step 1
     */
    public static function getObjectOrderTotal()
    {
        $objects = array();
        $objects[] = self::getShippingMethod();
        foreach (self::getTotal() as  $totalMethod) {
            $objects[] = $totalMethod;
        }
        $objects[] = self::getOtherFee();
        $objects[] = self::getReceived();
        return $objects;
    }
    
    /**
     * Process data order total
     * @param  array      $objects  [description]
     * Step 2
     * @return [array]    order total after process
     */
    public static function processDataTotal(array $objects = [])
    {
        $carts  = ShopCurrency::sumCartCheckout();
        $subtotal = $carts['subTotal'];
        $tax = $carts['subTotalWithTax'] - $carts['subTotal'];

        //Set subtotal
        $arraySubtotal = [
            'title' => sc_language_render('order.totals.sub_total'),
            'code' => 'subtotal',
            'value' => $subtotal,
            'text' => sc_currency_render_symbol($subtotal),
            'sort' => self::POSITION_SUBTOTAL,
        ];

        //Set tax
        $arrayTax = [
            'title' => sc_language_render('order.totals.tax'),
            'code' => 'tax',
            'value' => $tax,
            'text' => sc_currency_render_symbol($tax),
            'sort' => self::POSITION_TAX,
        ];

        // set total value
        $total = $subtotal + $tax;
        foreach ($objects as $key => $object) {
            if (is_array($object) && $object) {
                if ($object['code'] != 'received') {
                    $total += $object['value'];
                }
            } else {
                unset($objects[$key]);
            }
        }

        $arrayTotal = array(
            'title' => sc_language_render('order.totals.total'),
            'code' => 'total',
            'value' => $total,
            'text' => sc_currency_render_symbol($total),
            'sort' => self::POSITION_TOTAL,
        );
        //End total value

        $objects[] = $arraySubtotal;
        $objects[] = $arrayTax;
        $objects[] = $arrayTotal;

        //re-sort item total
        usort($objects, function ($a, $b) {
            if ($a['sort'] > $b['sort']) {
                return 1;
            } else {
                return -1;
            }
        });

        return $objects;
    }

    /**
     * Get sum value in order total
     * @param  string $code      [description]
     * @param  array $dataTotal [description]
     * @return int            [description]
     */
    public function sumValueTotal($code, $dataTotal)
    {
        $keys = array_keys(array_column($dataTotal, 'code'), $code);
        $value = 0;
        foreach ($keys as $object) {
            $value += $dataTotal[$object]['value'];
        }
        return $value;
    }

    /**
     * Get shipping method
     */
    public static function getShippingMethod()
    {
        $arrShipping = [];
        $shippingMethod = session('shippingMethod') ?? '';
        if ($shippingMethod) {
            $moduleClass = sc_get_class_plugin_config('Shipping', $shippingMethod);
            $returnModuleShipping = (new $moduleClass)->getData();
            $arrShipping = [
                'title' => $returnModuleShipping['title'],
                'code' => 'shipping',
                'value' => sc_currency_value($returnModuleShipping['value']),
                'text' => sc_currency_render($returnModuleShipping['value']),
                'sort' => self::POSITION_SHIPPING_METHOD,
            ];
        }
        return $arrShipping;
    }

    /**
     * Get payment method
     */
    public static function getPaymentMethod()
    {
        $arrPayment = [];
        $paymentMethod = session('paymentMethod') ?? '';
        if ($paymentMethod) {
            $moduleClass = sc_get_class_plugin_config('Paypal', $paymentMethod);
            $returnModulePayment = (new $moduleClass)->getData();
            $arrPayment = [
                'title' => $returnModulePayment['title'],
                'method' => $paymentMethod,
            ];
        }
        return $arrPayment;
    }

    /**
     * Get total method
     */
    public static function getTotal()
    {
        $totalMethod = [];

        $totalMethod = session('totalMethod', []);
        if ($totalMethod && is_array($totalMethod)) {
            foreach ($totalMethod as $keyMethod => $valueMethod) {
                $classTotalConfig = sc_get_class_plugin_config('Total', $keyMethod);
                $returnModuleTotal = (new $classTotalConfig)->getData();
                $totalMethod[] = [
                    'title' => $returnModuleTotal['title'],
                    'code' => 'discount',
                    'value' => $returnModuleTotal['value'],
                    'text' => sc_currency_render_symbol($returnModuleTotal['value']),
                    'sort' => self::POSITION_TOTAL_METHOD,
                ];
            }
        }
        if (!count($totalMethod)) {
            $totalMethod[] = array(
                'title' => sc_language_render('order.totals.discount'),
                'code' => 'discount',
                'value' => 0,
                'text' => 0,
                'sort' => self::POSITION_TOTAL_METHOD,
            );
        }
        return $totalMethod;
    }

    /**
     * Get received value
     */
    public static function getReceived()
    {
        return array(
            'title' => sc_language_render('order.totals.received'),
            'code' => 'received',
            'value' => 0,
            'text' => 0,
            'sort' => self::POSITION_RECEIVED,
        );
    }

    /**
     * Get other fee value
     */
    public static function getOtherFee()
    {
        sc_oder_process_other_fee();

        $otherFee = config('cart.process.other_fee.value');
        $otherFeeTitle = config('cart.process.other_fee.title');
        return array(
            'title' => $otherFeeTitle,
            'code' => 'other_fee',
            'value' => $otherFee,
            'text' => sc_currency_render_symbol($otherFee),
            'sort' => self::POSITION_OTHER_FEE,
        );
    }
}
