<template>
  <Field type="entity" name="entity_id" required v-if="getUserData().isBorder" @change="change" />

  <Tab>
    <Panel title="E-mail">
      <Smtp />
    </Panel>
    <Panel title="Push">
      <Push />
    </Panel>
    <Panel title="Általános">
      <General />
    </Panel>
  </Tab>
</template>

<script>
export default {
  created: function () {
    if(getUserData().isBorder){
      getStore('general').fixQuery({ entity_id: getActiveEntity() });
      getStore('smtp').fixQuery({ entity_id: getActiveEntity() });
    }
  },
  components: {
    Smtp: cLoad("smtp"),
    Push: cLoad("push"),
    General: cLoad("general"),
  },
  methods: {
    change: function (entity_id) {
      this.$nextTick(() => {
        getStore('general').fixQuery({ entity_id: getActiveEntity() });
        getStore('smtp').fixQuery({ entity_id: getActiveEntity() });
        var form = this.down('Form');
        if (form) form.doload();
      });

    }
  },
};
</script>