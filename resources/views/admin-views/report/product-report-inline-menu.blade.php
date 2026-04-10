<div class="inline-page-menu my-4">
    <ul class="list-unstyled">
        <li class="{{ Request::is('admin/report/all-product') ?'active':'' }}"><a href="{{route('admin.report.all-product')}}">{{translate('all_Food')}}</a></li>
        <li class="{{ Request::is('admin/stock/product-stock') ?'active':'' }}"><a href="{{route('admin.stock.product-stock')}}">{{translate('food_Stock')}}</a></li>
        <li class="{{ Request::is('admin/stock/product-in-wishlist') ?'active':'' }}"><a href="{{route('admin.stock.product-in-wishlist')}}">{{translate('wish_Listed_Food')}}</a></li>
    </ul>
</div>
