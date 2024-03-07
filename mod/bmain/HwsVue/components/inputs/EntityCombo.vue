<template>
  <div v-if="$root.entity">
    <Field
      v-bind="$attrs"
      title="Aktív entitás"
      class="vflex entityCombo"
      type="combo"
      noBlank
      nosearch
      placeHolder=""
      :startValue="$root.entity.active"
      :boxes="$root.entity.list"
      @change="onChange"
      noLabel
    >
      <div class="icon cflex">&#xf0e8;</div>
    </Field>
  </div>
</template>

<script>
export default {
  methods: {
    onChange: function (val) {
      var me = this;
      this.$root.changeEntity(val, function () {
        //callbackbe kellett rakni, különben a grid -ben lévő showGrid computed érték hamarabb fut le és nem frissül
        let pager = me.up("Pager");
        if (pager) pager.doLoad();
      });
    },
  },
};
</script>