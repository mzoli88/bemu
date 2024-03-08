<template>
  <div class="fit vflex intervalFieldCt fieldbody">
    <Field
      type="number"
      class="fit"
      :name="name + '>'"
      @change="valChange"
      :disabled="$attrs.disabled"
      noLabel
      :negative="negative"
      :float="float"
    />
    <div class="cflex itervalIcon noselect">-</div>
    <Field
      type="number"
      class="fit"
      :name="name + '<'"
      @change="valChange"
      :disabled="$attrs.disabled"
      noLabel
      :negative="negative"
      :float="float"
    />
  </div>
</template>

<script>
export default {
  props: {
    name: String,
    negative: Boolean,
    float: [Number, String],
  },
  methods: {
    valChange: function (val, field) {
      this.up("Field").validate();
    },
  },
};
</script>

<style scoped>
.itervalIcon {
  padding: 2px;
}
</style>
