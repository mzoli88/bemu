<template>
  <div class="Searchdate fit hflex inputgroup">
    <div class="fit vflex intervalFieldCt fieldbody">
      <div
        class="cflex pointer fieldButtonFront"
        :tabindex="$attrs.disabled ? null : 0"
        @click="intervalChange"
        @keydown.space="intervalChange"
        :title="interval ? 'Dátum' : 'Intervallum'"
      >
        <div
          class="icon dateplus"
          :class="interval ? 'intervalOff' : 'intervalOn'"
        ></div>
      </div>
      <Field
        v-if="interval"
        :key="interval ? 'interval' : 'date'"
        type="dateinterval"
        class="fit"
        :name="name"
        :disabled="$attrs.disabled"
        noLabel
      />
      <Field
        v-else
        type="date"
        class="fit"
        :name="name"
        :disabled="$attrs.disabled"
        noLabel
      />
    </div>
  </div>
</template>

<script>
export default {
  props: {
    name: String,
  },
  data: function () {
    return {
      interval: false,
    };
  },
  methods: {
    intervalChange: function () {
      if (this.$attrs.disabled) return false;
      this.interval = !this.interval;
      this.$el.focus();
    },
  },
};
</script>

<style scoped>
.itervalIcon {
  padding: 4px;
}
</style>