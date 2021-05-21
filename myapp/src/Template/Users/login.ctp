<?php /*
<div class="users form">
  <?= $this->Flash->render() ?>
  <?= $this->Form->create() ?>
  <fieldset>
    <legend><?= __('ユーザ名とパスワードを入力してください') ?></legend>
    <?= $this->Form->control('username') ?>
    <?= $this->Form->control('password') ?>
  </fieldset>
  <?= $this->Form->button(__('Login')); ?>
  <?= $this->Form->end() ?>
</div>
*/?>

<div class="col-md-6 mx-auto mt-5">
  <!-- /.login-logo -->
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="/" class="h1"><b>LOGIN</b></a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Sign in to start your session</p>

      <?= $this->Form->create() ?>
        <div class="input-group mb-3">
          <?= $this->Form->text('username',[
              "type"=>"text",
              "class"=>"form-control ",
              "placeholder"=>"username",
              "label"=>false,
              "div"=>false
          ]) ?>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <?= $this->Form->password('password',[
              "type"=>"password",
              "class"=>"form-control ",
              "placeholder"=>"password",
              "label"=>false,
              "div"=>false
          ]) ?>

          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="remember">
              <label for="remember">
                Remember Me
              </label>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-4">
            <?= $this->Form->button(__('Login'),[
                "class"=>"btn btn-primary btn-block"
            ]); ?>
          </div>
          <!-- /.col -->
        </div>
        <?= $this->Form->end() ?>



      <!-- /.social-auth-links -->

      <p class="mb-1">
        <a href="forgot-password.html">I forgot my password</a>
      </p>
      <p class="mb-0">
        <a href="register.html" class="text-center">Register a new membership</a>
      </p>
    </div>
    <!-- /.card-body -->
  </div>
  <!-- /.card -->
</div>
<!-- /.login-box -->
