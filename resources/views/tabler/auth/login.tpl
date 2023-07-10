{include file='header.tpl'}

<body class="border-top-wide border-primary d-flex flex-column">
    <div class="page page-center">
        <div class="container-tight my-auto">
            <div class="text-center mb-4">
                <a href="#" class="navbar-brand navbar-brand-autodark">
                    <img src="/images/uim-logo-round_96x96.png" height="64" alt="SSPanel-UIM Logo">
                </a>
            </div>
            <div class="card card-md">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">登录到用户中心</h2>
                    <div class="mb-3">
                        <label class="form-label">注册邮箱</label>
                        <input id="email" type="email" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">
                            登录密码
                            <span class="form-label-description">
                                <a href="/password/reset">忘记密码</a>
                            </span>
                        </label>
                        <div class="input-group input-group-flat">
                            <input id="passwd" type="password" class="form-control" autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">两步认证</label>
                        <input id="code" type="email" class="form-control" placeholder="如果没有设置两步认证可留空">
                    </div>
                    <div class="mb-2">
                        <label class="form-check">
                            <input id="remember_me" type="checkbox" class="form-check-input" />
                            <span class="form-check-label">记住此设备</span>
                        </label>
                    </div>
                    {if $public_setting['enable_login_captcha']}
                        {if $public_setting['captcha_provider'] === 'turnstile'}
                            <div class="mb-3">
                                <div class="input-group mb-3">
                                    <div id="cf-turnstile" class="cf-turnstile" data-sitekey="{$captcha['turnstile_sitekey']}" data-theme="light"></div>
                                </div>
                            </div>
                        {/if}
                        {if $public_setting['captcha_provider'] === 'geetest'}
                            <div class="mb-3">
                                <div class="input-group mb-3">
                                    <div id="geetest"></div>
                                </div>
                            </div>
                        {/if}
                    {/if}
                    <div class="form-footer">
                        <button id="login-dashboard" class="btn btn-primary w-100">登录</button>
                    </div>
                </div>
            </div>
            <div class="text-center text-secondary mt-3">
                还没有账户？ <a href="/auth/register" tabindex="-1">点击注册</a>
            </div>
        </div>
    </div>

    <script>
        $("#login-dashboard").click(function() {
            $.ajax({
                type: 'POST',
                url: '/auth/login',
                dataType: "json",
                data: {
                    code: $('#code').val(),
                    email: $('#email').val(),
                    passwd: $('#passwd').val(),
                    remember_me: $('#remember_me').val(),
                    {if $public_setting['enable_login_captcha']}
                        {if $public_setting['captcha_provider'] === 'turnstile'}
                            turnstile: $('input[name=cf-turnstile-response]').val(),
                        {/if}
                        {if $public_setting['captcha_provider'] === 'geetest'}
                            geetest: geetest_result,
                        {/if}
                    {/if}
                },
                success: function(data) {
                    if (data.ret === 1) {
                        $('#success-message').text(data.msg);
                        $('#success-dialog').modal('show');
                        window.setTimeout(location.href=data.redir, {$config['jump_delay']});
                    } else {
                        $('#fail-message').text(data.msg);
                        $('#fail-dialog').modal('show');
                    }
                }
            })
        });
    </script>

    {if $public_setting['enable_login_captcha']}
        {if $public_setting['captcha_provider'] === 'turnstile'}
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        {/if}
        {if $public_setting['captcha_provider'] === 'geetest'}
            <script src="https://static.geetest.com/v4/gt4.js"></script>
            <script>
                var geetest_result = '';
                initGeetest4({
                    captchaId: '{$captcha['geetest_id']}',
                    product: 'float',
                    language: "zho",
                    riskType:'slide'
                }, function (geetest) {
                    geetest.appendTo("#geetest");
                    geetest.onSuccess(function() {
                        geetest_result = geetest.getValidate();
                    });
                });
            </script>
        {/if}
    {/if}

{include file='footer.tpl'}
