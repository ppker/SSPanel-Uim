<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Setting;

use App\Controllers\BaseController;
use App\Models\Setting;
use Exception;
use function json_encode;

final class CaptchaController extends BaseController
{
    public static array $update_field = [
        'captcha_provider',
        'enable_reg_captcha',
        'enable_login_captcha',
        'enable_checkin_captcha',
        'enable_reset_password_captcha',
        // Turnstile
        'turnstile_sitekey',
        'turnstile_secret',
        // Geetest
        'geetest_id',
        'geetest_key',
    ];

    /**
     * @throws Exception
     */
    public function captcha($request, $response, $args)
    {
        $settings = [];
        $settings_raw = Setting::get(['item', 'value', 'type']);

        foreach ($settings_raw as $setting) {
            if ($setting->type === 'bool') {
                $settings[$setting->item] = (bool) $setting->value;
            } else {
                $settings[$setting->item] = (string) $setting->value;
            }
        }

        return $response->write(
            $this->view()
                ->assign('update_field', self::$update_field)
                ->assign('settings', $settings)
                ->fetch('admin/setting/captcha.tpl')
        );
    }

    public function saveCaptcha($request, $response, $args)
    {
        $list = self::$update_field;

        foreach ($list as $item) {
            $setting = Setting::where('item', '=', $item)->first();

            if ($setting->type === 'array') {
                $setting->value = json_encode($request->getParam($item));
            } else {
                $setting->value = $request->getParam($item);
            }

            if (! $setting->save()) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => "保存 {$item} 时出错",
                ]);
            }
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => '保存成功',
        ]);
    }
}
