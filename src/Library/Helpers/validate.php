<?php

if (!function_exists('sc_check_view') && !in_array('sc_check_view', config('helper_except', []))) {
    /**
     * Check view exist
     *
     * @param   [string]  $view path view
     *
     * @return  [string]         [$domain]
     */
    function sc_check_view($view)
    {
        if (!view()->exists($view)) {
            sc_report('View not found '.$view);
            echo  sc_language_render('front.view_not_exist', ['view' => $view]);
            exit();
        }
    }
}


if (!function_exists('sc_clean') && !in_array('sc_clean', config('helper_except', []))) {
    /**
     * Clear data
     */
    function sc_clean($data = null, $exclude = [], $level_hight = false)
    {
        if ($level_hight) {
            if (is_array($data)) {
                $data = array_map(function ($v) {
                    return strip_tags($v);
                }, $data);
            } else {
                $data = strip_tags($data);
            }
        }
        if (is_array($data)) {
            array_walk($data, function (&$v, $k) use ($exclude, $level_hight) {
                if (is_array($v)) {
                    $v = sc_clean($v, $exclude, $level_hight);
                } else {
                    if ((is_array($exclude) && in_array($k, $exclude)) || (!is_array($exclude) && $k == $exclude)) {
                        $v = $v;
                    } else {
                        $v = htmlspecialchars_decode($v);
                        $v = htmlspecialchars($v, ENT_COMPAT, 'UTF-8');
                    }
                }
            });
        } else {
            $data = htmlspecialchars_decode($data);
            $data = htmlspecialchars($data, ENT_COMPAT, 'UTF-8');
        }
        return $data;
    }
}
