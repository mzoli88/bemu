<template>
  <div class="fit padding noselect loginPage hflex">
    <div class="cflex DarkModeLogin">
      <Button
        icon="f042"
        title="Inverz (sötét) mód"
        @click="doDarkMode"
        noHighlight
        noSpin
      />
    </div>
    <div class="MainStatpage loginMainPage hflex fit">
      <div class="fit"></div>
      <div class="logoImageDiv cflex" v-if="config.login_logo">
        <img :src="'storage/' + config.login_logo" />
      </div>
      <div class="login-site-name cflex" v-if="config && config.name">
        {{ config.name }}
      </div>
      <div class="login-system-users cflex">
        {{ config && !empty(config.system_users) ? config.system_users : "" }}
      </div>
      <div class="cflex">
        <Panel
          v-if="page == 'login'"
          title="Bejelentkezés"
          border
          h
          class="startPagePanel"
          padding
          size="auto"
        >
          <Form topLabels noFixWidth>
            <slot name="loginFields" />
            <Field
              autocomplete="username"
              name="login"
              ref="login"
              label="Felhasználónév"
              type="text"
              @onEnter="onLogin"
            />
            <Field
              autocomplete="current-password"
              name="password"
              label="Jelszó"
              type="password"
              @onEnter="onLogin"
            />
            <slot name="extraloginField" />
          </Form>
          <div class="cflex">
            <Button @click="onLogin">Belépés</Button>
          </div>
          <div class="link">
            <a href="#forgottenPassword">Elfelejtett jelszó</a>
          </div>
        </Panel>
        <Panel
          v-if="page == 'forgottenPassword'"
          title="Elfelejtett jelszó"
          border
          h
          class="startPagePanel"
          padding
          size="auto"
        >
          <caption class="message">
            A jelszó cseréjéhez a megadott e-mail címre küldjük a szükséges
            linket.
          </caption>
          <Form store="auth.forgottenpassword" topLabels noFixWidth>
            <slot name="forgottenPasswordFields" />
            <Field
              ref="forgottenpasswordLogin"
              label="Login név"
              type="text"
              autocompleteOn
              name="login"
              @onEnter="onForgottenpassword"
            />
            <Field
              ref="forgottenpasswordEmail"
              label="E-mail cím"
              type="text"
              validation="email"
              autocompleteOn
              name="email"
              @onEnter="onForgottenpassword"
            />
          </Form>
          <div class="cflex">
            <Button @click="onForgottenpassword">Küldés</Button>
          </div>
          <div class="link">
            <a href="#login">Bejelentkezés</a>
          </div>
        </Panel>
        <Panel
          title="Új jelszó megadása"
          border
          h
          class="startPagePanel"
          size="auto"
          v-if="page == 'newpassword'"
          padding
        >
          <Form topLabels noFixWidth>
            <slot name="newpasswordFields" />
            <Field label="Jelszó" type="password" name="password" />
            <Field
              label="Jelszó megerősítése"
              type="password"
              name="password_confirmation"
            />
          </Form>
          <div class="cflex">
            <Button @click="onnewpassword">Küldés</Button>
          </div>
          <div class="link">
            <a href="#login">Bejelentkezés</a>
          </div>
        </Panel>
        <Panel
          v-if="page == 'activate'"
          title="Regisztráció megerősítése"
          border
          h
          class="startPagePanel"
          padding
          size="auto"
        >
          <Form topLabels noFixWidth>
            <Field label="Jelszó" type="password" name="password" />
            <Field
              label="Jelszó megerősítése"
              type="password"
              name="password_confirmation"
            />
          </Form>
          <div class="cflex">
            <Button @click="activate">Küldés</Button>
          </div>
          <div class="link">
            <a href="#login">Bejelentkezés</a>
          </div>
        </Panel>
      </div>
      <div class="cflex" v-if="config">
        <div class="loginInformBox">
          <div class="loginInformBoxCompany" v-if="config.informative_text">
            {{ config.informative_text }}
          </div>
          <div class="cflex" v-if="config.company && config.informative_text">
            <div class="loginTextSeparator"></div>
          </div>
          <div class="loginCompanyName" v-if="config.company">
            {{ config.company }}
          </div>
        </div>
      </div>
      <div class="fit"></div>
    </div>
  </div>
</template>

