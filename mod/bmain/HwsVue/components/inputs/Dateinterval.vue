<template>
  <div class="fit vflex intervalFieldCt fieldbody">
    <Field
      type="date"
      :name="name + '>'"
      class="fit"
      @change="valChange"
      :disabled="$attrs.disabled"
      :max="max"
      ref="firstField"
      noLabel
    />
    <div class="cflex itervalIcon noselect">-</div>
    <Field
      type="date"
      :name="name + '<'"
      class="fit"
      @change="valChange"
      :disabled="$attrs.disabled"
      :min="min"
      ref="secondField"
      noLabel
    />
  </div>
</template>

<script>
export default {
  props: {
    name: String,
  },
  data: function () {
    return {
      min: null,
      max: null,
    };
  },
  methods: {
    valChange: function (val, field) {
      if (field.name == this.name + ">") {
        this.min = val;
        if (!empty(val) && empty(this.$refs.secondField.getValue())) {
          //ha datepicker nyitva volt/van akkor a másik datpicerre menjen át
          //ha kézel töltik ki a mezőt akkor fókuszáljon a második mezőre
          if (document.activeElement.classList.contains("datepicker")) {
            this.$refs.secondField.$refs.input.pickerShow(
              null,
              this.$refs.firstField.getValue()
            );
          } else {
            this.$refs.secondField.$refs.input.$el
              .querySelector("input")
              .focus();
          }
        }
      }

      if (field.name == this.name + "<") {
        this.max = val;
      }

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