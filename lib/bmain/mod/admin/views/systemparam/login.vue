<template>
  <Panel border>
    <Form
      store="admin.params"
      load
      :save="hasPerm('systemparam_edit','SysAdmin')"
      :afterSave="afterSave"
    >
      <Field
        type="text"
        label="Rendszer megnevezése"
        name="system_name"
        required
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
      <Field
        type="text"
        label="Ki használja a rendszert?"
        name="login_system_users"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
      <Field
        type="textarea"
        label="Tájékoztató szöveg"
        name="login_informative_text"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
      <Field type="text" label="Cégnév" name="login_company" :noEdit="!hasPerm('systemparam_edit','SysAdmin')" />
      <Field type="slot" label="Logó" v-if="hasPerm('systemparam_edit','SysAdmin')">
        <div class="vflex" >
          <Field
            type="file"
            noLabel
            name="login_logo"
            accept="image/png, image/gif, image/jpeg"
          />
          <div class="cflex" v-if="canDeleteLogo()">
            <Button title="Törlés" @click="deleteLogo" />
          </div>
        </div>
      </Field>
    </Form>
  </Panel>
</template>

<script>
export default {
  methods: {
    canDeleteLogo: function () {
      return !empty(config.login_logo);
    },
    afterSave: function () {
      config.login_logo = 1;
    },
    deleteLogo: function () {
      ask("Biztosan törölni szeretné a logo-t?", (x) => {
        config.login_logo = null;
        getStore("deletelogo").delete();
      });
    },
  },
};
</script>