<script>
export default {
  mounted: function () {
    document.title = "Belépés";
    if (!this.page) this.setActive(this.getCpHash());
    this.$nextTick(() => {
      if (
        this.$refs.login &&
        this.$refs.login.$refs.input &&
        this.$refs.login.$refs.input.$refs.input
      ) {
        this.$refs.login.$refs.input.$refs.input.focus();
      }
    });
  },
  data: function () {
    return {
      page: null,
      code: null,
      config: config,
    };
  },
  watch: {
    page: function (val) {
      val = val || "login";
      this.setHash(val);
    },
  },
  methods: {
    setActive: function (val) {
      val = val || "login";
      if (val == this.page) return;

      let tmp = val.split("|");

      switch (tmp.shift()) {
        case "activate":
          this.code = tmp.shift();
          this.page = "activate";
          break;
        case "forgottenPassword":
          this.page = "forgottenPassword";
          break;
        case "newpassword":
          this.code = tmp.shift();
          // dd(this.code);
          this.page = "newpassword";
          break;
        default:
          this.page = "login";
          break;
      }
    },
    doDarkMode: function () {
      this.$root.DarkMode = !this.$root.DarkMode;
    },
    $hash: function (hash) {
      this.setActive(hash);
    },
    onLogin: function () {
      var me = this;
      if (!this.formValidate()) return;
      var values = this.formGet();
      if (empty(values.login) || empty(values.password)) return;
      maskOn();

      tokenHandler.login(
        {
          login: values.login,
          password: values.password,
        },
        function (s, d, c) {
          if (!s) {
            maskOff();
            if (c == 422) {
              if(empty(d.title)){
                msg(d.message, "error");
              }else{
                msg(d.title, "error", null, d.message);
              }
            } else {
              msg("Hibás felhasználónév vagy jelszó!", "error");
            }
          } else {
            me.setHash();
          }
        }
      );
    },
    onForgottenpassword: function () {
      var me = this;
      if (!this.formValidate()) return;
      getStore("auth.forgottenpassword").create(this.formGet(), function () {
        msg("A jelszó cseréhez szükséges kérelem beküldve!", "info");
        me.page = "login";
      });
    },
    activate: function () {
      if (empty(this.code)) return;
      if (!this.formValidate()) return;
      var me = this,
        vals = this.formGet();
      vals.activate_token = this.code;
      getStore("auth.confirmregistration").create(
        vals,
        function (s, data, code) {
          if (!s) {
            msg("Sikertelen!", "error", null, data.message);
            if (code == 422) {
              me.formSetErrors(data);
            }
            return;
          }
          msg("Sikeres aktiválás!", null, function () {
            me.page = "login";
          });
        }
      );
    },
    onnewpassword: function () {
      if (empty(this.code)) return;
      if (!this.formValidate()) return;
      var me = this,
        vals = this.formGet();
      vals.activate_token = this.code;
      getStore("auth.passwordchange").create(vals, function (s, data, code) {
        if (!s) {
          msg("Sikertelen jelszó módosítás!", "error", null, data.message);
          if (code == 422) {
            me.formSetErrors(data);
          }
          return;
        }
        msg("Sikeres jelszó módosítás!", null, function () {
          me.page = "login";
        });
      });
    },
  },
};
</script>


<style scoped>
.loginPage {
  background: radial-gradient(circle at center, #f1f1f1 40%, #aaa);
}

.DarkMode .loginPage {
  background: none;
}

.startPagePanel {
  width: 360px;
  background-color: white;
  box-shadow: 0px 0px 50px 12px #ddd;
}

.loginInformBox {
  width: 340px;
  text-align: center;
  font-size: 13px;
  padding: 10px;
  margin-top: 20px;
}

.loginInformBoxCompany {
  font-style: italic;
}

.loginTextSeparator {
  border: 1px solid #777;
  margin: 20px;
  width: 40px;
}

.loginCompanyName {
  color: var(--higlight-text-color);
  font-weight: bold;
}

.login-system-users {
  margin-bottom: 30px;
  font-size: 18px;
}

a:visited,
a {
  text-decoration: none;
}

.link {
  text-align: end;
}

.message {
  font-weight: bold;
  padding-bottom: 10px;
}

.login-site-name {
  font-size: 70px;
  animation: lights 20s 10s linear infinite;
  font-weight: normal;
  color: var(--higlight-text-color);
  margin-bottom: 20px;
}

.logoImageDiv {
  margin-bottom: 20px;
}

.loginMainPage {
  overflow: auto;
}

.loginMainPage > div {
  min-height: 10px;
}

.DarkModeLogin {
  position: absolute;
  top: 20px;
  right: 20px;
}
</style>
