<?php
namespace SCart\Core\Admin\Controllers;

use App\Http\Controllers\RootAdminController;
use SCart\Core\Front\Models\ShopApiConnection;
use SCart\Core\Front\Models\Languages;
use SCart\Core\Front\Models\ShopLanguage;
use Validator;

class AdminLanguageManagerController extends RootAdminController
{
    public function __construct()
    {
        parent::__construct();
    }
    public function index()
    {
        $lang = request('lang');
        $position = request('position');
        $languages = ShopLanguage::getListAll();
        $positionLang = Languages::getPosition();
        $languagesPosition = Languages::getLanguagesPosition($lang, $position);
        
        $codeLanguages = ShopLanguage::getCodeAll();
        if (!in_array($lang, array_keys($codeLanguages))) {
            $languagesPositionEL =   [];
        } else {
            $languagesPositionEL = Languages::getLanguagesPosition('en', $position);
        }
        $arrayKeyLanguagesPosition = array_keys($languagesPosition);
        $arrayKeyLanguagesPositionEL = array_keys($languagesPositionEL);
        $arrayKeyDiff = array_diff($arrayKeyLanguagesPositionEL, $arrayKeyLanguagesPosition);
        $urlUpdateData = sc_route_admin('admin_language_manager.update');
        $data = [
            'languages' => $languages,
            'lang' => $lang,
            'positionLang' => $positionLang,
            'position' => $position,
            'languagesPosition' => $languagesPosition,
            'languagesPositionEL' => $languagesPositionEL,
            'arrayKeyDiff' => $arrayKeyDiff,
            'urlUpdateData' => $urlUpdateData,
            'title' => sc_language_render('admin.language_manager.title'),
            'subTitle' => '',
            'icon' => 'fa fa-indent',
            'removeList' => 0, // 1 - Enable function delete list item
            'buttonRefresh' => 0, // 1 - Enable button refresh
            'buttonSort' => 0, // 1 - Enable button sort
            'css' => '', 
            'js' => '',
            'layout' => 'index',
        ];


        return view($this->templatePathAdmin.'screen.language_manager')
            ->with($data);
    }

    /**
     * Update data
     *
     * @return void
     */
    public function postUpdate() {
        if (!request()->ajax()) {
            return response()->json(['error' => 1, 'msg' => sc_language_render('admin.method_not_allow')]);
        } else {
            $data = request()->all();
            $lang = sc_clean($data['lang']);
            $name = sc_clean($data['name']);
            $value = sc_clean($data['value']);
            $position = sc_clean($data['position']);

            $languages = ShopLanguage::getCodeAll();
            if (!in_array($lang, array_keys($languages))) {
               return response()->json(['error' => 1, 'msg' => sc_language_render('admin.method_not_allow')]);
            }
            Languages::updateOrCreate(
                ['location' => $lang, 'code' => $name],
                ['text' => $value, 'position' => $position],
            );
            return response()->json(['error' => 0, 'msg' => sc_language_render('action.update_success')]);
        }
    }



}
