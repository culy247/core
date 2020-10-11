<?php
#S-Cart/Core/Front/Models/ShopProductGroup.php
namespace S-Cart\Core\Front\Models;

use Illuminate\Database\Eloquent\Model;
use S-Cart\Core\Front\Models\ShopProduct;

class ShopProductGroup extends Model
{
    protected $primaryKey = ['group_id', 'product_id'];
    public $incrementing  = false;
    protected $guarded    = [];
    public $timestamps    = false;
    public $table = SC_DB_PREFIX.'shop_product_group';
    protected $connection = SC_CONNECTION;

    public function product()
    {
        return $this->belongsTo(ShopProduct::class, 'product_id', 'id');
    }
}
