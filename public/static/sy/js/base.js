$(function () {
    $('#login').on('click', function () {
        var user_name = $('#user_name').val();
        var password = $('#password').val();
        var $btn = $(this);
        if (!user_name) {
            layer.alert('请输入用户名');
            return;
        }
        if (!password) {
            layer.alert('请输入密码');
            return;
        }
        // $btn.button('loading');
        $$.ajax('/user/login', {
            user_name: user_name,
            password: password
        }).then(res => {
            $btn.button('reset');
            layer.msg(res.msg);
            window.location.href = '/webApp/index';
        })
    });
    $('#register').on('click', function () {
        var user_name = $('#user_name').val();
        var password = $('#password').val();
        var confirm_password = $('#password-confirm').val();
        var $btn = $(this);
        if (!user_name) {
            layer.alert('请输入用户名');
            return;
        }
        if (!password) {
            layer.alert('请输入密码');
            return;
        }
        if (password != confirm_password) {
            layer.alert('两次输入的密码不一致，请重新输入');
            return;
        }
        // $btn.button('loading');
        $$.ajax('/user/register', {
            user_name: user_name,
            password: password
        }).then(res => {
            $btn.button('reset');
            layer.msg(res.msg);
            window.location.href = '/index/index';
        })
    });
});

function Logout() {
    window.location.href = '/user/logout'
}

// $(function () {
//     $(".help_icon[data-toggle='tooltip']").tooltip();
// });