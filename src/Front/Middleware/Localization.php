<?php

namespace S-Cart\Core\Front\Middleware;

use S-Cart\Core\Front\Models\ShopLanguage;
use Closure;
use Session;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
//Set language
        $languages = ShopLanguage::getListActive();
        $requestLocale = $request->get('lang');
        if ($requestLocale) {
            $detectLocale = $requestLocale;
        } else
        if (!Session::has('locale')) {
            $detectLocale = sc_store('language') ?? config('app.locale');
        } else {
            $detectLocale = session('locale');
        }
        $currentLocale = array_key_exists($detectLocale, $languages->toArray()) ? $detectLocale : $languages->first()->code;
        session(['locale' => $currentLocale, 'locale_id' => $languages[$currentLocale]['id']]);
        app()->setLocale($currentLocale);
//End language
        return $next($request);
    }
}
