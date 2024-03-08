<template>
  <Dropdown class="timePicker noselect" tabindex="0" @blur="obBlur" noDfocus>
    <div class="padding">
      {{ date + " " + hour + ":" + minute }}
    </div>
    <div class="vflex noFixWidth gap">
      <Field
        noLabel
        type="number"
        style="width: 150px"
        validation="min:0|max:23"
        floatErr
        max="23"
        ref="hour"
        @blur="obBlur"
        @change="onHourChange"
      />
      <div class="cflex">:</div>
      <Field
        noLabel
        type="number"
        style="width: 150px"
        validation="min:0|max:59"
        max="59"
        floatErr
        @blur="obBlur"
        ref="min"
        @change="onMinuteChange"
      />
    </div>
  </Dropdown>
</template>
<script>
export default {
  // props:
  mounted: function () {
    var focusEl = this.$refs.hour.$el.querySelector("input");
    if (focusEl) focusEl.focus();
  },
  data: function () {
    var input = this.up({
      pickerShow: Function,
    });
    // dd(input);
    return {
      date: input.timepickerVAlue,
      hour: "00",
      minute: "00",
    };
  },
  methods: {
    onHourChange: function (val, old) {
      val = val || 0;
      this.hour = val.toString().padStart(2, "0");
      if (val.toString().length == 2) {
        var focusEl = this.$refs.min.$el.querySelector("input");
        if (focusEl) focusEl.focus();
      }
    },
    onMinuteChange: function (val, old) {
      val = val || 0;
      this.minute = val.toString().padStart(2, "0");
      //   if (val.toString().length == 2) {
      // var focusEl = this.$refs.min.$el.querySelector("input");
      // if (focusEl) focusEl.focus();
      //   }
    },
    obBlur: function (e) {
      var input = this.up({
        pickerShow: Function,
      });
      if (!this.$el.contains(e.relatedTarget)) {
        // dd(this.date + " " + this.hour + ":" + this.minute);
        input.pickerSelect(this.date + " " + this.hour + ":" + this.minute);
      }
    },
  },
};
</script>